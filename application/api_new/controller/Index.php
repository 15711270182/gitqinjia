<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\api_new\controller;
use app\api_new\model\TelCollection;
use app\api_new\model\Video as VideoModel;
use app\api_new\model\WeightScore;
use app\api_new\service\ScoreService;
use app\api_new\service\UsersService;
use app\api_new\service\RecommendService;
use app\api_new\service\Send as SendService;
use app\api_new\service\Qrcode;
use app\api_new\service\Upload;
use app\api_new\model\Children as ChildrenModel;
use app\api_new\model\Collection as CollectionModel;
use app\api_new\model\TelCollection as TelModel;
use app\api_new\model\Product as ProductModel;
use app\api_new\model\User as UserModel;
use app\api_new\model\Team as TeamModel;
use app\api_new\model\Poster as PosterModel;
use app\api_new\model\Order as OrderModel;
use app\wechat\service\WechatService;
use think\Controller;
use think\Db;
use think\Queue;

use think\facade\Cache;
use WeChat\Product;

/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Index extends Base
{
    /**
     * @Notes:首页视频认证用户 登录情况下
     * @Interface home_video
     * @return string
     * @author: zy
     * @Time: 2021/12 /03
     */
    public function home_video(){
        $uid = $this->uid;
        $page = input('page', 1, 'intval');
        $pageSize = input('pageSize', 10, 'intval');

        $user_info = Db::name('children')->where(['uid'=>$uid])->find();
        if (empty($user_info)){
            return $this->errorReturn(self::errcode_fail,'孩子资料未完善');
        }
        $sex = 1;
        if($user_info['sex'] == 1){
            $sex = 2;
        }
        $residence = $user_info['residence'];
        $where = "sex = {$sex} and auth_status = 1 and status = 1 and is_ban = 1 and is_del = 1 and video_url != '' and residence like '{$residence}%'";
        $list = ChildrenModel::childrenSelectPage($where,'','id desc',$page,$pageSize);
        $totalCount = Db::name('children')->where($where)->count();
        $totalPage = ceil($totalCount / $pageSize);
        $data = [];
        $data['totalCount'] = $totalCount;
        $data['totalPage'] = $totalPage;
        if(empty($list)){
            $data['list'] = [];
            return $this->successReturn($data,'暂无数据',self::errcode_ok);
            // return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        $rec = new RecommendService();
        $new_list = $rec->getDataList($list);

        $data['list'] = $new_list;
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
    /**
     * @Notes:首页推荐 登录情况下  已认证
     * @Interface home
     * @return string
     * @author: zy
     * @Time: 2021/12 /03
     */
    public function home()
    {
        $uid = $this->uid;
        $page = input('page', 1, 'intval');
        $pageSize = input('pageSize', 10, 'intval');
        
        $rec = new RecommendService();
        $data = $rec->getRecommendNew($uid,$page,$pageSize);
        //是否关注公众号
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $data['switch_auth'] = $userinfo['switch_auth'];
        $where_wx = "unionid = '{$userinfo['unionid']}' and subscribe_at is not null";
        $is_gz = UserModel::wxFind($where_wx);
        $is_wechat = !empty($is_gz)?1:0;
        $data['is_wechat'] = $is_wechat;
        //判断资料是否完善
        $field_c = 'native_place,education,work,income,school,house,cart,expect_education,parents,bro,min_age,min_height';
        $cInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_c);
        $info = array_values($cInfo);
        $info_status = 1; //资料完善
        foreach($info as $k=>$v){
            if(empty($v)){
                 $info_status = 0; //资料未完善
                 break;
            }
        }
        if($info_status == 1){
            $realname = UserModel::userValue(['id'=>$uid],'realname');
            if(empty($realname)){
                $info_status = 0; //资料未完善
            }
        }
        $data['info_status'] = $info_status;
        //判断择偶标准相亲说明是否完善
        $field_e = 'remarks,expect_education,min_age,min_height';
        $eInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_e);
        $eInfo = array_values($eInfo);
        $info_exp_status = 1; //资料完善
        foreach($eInfo as $k=>$v){
            if(empty($v)){
                 $info_exp_status = 0; //资料未完善
                 break;
            }
        }
        $data['info_exp_status'] = $info_exp_status;

        $field_a = 'auth_status,id_name,id_number,search_auth';
        $ccInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_a);
        //实名认证状态   1已实名  2未支付未实名  3已支付未填写身份信息  4 已支付人脸未通过（暂无）
        switch ($ccInfo['auth_status']) {
            case '1':
                $data['auth_status'] = 1;
                break;
            default:
                //判断88是否支付
                $oInfo = OrderModel::orderFind(['uid'=>$uid,'status'=>1,'source'=>2]);
                if($oInfo){
                    if(empty($ccInfo['id_name']) && empty($ccInfo['id_number'])){
                        $data['auth_status'] = 3;
                    }else{
                        $data['auth_status'] = 4;
                    }
                }else{
                    $data['auth_status'] = 2;
                }
                break;
        }
        $data['search_auth'] = $ccInfo['search_auth'];
        //取用户需要的支付的类型 1:购买会员 2：购买次数
        $paytype = $userinfo['paytype'];
        $data['paytype'] = $paytype;
        // $field = "id,title,type,num,price,create_at,discount,old_price";
        // $product = ProductModel::productSelect(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        // foreach ($product as $key => $value) {
        //     $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
        //     if($paytype == 1){
        //         $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
        //     }
        // }
        return $this->successReturn($data,'成功',self::errcode_ok);
    }

    /**
     * @Notes:首页推荐 登录情况下  未认证
     * @Interface home_unauthorized
     * @return string
     * @author: zy
     * @Time: 2022/01/04
     */
    public function home_unauthorized()
    {
        $uid = $this->uid;
        $page = input('page', 1, 'intval');
        $pageSize = input('pageSize', 10, 'intval');
        
        $rec = new RecommendService();
        $data = $rec->getRecommendNewUnAuth($uid,$page,$pageSize);
        //是否关注公众号
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $where_wx = "unionid = '{$userinfo['unionid']}' and subscribe_at is not null";
        $is_gz = UserModel::wxFind($where_wx);
        $is_wechat = !empty($is_gz)?1:0;
        $data['is_wechat'] = $is_wechat;
        //判断资料是否完善
        $field_c = 'native_place,education,work,income,school,house,cart,expect_education,parents,bro,min_age,min_height';
        $cInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_c);
        $info = array_values($cInfo);
        $info_status = 1; //资料完善
        foreach($info as $k=>$v){
            if(empty($v)){
                 $info_status = 0; //资料未完善
                 break;
            }
        }
        if($info_status == 1){
            $realname = UserModel::userValue(['id'=>$uid],'realname');
            if(empty($realname)){
                $info_status = 0; //资料未完善
            }
        }
        $data['info_status'] = $info_status;
        //判断择偶标准相亲说明是否完善
        $field_e = 'remarks,expect_education,min_age,min_height';
        $eInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_e);
        $eInfo = array_values($eInfo);
        $info_exp_status = 1; //资料完善
        foreach($eInfo as $k=>$v){
            if(empty($v)){
                 $info_exp_status = 0; //资料未完善
                 break;
            }
        }
        $data['info_exp_status'] = $info_exp_status;

        $field_a = 'auth_status,id_name,id_number,search_auth';
        $ccInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_a);
        //实名认证状态   1已实名  2未支付未实名  3已支付未填写身份信息  4 已支付人脸未通过（暂无）
        switch ($ccInfo['auth_status']) {
            case '1':
                $data['auth_status'] = 1;
                break;
            default:
                //判断88是否支付
                $oInfo = OrderModel::orderFind(['uid'=>$uid,'status'=>1,'source'=>2]);
                if($oInfo){
                    if(empty($ccInfo['id_name']) && empty($ccInfo['id_number'])){
                        $data['auth_status'] = 3;
                    }else{
                        $data['auth_status'] = 4;
                    }
                }else{
                    $data['auth_status'] = 2;
                }
                break;
        }
        $data['search_auth'] = $ccInfo['search_auth'];
        //取用户需要的支付的类型 1:购买会员 2：购买次数
        $paytype = $userinfo['paytype'];
        $data['paytype'] = $paytype;
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
    /**
     * @Notes:首页未登录情况下拉取用户信息
     * @Interface getuserlist
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function getuserlist()
    {
        //条件 已实名  排序 时间倒叙  完善度倒叙  50条数据
        $where['auth_status'] = 1;
        $where['status'] = 1;
        $where['is_ban'] = 1;
        $list = ChildrenModel::childrenSelectPage($where,'','login_last_time desc,video_url desc,full_info desc',0,50);
        $rec = new RecommendService();
        foreach($list as $key => $value){
            $list[$key] = $rec->userchange($value);
        }
        return $this->successReturn($list,'成功',self::errcode_ok);
    }
    /**
     * @Notes:相亲资料详情页  -  新版加入推荐数据
     * @Interface childrenDetailsNew
     * @return string
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function childrenDetailsNew()
    {
        $uid = $this->uid;
        $bid = input("bid"); //要查看的用户id
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        $children = ChildrenModel::childrenFind(['uid'=>$bid]);
        if(empty($children)){
            return $this->errorReturn(self::errcode_fail);
        }
        //添加查看记录
        if($uid != $bid){
            $info_save['uid'] = $uid;
            $info_save['bid'] = $bid;
            $info_save['create_time'] = date('Y-m-d H:i:s');
            Db::name('view_info_record')->insertGetId($info_save);
            if($uid != 354 && $uid != 1234 && $uid != 677 && $uid != 2210){
                $cache_bid = cache($uid.'_look_'.$bid);
                if(empty($cache_bid)){ //缓存没有  推送模板
                    //发送访客记录模板
                    $b_userinfo = UserModel::userFind(['id'=>$bid]);
                    $where_x = [];
                    $where_x['unionid'] = $b_userinfo['unionid'];
                    $where_x['subscribe'] = 1;
                    $mini_user = UserModel::wxFind($where_x);
                    if($mini_user && $mini_user['status'] == 1){ //用户关注 非注销 发送模板消息
                        $uuInfo = ChildrenModel::childrenFind(['uid'=>$uid],'phone,sex,year,residence');
                        
                        $nickname = UserModel::userValue(['id'=>$uid],'nickname');
                        $nickname = !empty($nickname)?$nickname:'匿名用户';
                        if(!empty($uuInfo['phone'])){
                            $phone = substr_cut_phone($uuInfo['phone']);
                            $page = 'pages/details/details?id='.$uid;
                            $pagepath = 'pages/details/details?id='.$uid;
                        }else{
                            $phone = '暂未填写';
                            $page = 'pages/home/home';
                            $pagepath = 'pages/home/home';
                        }

                        $openid = $mini_user['openid'];
                        $tip = '访客来访提醒';
                        $remark = '点击进入"完美亲家"小程序';
                        $temp_id = 'xVzOzhbKvh4lQSUeizI9M0rdQzeTiuQ7s3hnDme1_mA';
                        $arr = array();
                        $arr['first'] = array('value'=>$tip,'color'=>'#FF0000');
                        $arr['keyword1'] = array('value'=>$nickname,'color'=>'#FF0000');
                        $arr['keyword2'] = array('value'=>$phone,'color'=>'#0000ff');
                        $arr['keyword3'] = array('value'=>date('Y-m-d H:i:s'),'color'=>'#0000ff');
                        $arr['remark'] = array('value'=>$remark,'color'=>'#0000ff');
                        $param = [
                            'touser'=>$openid,
                            'template_id'=>$temp_id,
                            'page'=>$page,
                            'data'=>$arr,
                            'miniprogram' => [
                                'pagepath'=>$pagepath,
                                'appid'=>'wx70d65d2170dbacd7',
                            ],
                        ];
                        $res = $this->shiwuSendMsg($param);
                        if($res == true){
                            //24小时之内 查看过的人只发一次
                            cache($uid.'_look_'.$bid,$bid,24*3600);
                        }
                    }
                }
            }
        }
        $where_t['uid'] = $uid;
        $where_t['bid'] = $bid;
        $where_t['type'] = 2;
        $where_t['is_read'] = 0;
        $tInfo = TelModel::telFind($where_t);
        if(!empty($tInfo)){
            TelModel::telEdit(['uid'=>$uid,'bid'=>$bid],['is_read'=>1]);
        }
        //判断自己有没有完善资料  没有展示8位
        $newData = [];
        $find = ChildrenModel::childrenFind(['uid'=>$uid]);
        if(empty($find)){
            $start_year = $children['year'] - 2;
            $end_year = $children['year'] + 3;
            $where_j = "sex = {$children['sex']} and year between '{$start_year}' and '{$end_year}'";
            if($children['education'] <= 3){
                $where_j .= " and education >= 3";
            }else{
                $where_j .= " and education >= 4";
            }
            $field = "uid,sex,year,education,province,residence,height,income";
            $arr1 = ChildrenModel::childrenSelectPage($where_j,$field,'year desc',0,8);
            $count = count($arr1);
            $more_count = 8;
            $arr2 = [];
            if($count < $more_count){
                $last_count = $more_count - $count;
                if($last_count > 0){
                    $s_year = $children['year'] - 5;
                    $e_year = $children['year'] + 5;
                    $where_jn = "sex = {$children['sex']} and year between '{$s_year}' and '{$e_year}'";
                    $arr2 = ChildrenModel::childrenSelectPage($where_jn,$field,'year desc',0,$last_count);
                }
            }
            $newData = array_merge($arr1, $arr2);
            shuffle($newData);
            $uidData = array_column($newData,'uid');
            $where_u['id'] = $uidData;
            $realname = UserModel::getuserColumn($where_u,'id,realname');
            $headimgurl = UserModel::getuserColumn($where_u,'id,headimgurl');
            foreach($newData as $k=>$v){
                $newData[$k]['realname'] = isset($realname[$v['uid']])?mb_substr($realname[$v['uid']], 0,1 ).'家长':'家长';
                $newData[$k]['headimgurl'] = isset($headimgurl[$v['uid']])?$headimgurl[$v['uid']]:'https://pics.njzec.com/default.png';
                $newData[$k]['sex'] = $v['sex']==1?'男':'女';
                $newData[$k]['year'] = substr($v['year'], 2,2).'年';
                $newData[$k]['shuxiang'] = getShuXiang($v['year']);
                $newData[$k]['height'] = $v['height'].'cm';
                $newData[$k]['education'] = UsersService::education($v['education']);
                $newData[$k]['income'] = UsersService::income_new($v['income']);
                $province = str_replace(['省','市'],'',$v['province']);
                $residence = str_replace(['省','市'],'',$v['residence']);
                $newData[$k]['residence'] = $province.' '.$residence;
                unset($newData[$k]['province']);
            }
        }
        //被查看者没有填写相亲说明 择偶标准等  发送模板消息 或短信
        $b_find = ChildrenModel::childrenFind(['uid'=>$bid]);
        if(empty($b_find['remarks']) || empty($b_find['expect_education']) || empty($b_find['min_age']) || empty($b_find['min_height'])){
            $this->sendMessageBid($uid,$bid);
        }
        // else{
        //     $b_userinfo = UserModel::userFind(['id'=>$bid]);
        //     $where_x = [];
        //     $where_x['unionid'] = $b_userinfo['unionid'];
        //     $where_x['subscribe'] = 1;
        //     $mini_user = UserModel::wxFind($where_x);
        //     if($mini_user && $mini_user['status'] == 1){ //用户关注 非注销 发送模板消息
        //         $create_at = ChildrenModel::getchildrenField(['uid'=>$bid],'create_at');
        //         $nickname = !empty($userinfo['nickname'])?$userinfo['nickname']:'微信用户';
        //         $date = date('Y-m-d H:i:s',$create_at);
        //         $openid = $mini_user['openid'];
        //         $tip = '某家长刚查看了您的资料,对您资料比较感兴趣';
        //         $remark = '进入小程序查看';
        //         $temp_id = 'pFlGp3GAYyMdxQLoBXg18B-WLbW5hBA4GvqmsNZoIKo';
        //         $arr = array();
        //         $arr['first'] = array('value'=>$tip,'color'=>'#FF0000');
        //         $arr['keyword1'] = array('value'=>$nickname,'color'=>'#0000ff');
        //         $arr['keyword2'] = array('value'=>$date,'color'=>'#0000ff');
        //         $arr['remark'] = array('value'=>$remark,'color'=>'#0000ff');
        //         $param = [
        //             'touser'=>$openid,
        //             'template_id'=>$temp_id,
        //             'page'=>'pages/editProfile/editProfile',
        //             'data'=>$arr,
        //             'miniprogram' => [
        //                 'pagepath'=>'pages/editProfile/editProfile',
        //                 'appid'=>'wx70d65d2170dbacd7',
        //             ],
        //         ];
        //         $this->shiwuSendMsg($param);
        //     }
        // }
        //子女信息
        $userinfo = UserModel::userFind(['id'=>$bid]);
        $children['realname'] = $userinfo['realname'];
        $children['headimgurl'] = !empty($userinfo['headimgurl'])?$userinfo['headimgurl']:'https://pics.njzec.com/default.png';
        $children['sui'] = date('Y') - $children['year'];
        $children['shuxiang'] = getShuXiang($children['year']);
        $children['year'] = substr($children['year'],-2).'年';
        $children['user_status'] = $userinfo['status'];
        $children['education'] = UsersService::education($children['education']);
        $children['expect_education'] = UsersService::expect_education($children['expect_education']);
        $children['income'] = UsersService::income($children['income']);
        $children['house'] = UsersService::house($children['house']);
        $children['cart'] = UsersService::cart($children['cart']);
        $children['parents_test'] = UsersService::parents($children['parents']);
        $children['bro_test'] = UsersService::bro($children['bro']);
        //审核团队信息
        $team = TeamModel::teamFind(['id'=>$children['team_id']]);
        $children['sh_id'] = $team['id']; //审核队员几号
        $children['sh_headimg'] = $team['headimg']; //审核队员头像
        $children['sh_name'] = $team['name']; //审核队员名字
        $children['sh_time'] = rand(10,20); //审核队员时间
        //看看对方我是否收藏 1否 2是
        $is_collection = CollectionModel::collectionFind(['uid'=>$uid,'bid'=>$bid,'is_del'=>'1']);
        $children['is_collection'] = 2;
        if(empty($is_collection)){
            $children['is_collection'] = 1;
        }
        if($uid == $bid){
            $children['is_telcollection'] = 2;
            $children['is_me'] = 2;
            return $this->successReturn($children,'成功',self::errcode_ok);
        }
        //判断用户是否查看过手机号 1否 2是
        $is_telcollection = TelModel::telFind(['uid'=>$uid,'bid'=>$bid,'is_del'=>'1']);
        $children['is_telcollection'] = 2;
        if(empty($is_telcollection)){
            $children['phone'] = '家长电话';
            $children['is_telcollection'] = 1;
        }
        $children['is_me'] = 1;//不是自己
        $is_vip = UsersService::isVip($userinfo);
        $children['is_vip'] = $is_vip;
        $children['list'] = $newData;
        return $this->successReturn($children,'成功',self::errcode_ok);
    }
    /**
     * @Notes:点击{查看手机号}前的大概信息
     * @Interface onclickTel
     * @return string
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function onclickTel()
    {
        $uid = $this->uid;
        $bid = input("bid");
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        //查看有没有反馈过对方
        $fInfo = Db::name('feedback')->where(['uid'=>$uid,'bid'=>$bid])->find();
        $feed_status = 0;
        if(!empty($fInfo)){
            $feed_status = 1;
        }
        //看看有没有以前有没有看过
        $TelCollection = TelModel::telFind(['uid'=>$uid,'bid'=>$bid]);
        if(!empty($TelCollection)){
            if($TelCollection['type'] == 2 && $TelCollection['status'] == 0){
                TelModel::telEdit(['uid'=>$uid,'bid'=>$bid],['status'=>1]);
                $biduser = UserModel::userFind(['id'=>$bid]);
                //添加查看记录
                $params = [
                    'uid' => $uid,
                    'type' => 2,
                    'count' => 0,
                    'remarks' => '对方查看你,免费获得查看'.$biduser['nickname'].'手机号',
                    'create_at' => time()
                ];
                TelModel::tcountAdd($params);
            }
            $data = $this->TelChange($bid,1);
            $data['status'] = 1;
            $data['count'] = 1;
            $data['feed_status'] = $feed_status;
            return $this->successReturn($data,'成功',self::errcode_ok);
        }
        $children = ChildrenModel::childrenFind(['uid'=>$bid]);
        $userinfo = UserModel::userFind(['id'=>$uid]);

        //不是会员也没看过
        $data = $this->TelChange($bid,2);
        $data['status'] = 2;//2是看不了
        $data['count'] = $userinfo['count'];//剩余次数
        $data['feed_status'] = $feed_status;
        return $this->successReturn($data,'成功',self::errcode_ok);
    }

    /**
     * @Notes:查看手机号 查看者/被查看者添加记录
     * @Interface seeTelNew
     * @return string
     * @throws \think\Exception
     * @author: zy
     * @Time: 2021/08/25
     */
    public function seeTelNew()
    {
        $uid = $this->uid;
        $bid = input("bid"); //被查看者的id
        $lockInfo = lock('seetelnew_'.$uid);
        if($lockInfo == false){
            return $this->errorReturn(self::errcode_fail,'正在提交中,请勿频繁操作');
        }
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        $children = ChildrenModel::childrenFind(['uid'=>$bid]);
        if(empty($children)){
            return $this->errorReturn(self::errcode_fail,'无资料信息');
        }
        //判断是否是被查看者身份 是 可直接返回手机号
        $where_t['uid'] = $uid;
        $where_t['bid'] = $bid;
        $where_t['is_show'] = 0;
        $where_t['type'] = 2;
        $find = TelModel::telFind($where_t);
        if(!empty($find)){
             if($find['type'] == 2 && $find['status'] == 0){
                TelModel::telEdit(['uid'=>$uid,'bid'=>$bid],['status'=>1]);
             }
            $biduser = UserModel::userFind(['id'=>$bid]);
            //添加查看记录
            $params = [
                'uid' => $uid,
                'type' => 2,
                'count' => 0,
                'remarks' => '对方查看你,免费获得查看'.$biduser['nickname'].'手机号',
                'create_at' => time()
            ];
            TelModel::tcountAdd($params);
            $data['three'] = substr($children['phone'],0,3).' '.substr($children['phone'],3,4).' '.substr($children['phone'],7,4);
            $data['status'] = 1;//1是可以看
            return $this->successReturn(['data'=>$data,'count'=>0],'成功',self::errcode_ok);
        }
        //查看者是否有次数
        $userinfo = UserModel::userFind(['id'=>$uid]);
        //判断今日查看手机号次数
        $tel_count = DB::name('tel_count')->where(['uid'=>$uid,'type'=>2])->whereTime('create_at', 'today')->count();
        if($tel_count >= 5){
            return $this->errorReturn(self::errcode_fail,'今日次数已用光,明日再来');
        }
        if($userinfo['count'] > 0){
            $result = TelModel::shiwuDataNew($bid,$uid);
            if($result == true){
                $data['three'] = substr($children['phone'],0,3).' '.substr($children['phone'],3,4).' '.substr($children['phone'],7,4);
                $data['status'] = 1;//1是可以看
                //给被查看方发送短信通知  来访模板消息
                $this->sendMessage($uid,$bid);
                return $this->successReturn(['data'=>$data,'count'=>0],'成功',self::errcode_ok);
            }
            return $this->errorReturn(self::errcode_fail,'查看失败');
        }
        return $this->errorReturn(self::errcode_fail,'次数已经用光啦');
    }
    /**
     * @Notes: 查看详情被查看方未完善资料- 给对方发送短信通知/模板消息
     * @Interface sendMsg
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function sendMessageBid($uid,$bid){
        $messageCache = cache('message-'.$bid);
        if(empty($messageCache)){
            $userinfo = UserModel::userFind(['id'=>$bid]);
            $where_x['unionid'] = $userinfo['unionid'];
            $where_x['subscribe'] = 1;
            $mini_user = UserModel::wxFind($where_x);
            if($mini_user && $mini_user['status'] == 1){ //用户关注 非注销 发送模板消息
                $create_at = ChildrenModel::getchildrenField(['uid'=>$bid],'create_at');
                $nickname = !empty($userinfo['nickname'])?$userinfo['nickname']:'微信用户';
                $date = date('Y-m-d H:i:s',$create_at);
                $openid = $mini_user['openid'];
                $tip = '某家长刚查看了您的资料,但因为信息不全,又默默离开了~建议您尽快完善';
                $remark = '填写资料越详细，会有更多的人联系你哦';
                $temp_id = 'pFlGp3GAYyMdxQLoBXg18B-WLbW5hBA4GvqmsNZoIKo';
                $arr = array();
                $arr['first'] = array('value'=>$tip,'color'=>'#FF0000');
                $arr['keyword1'] = array('value'=>$nickname,'color'=>'#0000ff');
                $arr['keyword2'] = array('value'=>$date,'color'=>'#0000ff');
                $arr['remark'] = array('value'=>$remark,'color'=>'#0000ff');
                $param = [
                    'touser'=>$openid,
                    'template_id'=>$temp_id,
                    'page'=>'pages/editProfile/editProfile',
                    'data'=>$arr,
                    'miniprogram' => [
                        'pagepath'=>'pages/editProfile/editProfile',
                        'appid'=>'wx70d65d2170dbacd7',
                    ],
                ];
                $this->shiwuSendMsg($param);
                cache('message-'.$bid,$bid,24*3600);
            }else{
                $realname = !empty($userinfo['realname'])?$userinfo['realname']:'有位';
                //发送短信
                $b_phone = ChildrenModel::getchildrenField(['uid'=>$bid],'phone'); //收信人 手机号码
                $project_id = '4lSb84';//模板ID
                $vars = json_encode([
                    'realname' => $realname,
                    'url' => 'v1kj.cn/info'
                ]);
                $send = new SendService();
                $msgJson = $send->sendMsg($b_phone,$project_id,$vars);
//                custom_log('短信接收返回json',print_r($msgJson,true));
                $msgJson = json_decode($msgJson,true);
                if($msgJson['status'] == 'success'){
                    //添加发送记录
                    $arrMsg['uid'] = $uid;
                    $arrMsg['bid'] = $bid;
                    $arrMsg['remark'] = '用户'.$uid.'查看用户'.$bid.',的个人详情页,对方资料未完善';
                    $arrMsg['type'] = 1;
                    $arrMsg['create_time'] = date('Y-m-d H:i:s');
                    DB::name('send_message_record')->insertGetId($arrMsg);
                    cache('msgSend-',$uid);
                    cache('message-'.$bid,$bid,24*3600);
                }
            }
        }
    }
    /**
     * @Notes: 给被查看方发送短信通知  发送模板消息
     * @Interface sendMsg
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function sendMessage($uid,$bid){
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $realname = !empty($userinfo['realname'])?$userinfo['realname']:'有位';
        //发送短信
        $b_phone = ChildrenModel::getchildrenField(['uid'=>$bid],'phone'); //收信人 手机号码
        $project_id = 'pjjUb4';//模板ID
        $vars = json_encode([
            'realname' => $realname,
            'url' => 'v1kj.cn'
        ]);
        $send = new SendService();
        $msgJson = $send->sendMsg($b_phone,$project_id,$vars);
        custom_log('短信接收返回json',print_r($msgJson,true));
        $msgJson = json_decode($msgJson,true);
        if($msgJson['status'] == 'success'){
            //添加发送记录
            $arrMsg['uid'] = $uid;
            $arrMsg['bid'] = $bid;
            $arrMsg['remark'] = '用户'.$uid.'查看用户'.$bid.',给它发送短信成功';
            $arrMsg['create_time'] = date('Y-m-d H:i:s');
            cache('sendmsg-'.$bid,$uid);
            DB::name('send_message_record')->insertGetId($arrMsg);
        }
        //发送模板消息
        $unionid = UserModel::userValue(['id'=>$bid],'unionid');
        $where_x['unionid'] = $unionid;
        $where_x['subscribe'] = 1;
        $mini_user = UserModel::wxFind($where_x);
        if($mini_user && $mini_user['status'] == 1){ //用户关注 非注销 发送模板消息
            $u_phone = ChildrenModel::getchildrenField(['uid'=>$uid],'phone');
            $openid = $mini_user['openid'];
            $time = date('Y-m-d H:i');
            $tip = '有位家长付费解锁了您的联系方式，您可以免费查看对方';
            $name =  $userinfo['realname'].'家长';
            $phone = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $u_phone);
            $remark = '帮孩子找对象，首选完美亲家';
            $temp_id = 'aGiyIGwKmygDgnNWl9XGyIFNjSAOvau8Tr5RNjLlkkM';
            $arr = array();
            $arr['first'] = array('value'=>$tip,'color'=>'#FF0000');
            $arr['keyword1'] = array('value'=>$name,'color'=>'#0000ff');
            $arr['keyword2'] = array('value'=>$phone,'color'=>'#0000ff');
            $arr['keyword3'] = array('value'=>$time,'color'=>'#0000ff');
            $arr['remark'] = array('value'=>$remark,'color'=>'#0000ff');
            $param = [
                'touser'=>$openid,
                'template_id'=>$temp_id,
                'page'=>'pages/message/message?type=2',
                'data'=>$arr,
                'miniprogram' => [
                    'pagepath'=>'pages/message/message?type=2',
                    'appid'=>'wx70d65d2170dbacd7',
                ],
            ];
            $this->shiwuSendMsg($param);
        }
    }
    /**
     * @Notes:添加手机号发送验证码
     * @Interface checkTel
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function checkTel()
    {
        $tel = input("tel", '', 'htmlspecialchars_decode');
        if(empty($tel)){
            return $this->errorReturn(self::errcode_fail,'tel参数不能为空');
        }
        $checkphone  = preg_phone($tel);
        if(!$checkphone){
            return $this->errorReturn(self::errcode_fail,'手机号格式不正确');
        }
        $children =  ChildrenModel::childrenFind(['phone'=>$tel]);
        if(empty($children)){
            //没有 可以注册 发验证码
            $code = cache($tel);
            if(empty($code)){
                $code = rand(1000,9999);
                cache($tel,$code,300);
            }
            $data = sendTemplateSMS($tel,$code,'5分钟','969357');
            $data = get_object_vars(json_decode($data)); //stdclass 转化 数组
            if($data['statusCode'] == '000000'){
                return $this->successReturn('','验证码发送成功',self::errcode_ok);
            }
            return $this->errorReturn(self::errcode_fail,'短信次数超过5次,请换个手机号');
        }
        return $this->errorReturn(self::errcode_fail,'手机号已注册');
    }
    /**
     * @Notes:验证码校验 （注册/修改）
     * @Interface checkCode
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function checkCode()
    {
        // $uid = $this->uid;
        $type = input('type') ? : 2; //1修改手机号  2注册手机号
        $tel = input("tel", '', 'htmlspecialchars_decode');
        $code = input("code", '', 'htmlspecialchars_decode');
        if(empty($tel)){
            return $this->errorReturn(self::errcode_fail,'tel参数不能为空');
        }
        if(empty($code)){
            return $this->errorReturn(self::errcode_fail,'code参数不能为空');
        }
        $checkcode = cache($tel);
        if(empty($checkcode)){
            return $this->errorReturn(self::errcode_fail,'验证码过期');
        }
        if($type == 1){
            $session3rd = input('session3rd');
            $data = cache(config('wechat.miniapp.appid') . '_SESSION__'. $session3rd);
            $uid = $data['uid'];
            if(empty($uid)){
                return $this->errorReturn(self::errcode_fail,'session3rd参数不能为空');
            }
        }
        if($checkcode == $code){
            if($type == 1){ //验证通过 手机号存入数据库
                 $update['phone'] = $tel;
                 ChildrenModel::childrenEdit(['uid'=>$uid],$update);
            }
            return $this->successReturn('','成功',self::errcode_ok);
        }
        return $this->errorReturn(self::errcode_fail,'验证码错误');
    }
    /**
     * @Notes:获取视频列表
     * @Interface getVideoList
     * @return string
     * @author: zy
     * @Time:
     */
    public function getVideoList()
    {
        $map['is_del'] = 1;
        $map['is_online'] =1;
        $list = VideoModel::videoSelect($map,'id,img,title','id desc');
        return $this->successReturn($list,'成功',self::errcode_ok);
    }
    /**
     * @Notes:获取视频详情
     * @Interface getVideoInfo
     * @return string
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function getVideoInfo()
    {
        $id = input("id");
        if(!$id){
            return $this->errorReturn(self::errcode_fail,'id不能为空');
        }
        $map['is_del'] = 1;
        $map['is_online'] =1;
        $map['id'] = $id;
        $info = VideoModel::videoFind($map);
        if(!$info){
            return $this->errorReturn(self::errcode_fail,'视频不存在');
        }
        VideoModel::getvideoInt($map,'play_count',1);
        return $this->successReturn($info,'成功',self::errcode_ok);
    }
    /**
     * @Notes: 分享图海报  新版
     * @Interface shareInfoNew
     * @return string
     * @author: zy
     * @Time:
     */
    public function shareInfoNew()
    {
        $uid = $this->uid;
        $share_uid = input("uid"); //分享人的uid
        if(!$share_uid){
            return $this->errorReturn(self::errcode_fail,'分享人uid不能为空');
        }
        $url = cache('shareposter-'.$share_uid);
        if(empty($url)){
            $Poster = new Poster();
            $url = $Poster->index($share_uid);
        }
        $userinfo = UserModel::userFind(['id'=>$share_uid]);
        if(empty($userinfo['share_get_poster'])){
            UserModel::userEdit(['id'=>$share_uid],['share_get_poster'=>$url]);
        }
        if($uid == $share_uid){
            $data['text'] = "我的信息全在这，快帮忙介绍个对象吧";
        }else{
            $cInfo = ChildrenModel::childrenFind(['uid'=>$share_uid]);
            $year = $cInfo['year'];
            $sex = $cInfo['sex'] == 1 ?'男孩':'女孩';
            $data['text'] = "这个".$year."年".$sex."条件挺好的，适不适合你家孩子？";
        }
        $data['img'] = $url;
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
    /**
     * @Notes:获取常见问题
     * @Interface ques
     * @return string
     * @author: zy
     * @Time: 2021/07/26
     */
    public function ques()
    {
        $type = input("type", '', 'htmlspecialchars_decode');
        if (empty($type)) {
            return $this->errorReturn(self::errcode_fail,'type参数不能为空');
        }
        $res = Db::name('ques')->where(['id'=>$type])->field('content')->find();
        return $this->successReturn($res,'成功',self::errcode_ok);
    }
    /**
     * @Notes:生成二维码
     * @Interface erwma
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function erwma()
    {
        $uid = $this->uid;
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $data['nickname'] = !empty($userinfo['nickname'])?$userinfo['nickname']:'匿名';
        $data['headimgurl'] = !empty($userinfo['headimgurl'])?$userinfo['headimgurl']:'https://pics.njzec.com/default.png';
        if($userinfo['share_qrcode']){
            $data['share_qrcode'] = $userinfo['share_qrcode'];
            return $this->successReturn($data,'成功',self::errcode_ok);
        }
        $poster = new Poster();
        $path = './uploads/headImg';
        if(!is_dir($path)){
            mkdir($path,0700,true);
        }
        $head_name = 'head_img_'.$uid.'.png';
        $head_img_path = $path.'/'.$head_name;
        if (!file_exists($head_img_path)){
            $head_path = $poster->getImage($data['headimgurl'], $path, $head_name);
            $head_img_path = $head_path['save_path'];
        }
        $head_img_path = $poster->ssimg1($path.'/', $head_img_path, 80, 80);
        $sid = $uid;
        $path = './uploads/qrcode/';
        $page_path = 'pages/home/home';
        $share_back_path = './uploads/backgroud/backgroud.png';
        $header = [];
        $header['path'] = $head_img_path;
        $header['size'] = 150;
        $header['locate'] = [160,690];
        $header['xPos'] = 'left';

        $local_path =  (new Qrcode())->generateQrCode($path, $sid, $page_path);
        $qrcode['path'] = $local_path;
        $qrcode['size'] = 650;
        $qrcode['locate'] = [232,950];
        $qrcode['xPos'] = 'left';
        $qrcode['yPos'] = 'top';
        $images[0] = $header;
        $images[1] = $qrcode;

        if (mb_strlen($userinfo['realname']) == 0) {
            $name = $userinfo['nickname'];
        }else{
            $name = mb_substr($userinfo['realname'], 0,1 ).'家长';
        }
        $text_array[0]['location'] ='340,760';
        $text_array[0]['text'] = $name;
        $text_array[0]['font_size'] = 60;
        $text_array[0]['font_color'] = '#000';
        $text_array[1]['text'] =  '邀请您在完美亲家填写资料';
        $text_array[1]['location'] = '340,820';
        $text_array[1]['font_size'] = 30;
        $text_array[1]['font_color'] = '#ccc';
        $text_array[2]['location'] = '210,1690';
        $text_array[2]['text'] =  '长按识别小程序码 获取对象';
        $text_array[2]['font_size'] = 55;
        $text_array[2]['font_color'] = '#000';
        $posterModel = new PosterModel();
        $local_path = $posterModel->creates($uid,$share_back_path,$images,$text_array);

        $upload = new Upload();
        $img_url_data = $upload->index($local_path);//获取七牛图片
        $img_url_data = json_decode($img_url_data, 1);

        if ($img_url_data['code'] == 200) {
            unlink($local_path);
            UserModel::userEdit(['id'=>$uid],['share_qrcode'=>$img_url_data['img']]);
            $data['share_qrcode'] = $img_url_data['img'];
            return $this->successReturn($data,'成功',self::errcode_ok);
        } else {
            unlink($local_path);
            return $this->errorReturn(self::errcode_fail,'生成失败');
        }
    }

    /**
     * @Notes:记录已订阅数据
     * @Interface subRecord
     * @author: zy
     * @Time: 2021/08/05
     */
    public function subRecord(){
        $uid = $this->uid;
        $type = input('type'); //0静默未填写  1填写完资料
        $userInfo = UserModel::userFind(['id'=>$uid]);
        if(empty($userInfo)){
            return $this->errorReturn(self::errcode_fail,'数据异常,查无用户');
        }
        if($type == 1){
            UserModel::getuserInt(['id'=>$uid],'is_subscribe2',1);
            return $this->successReturn('','成功',self::errcode_ok);
        }else{
            UserModel::getuserInt(['id'=>$uid],'is_subscribe',1);
            return $this->successReturn('','成功',self::errcode_ok);

        }
        return $this->successReturn('','成功',self::errcode_ok);
    }
    /**
     * 查看手机号的 数据拼接
     * $bid被查看者的id
     *type 1是可看手机号 2是看不了手机号
     * @author LH
    */
    public function telChange($bid,$type)
    {
        $userinfo = UserModel::userFind(['id'=>$bid]);
        $children = ChildrenModel::childrenFind(['uid'=>$bid]);
        if($userinfo['realname']){
            $xing = mb_substr( $userinfo['realname'],0,1);
        }
        $user = [];
        $two = $children['sex']==1?'儿子':'女儿';
        if($children['year']){
            $two = $two.'/'.substr($children['year'],-2).'年' ;
        }
        $education = UsersService::education($children['education']);
        $two = $two.'/'.$education;
        if($children['residence']){
            $two = $two.'/现居'.$children['residence'];
        }
        $three = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);
        if($type == 1){
            $three = $children['phone'];
        }
        $user['first'] = isset($xing)?$xing.'家长':'家长';
        $user['two'] = $two;
        $user['three'] = substr($three,0,3).' '.substr($three,3,4).' '.substr($three,7,4);
        $user['four'] = !empty($userinfo['headimgurl'])?$userinfo['headimgurl']:'https://pics.njzec.com/default.png';
        return $user;
    }
    /**
     * @Notes:推荐更新通知 - 发送模板消息
     * @Interface send10Message
     * @return bool
     * @author: zy
     * @Time: 2021/08/09 10:00
     */
    public function sendTjMsg()
    {
        $count1 = 0;
        $count2 = 0;
        $where['u.status'] = 1;
//        $where['u.id'] = '479';
        $list = Db::table('userinfo')
            ->alias('u')
            ->where($where)
            ->join('wechat_fans c','u.unionid=c.unionid')
            ->field('u.openid as x_openid ,u.is_subscribe2,c.openid as w_openid,c.subscribe as subscribe,u.id as uid')
            ->select();
        foreach($list as $key => $value){
            if($value['subscribe'] == 1){ //关注公众号 发模板
                $tip = '今日推荐的12位相亲对象';
                $remark = '点击查看资料';
                $temp_id = 'yittRXCFWxzJSHJG6kWSCaed46Lr1JOdi_O-1lCvT2M';
                $data = array();
                $data['first'] = array('value'=>$tip,'color'=>'#FF0000');
                $data['keyword1'] = array('value'=>'完美亲家','color'=>'#0000ff');
                $data['keyword2'] = array('value'=>'同城相亲对象','color'=>'#0000ff');
                $data['remark'] = array('value'=>$remark,'color'=>'#0000ff');
                $param = [
                    'touser'=>$value['w_openid'],
                    'template_id'=>$temp_id,
                    'page'=>'pages/home/home',
                    'data'=>$data,
                    'miniprogram' => [
                        'pagepath'=>'pages/home/home',
                        'appid'=>'wx70d65d2170dbacd7',
                    ],
                ];

                $res1 = $this->shiwuSendMsg($param);
                if($res1 == true){
                    $count1++;
                }
            }else{
                //发送订阅模板(填写资料)
                if($value['is_subscribe2'] > 0){
                    $dy_data['number2'] = array('value' => "12",'color'=>'#0000ff');
                    $dy_data['time4'] = array('value' => "10:10",'color'=>'#0000ff');
                    $dy_temp_id = "1RFAByNMyfpaHKRtJT3GxKtDTfqwcfNA_741ss62OGs";
                    $param = [
                        'touser'=>$value['x_openid'],
                        'template_id'=>$dy_temp_id,
                        'page'=>'pages/home/home',
                        'data'=>$dy_data
                    ];
                    $res = $this->shiwuSendMsg($param,2);
                    if($res == true){
                        UserModel::getuserDec(['id'=>$value['uid']],'is_subscribe2',1);
                        $add['uid'] = $value['uid'];
                        $add['openid'] = $value['x_openid'];
                        $add['type'] = 2;
                        $add['create_time'] = date('Y-m-d H:i:s');
                        Db::name('send_record')->insertGetId($add);
                        $count2++;
                    }
                }
            }
        }
        $count = $count1 + $count2;
        echo $count;die;
    }

}
