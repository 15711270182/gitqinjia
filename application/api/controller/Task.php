<?php


namespace app\api\controller;

use think\Db;
use app\api\controller\Base;
/**
 * Class Task
 */
class Task extends Base
{
    /**
     * @Notes:日推荐数 数据统计
     * @Interface day
     * @author: zy
     * @Time: 2021/4/20   11:55
     */
    public function tjday(){
        $date = date('Y-m-d',strtotime('-1 days'));
        $start = strtotime($date.'000000');
        $end = strtotime($date.'235959');
        $find = DB::name("statistical_report")->where(['date'=>$date])->find();
        if(!empty($find)){
             echo 'ok';die;
        }
        $rdate = date('Ymd',strtotime($date));
        $rcount = Db::table('recommend_record')->where(['date'=>$rdate])->group('recommendid')->count();
        $start_time = date('Y-m-d 00:00:00',strtotime($date));
        $end_time = date('Y-m-d 23:59:59',strtotime($date));
        $browse_num = Db::table('view_info_record')->where('create_time', 'between',[$start_time, $end_time])->group('uid')->count(); //今日浏览人数
        $browsed_num = Db::table('view_info_record')->where('create_time', 'between',[$start_time, $end_time])->group('bid')->count(); //今日被浏览人数

        $tcount = $this->getTelList($start,$end);
        $data = [
            'rcount'=>$rcount,
            'tcount'=>count($tcount),
            'browse_num'=>$browse_num,
            'browsed_num'=>$browsed_num,
            'date'=>$date,
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $res = DB::table('statistical_report')->insertGetId($data);
        if($res){
             echo 'ok';die;
        }
        echo 'error';die;
    }

    public function getTelList($start, $end)
    {
        return Db::table('tel_collection')->where('create_at', 'between',[$start, $end])->where(['status'=>1,'type'=>1])->order('create_at desc')->select();
//
//        return DB::name("tel_collection")->alias('t')->join('tel_collection telB','t.uid = telB.bid and t.bid = telB.uid')
//            ->where('t.create_at', 'between',[$start, $end])->where(['t.status'=>1,'telB.status'=>1])->order('t.create_at desc')->select();
    }

   public function send(){
       /*****************
         * 非加密请求 示例代码
         ******************/
        //appid参数 appkey参数在     短信-创建/管理AppID中获取
        //手机号支持单个
        //短信内容：签名+正文    详细规则查看短信-模板管理
//        $appid = '67062';                                                               //appid参数
//        $appkey = 'ecdfa8e0597260657535c24bb18b1cb9';                                   //appkey参数
//        $to = '18551821638';                                                            //收信人 手机号码
//        $project_id = 'pjjUb4';                                                           //模板ID
//        $vars = json_encode([                                                    //模板对应变量
//           'realname' => '张',
//           'url' => 'http://www.baidu.com'
//        ]);
//
//        $post_data = array(
//            "appid"     => $appid,
//            "signature" => $appkey,
//            "to"        => $to,
//            "project"   => $project_id,
//            "vars"      => $vars
//        );
//        $ch = curl_init();
//        curl_setopt_array($ch, array(
//            CURLOPT_URL            => 'https://api.mysubmail.com/message/xsend.json',
//            CURLOPT_RETURNTRANSFER => 1,
//            CURLOPT_POST           => 1,
//            CURLOPT_POSTFIELDS     => $post_data
//        ));
//        $output = curl_exec($ch);
//        curl_close($ch);
//        echo $output;die;


        $appid = '67062';                                                               //appid参数
        $appkey = 'ecdfa8e0597260657535c24bb18b1cb9';                                   //appkey参数
        $to = '15711270182';                                                            //收信人 手机号码
        $project_id = 'pjjUb4';                                                           //模板ID
        $vars = json_encode([                                                  //模板对应变量
            'realname' => '张',
            'url' => 'https://testqin.njzec.com/h5/web/send'
        ]);
        //通过接口获取时间戳
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => 'https://api.mysubmail.com/service/timestamp.json',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST           => 0
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        $timestamp = $output['timestamp'];
        $post_data = [
            "appid"        => $appid,
            "to"           => $to,
            "project"      => $project_id,
            "timestamp"    => $timestamp,
            "sign_type"    => 'md5',
            "sign_version" => 2,
            "vars"         => $vars ,
        ];
        //整理生成签名所需参数
        $temp = $post_data;
        unset($temp['vars']);
        ksort($temp);
        reset($temp);
        $tempStr = "";
        foreach ($temp as $key => $value) {
            $tempStr .= $key . "=" . $value . "&";
        }
        $tempStr = substr($tempStr, 0, -1);
        //生成签名
        $post_data['signature'] = md5($appid . $appkey . $tempStr . $appid . $appkey);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => 'https://api.mysubmail.com/message/xsend.json',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $post_data
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        echo json_encode($output);
   }

}