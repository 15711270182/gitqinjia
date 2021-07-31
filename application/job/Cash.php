<?php
  namespace app\index\job;

  use think\queue\Job;
  use think\Db;
  use JiaweiXS\WeApp\WeApp;

  /**
   * 用户提现消化类
   * Class Cash
   * @package app\index\job
   */
  class Cash {
      
      /**
       * fire方法是消息队列默认调用的方法
       * @param Job            $job      当前的任务对象
       * @param array|mixed    $data     发布任务时自定义的数据
       */
      public function fire(Job $job,$data)
      {
          // 有些消息在到达消费者时,可能已经不再需要执行了
          custom_log('cashJobFire','data==='.print_r($data,true));
          $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
          if(!$isJobStillNeedToBeDone){
            custom_log('cashJobFire','结束');
              $job->delete();
              return;
          }
        
          $isJobDone = $this->doCashJob($data);
        
          if ($isJobDone) {
              // 如果任务执行成功， 记得删除任务
              $job->delete();
              custom_log('cashJobFire','data==='.'Job has been done and deleted"');
            //  print("<info>Hello Job has been done and deleted"."</info>\n");
          }else{
              if ($job->attempts() > 3) {
                  //通过这个方法可以检查这个任务已经重试了几次了
                  custom_log('cashJobFire','data==='.'Job has been retried more than 3 times');
                  print("<warn>Hello Job has been retried more than 3 times!"."</warn>\n");
                  
                  $job->delete();
                  
                  // 也可以重新发布这个任务
                  //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
                  //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
              }
          }
      }
      
      /**
       * 有些消息在到达消费者时,可能已经不再需要执行了
       * @param array|mixed    $data     发布任务时自定义的数据
       * @return boolean                 任务执行的结果
       */
      private function checkDatabaseToSeeIfJobNeedToBeDone($data){
          return true;
      }

      /**
       * 用户提现处理...
       */
      private function doCashJob($data)
      {
          $uid = $data['uid'];
          $openid = $data['openid'];
          $userCash = $data['usercash'];
          if(empty($uid)||empty($openid)||empty($userCash)){
              custom_log('cashJob','参数数据非法！！');
              return false;
          }
          $user_remain  = Db::table('userinfo')->where(['id' => $uid])->sum('balance');
          if($user_remain<0){
                 custom_log('cashJob','用户id=='.$uid.',提现金额小于0');
                 return true;
          }

          $map = array();
          $map['userid'] = $data['uid'];
          $map['type'] = 2;
          $out = Db::table('cash_change_record')->where($map)->sum('money');
          $map['type'] = 1;
          $in = Db::table('cash_change_record')->where($map)->sum('money');
          $last = bcsub($in, $out);
          if($last<=0){
                 custom_log('cashJob','用户id=='.$uid.',提现金额小于0');
                 return true;
          }
          $data = array();
          $data['balance'] = $userCash;
          $kouqian = Db::table('userinfo')->where($map)->update($data);
          if (!$kouqian) 
          {
              custom_log('cashJob','用户id=='.$uid.',提现金额小于0');
                 return true;
          }

          $orderNum = time().createRandStr(4);
          //添加用户提现明细记录
          $data = array();
          $data['userid'] = $uid;
          $data['money'] = $userCash;
          $data['category'] = 2;
          $data['type'] = 2;
          $data['addtime'] = time();
          $data['desc'] = "提现";
          $lemu = Db::table('cash_change_record')->insertGetId($data);

          //执行提现操作
          //成功则修改提现订单状态
          $appid = 'wx6ebfe2b857d9ad9c';
          $secret = '7f929295211a8972b3054550105593ab';
          $mch_id = '1544198411';
          $key = 'weiyiwangluokejiweiyiwangluokeji';
          $weapp = new WeApp($appid, $secret,'./little/'.$appid.'/');

          $payObj = $weapp->getPayObj($mch_id,$key,'./config/base_cert');

          $data['openid'] = $openid;
          $data['orderNum'] = $orderNum;
          $data['amount'] = $userCash;
          $data['desc'] = '包你说提现';
          $data['ip'] =  request()->ip();

          // $check_result = $this->checkUserCash($uid,$userCash);
          if(!$check_result){
              custom_log("cashJob", '提现验证失败，用户id，提现金额，提现时间=='.$uid.','.$userCash.',时间,'.date('Y-m-d H:i:s'));
              return false;
          }
//          custom_log('cash','uid==='.$uid);
//          custom_log('cash',print_r($data,true));
          $result_pay = $payObj->createTransfers($data);
          custom_log('cashJob',print_r($result_pay,true));
          if($result_pay['return_code']=='SUCCESS' && $result_pay['result_code']=='SUCCESS') {

              //更新提现表
              $map = array();
              $map['id'] = $lemu;
              $data = array();
              $data['order_num'] = $orderNum;
              Db::table('cash_change_record')->where($map)->update($data);

          }else if ($result_pay['err_code'] == "MONEY_LIMIT" || $result_pay['err_code'] == "NOTENOUGH" ) {
             

              $dat = array();
              $dat['uid'] = $uid;
              $dat['openid'] = $openid;
              $dat['money'] = $userCash;
              $dat['order_num'] = $orderNum;
              $dat['addtime'] = date('Y-m-d H:i:s');
              $dat['reason'] = $result_pay['err_code'] == "NOTENOUGH" ? "余额不足" : "提现账户已达上限";
              $fail = Db::table('cash_fail')->insert($dat,false,true);
              //发送短信提示微信账户余额不足
              $to = "18951946955, 15195918065, 19951927232";
              // $to = "18914363031, 18915947326";
              $datas = [];
              $res_d = $this->sendTemplateSMS($to, $datas, 422544);

              if ($fail) {
                 custom_log('cashJob','写入提现失败表成功，等待后续处理');
              } else {
                  custom_log('cashJob','写入提现失败表失败');
              }
          } else {
              $faile_number =  Cache::store('redis')->get('faile_count_'.$uid);
              if(empty($faile_number)){
                  $faile_number = 0;
              }
              $new_faile_number = $faile_number+1;
              Cache::store('redis')->set('faile_count_'.$uid,$new_faile_number,6*3600);

              custom_log('cashJob',print_r($result_pay,true).'=====uid==='.$uid);
          }
          return true;
      }



      /**
       * 发送模板短信
       * @param to 短信接收彿手机号码集合,用英文逗号分开
       * @param datas 内容数据
       * @param $tempId 模板Id
       */
      public function sendTemplateSMS($to,$datas,$tempId)
      {
          //主帐号,对应开官网发者主账号下的 ACCOUNT SID
          $accountSid = '8aaf0708697b6beb0169c8317c02378c';

          //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
          $accountToken = '76e1d7f2580e46c892144f659c194ea0';

          //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
          //在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
          $appId = '8a216da869c8398f0169c86986ef004e';

          //请求地址
          //沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
          //生产环境（用户应用上线使用）：app.cloopen.com
          $serverIP = 'sandboxapp.cloopen.com';


          //请求端口，生产环境和沙盒环境一致
          $serverPort = '8883';

          //REST版本号，在官网文档REST介绍中获得。
          $softVersion = '2013-12-26';
          $BodyType = 'xml';

          //主帐号鉴权信息验证，对必选参数进行判空。
          $auth=$this->accAuth();
          if($auth!=""){
              return $auth;
          }
          // 拼接请求包体
          if($BodyType=="json"){
              $data="";
              for($i=0;$i<count($datas);$i++){
                  $data = $data. "'".$datas[$i]."',";
              }
              $body= "{'to':'$to','templateId':'$tempId','appId':'$appId','datas':[".$data."]}";
          }else{
              $data="";
              for($i=0;$i<count($datas);$i++){
                  $data = $data. "<data>".$datas[$i]."</data>";
              }
              $body="<TemplateSMS>
            <to>$to</to>
            <appId>$appId</appId>
            <templateId>$tempId</templateId>
            <datas>".$data."</datas>
                  </TemplateSMS>";
          }
          //         $this->showlog("request body = ".$body);
          // 大写的sig参数
          $sig =  strtoupper(md5($accountSid . $accountToken . date("YmdHis")));
          // 生成请求URL
          $url="https://$serverIP:$serverPort/$softVersion/Accounts/$accountSid/SMS/TemplateSMS?sig=$sig";
          //         $this->showlog("request url = ".$url);
          // 生成授权：主帐户Id + 英文冒号 + 时间戳。
          $authen = base64_encode($accountSid . ":" . date("YmdHis"));
          // 生成包头
          $header = array("Accept:application/$BodyType","Content-Type:application/$BodyType;charset=utf-8","Authorization:$authen");
          // 发送请求
          $result = $this->curl_post($url,$body,$header);
          //         $this->showlog("response body = ".$result);
          //         if($this->BodyType=="json"){//JSON格式
          //            $datas=json_decode($result);
          //         }else{ //xml格式
          //            $datas = simplexml_load_string(trim($result," \t\n\r"));
          //         }
          //    //    if($datas == FALSE){
          //    //        $datas = new stdClass();
          //    //        $datas->statusCode = '172003';
          //    //        $datas->statusMsg = '返回包体错误';
          //    //    }
          //重新装填数据
          //         if($datas->statusCode==0){
          //          if($this->BodyType=="json"){
          //             $datas->TemplateSMS =$datas->templateSMS;
          //             unset($datas->templateSMS);
          //           }
          //         }

          //         return $datas;
          return $result;
      }

      /**
       * 主帐号鉴权
       */
      public function accAuth()
      {
          //主帐号,对应开官网发者主账号下的 ACCOUNT SID
          $accountSid= '8aaf0708697b6beb0169c8317c02378c';

          //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
          $accountToken= '76e1d7f2580e46c892144f659c194ea0';

          //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
          //在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
          $appId='8a216da869c8398f0169c86986ef004e';

          //请求地址
          //沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
          //生产环境（用户应用上线使用）：app.cloopen.com
          $serverIP='app.cloopen.com';


          //请求端口，生产环境和沙盒环境一致
          $serverPort='8883';

          //REST版本号，在官网文档REST介绍中获得。
          $softVersion='2013-12-26';
          if($serverIP==""){
              $data = new \stdClass();
              $data->statusCode = '172004';
              $data->statusMsg = 'IP为空';
              return $data;
          }
          if($serverPort<=0){
              $data = new \stdClass();
              $data->statusCode = '172005';
              $data->statusMsg = '端口错误（小于等于0）';
              return $data;
          }
          if($softVersion==""){
              $data = new \stdClass();
              $data->statusCode = '172013';
              $data->statusMsg = '版本号为空';
              return $data;
          }
          if($accountSid==""){
              $data = new \stdClass();
              $data->statusCode = '172006';
              $data->statusMsg = '主帐号为空';
              return $data;
          }
          if($accountToken==""){
              $data = new \stdClass();
              $data->statusCode = '172007';
              $data->statusMsg = '主帐号令牌为空';
              return $data;
          }
          if($appId==""){
              $data = new \stdClass();
              $data->statusCode = '172012';
              $data->statusMsg = '应用ID为空';
              return $data;
          }
      }

      /**
       * 检验用户提现，是否异常，如果异常则不给用户提现
       * @param $uid
       * @param $curr_with_money
       * @return bool
       */
      public function checkUserCash($uid, $curr_with_money){
          //验证出题
          //根据uid获取该用户所有出题记录的集合,注意：红包状态为已退回
          $subject_list = db('subject')->where(['userid' => $uid, 'pay_status' => 1, 'status' => 1])->field('id')->column('id');
          $score_list = db('answer')->where(['userid' => $uid])->field('subjectid')->column('subjectid');
          //如果出题sid集合不为空,进行出题验证
          $refund_money = 0;
          if(!empty($subject_list)){
              //取出该用户对应的sid的集合，并且求出出题金额的总和
              $total_sub_money = db('subject')->where(['userid' => $uid, 'pay_status' => 1, 'status' => 2])->sum('money');

              $map[] = ['subjectid','in',$subject_list];
              //搜索ans_refund表，获取所有退款金额
              $refund_money = db('subject_red_packets')->where($map)->where(['userid' => $uid,'status' => 1])->sum('money');

              //出题金额<退款金额，则为异常，记录异常，继续执行
              if($total_sub_money < $refund_money){
                  custom_log('withdrawalCheck', '用户出题异常：'.$uid);
                  return false;
              }
              //出题验正结束
          }

          //答题验证：
          //获取该用户，所有答题的集合

          $score_total_money = 0;

          //如果答题记录不为空
          if(!empty($score_list)){
              //获取用户参与所有答题的sid集合

              //通过sid集合求出所有，答题领红包的总金额
              $s_map[] = ['subjectid','in',$score_list];
              $s_map[] = ['userid','=',$uid];
              $s_map[] = ['status','=',1];
              $score_total_money = db('answer')->where($s_map)->sum('money');


              //通过sid集合求出所有，出题红包金额
              $sub_map[] = ['id','in',$score_list];
              $total_subject_money = (int)db('subject')->where($sub_map)->where(['pay_status' => 1])->sum('bonus_total');

              //如果答题金额 > 出题金额，返回false,记录异常
              if($score_total_money > $total_subject_money){
                  custom_log('withdrawalCheck', '用户答题异常：'.$uid);
                  return false;
              }

          }

          //搜索ans_my_score表，如果当前用户有多条一样的用户，返回报错，拉进黑名单，每个用户一套题目只能答一次
          $sub_res = db('answer')->where(['userid' => $uid])->field('count(subjectid) as c_num')
              ->group('subjectid')->order('subjectid desc')->having('count(*)>1')->select();
          if(!empty($sub_res)){
              custom_log('withdrawalCheck', '用户答题异常：'.$uid);
              return false;
          }
          //如果该用户出题、答题记录都为空，标记异常，返回false
          if(empty($score_list) && empty($subject_list)){
              custom_log('withdrawalCheck', '用户出题、答题皆异常：'.$uid);
              return false;
          }

          //提现验证
          //退款金额 + 答题金额 - 提现金额 >= 当前提现余额
          //该用户已提现金额
          $withdrawal = db('cash_change_record')->where(['userid' => $uid, 'type' => 2, 'category' => 2])->sum('money');
          $real_num = $refund_money + $score_total_money - $withdrawal;

          if($real_num < $curr_with_money){
              custom_log('withdrawalCheck', '用户余额异常：'.$uid);
              return false;
          }
          //通过所有验证，恭喜过关，返回true
          return true;
      }
      /**
       * 发起HTTPS请求
       */
      public function curl_post($url,$data,$header,$post=1)
      {
          //初始化curl
          $ch = curl_init();
          //参数设置
          $res= curl_setopt ($ch, CURLOPT_URL,$url);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
          curl_setopt ($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_POST, $post);
          if($post)
              curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
          curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
          $result = curl_exec ($ch);
          //连接失败
          if($result == FALSE){
              if($this->BodyType=='json'){
                  $result = "{\"statusCode\":\"172001\",\"statusMsg\":\"网络错误\"}";
              } else {
                  $result = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><Response><statusCode>172001</statusCode><statusMsg>网络错误</statusMsg></Response>";
              }
          }

          curl_close($ch);
          return $result;
      }

  }