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

namespace app\api\controller;
use app\api\model\TelCollection;
use app\api\model\Video as VideoModel;
use app\api\service\UsersService;
use app\api\service\RecommendService;
use app\api\service\Qrcode;
use app\api\service\Upload;
use app\api\model\Children as ChildrenModel;
use app\api\model\Collection as CollectionModel;
use app\api\model\TelCollection as TelModel;
use app\api\model\Product as ProductModel;
use app\api\model\User as UserModel;
use app\api\model\Team as TeamModel;
use app\api\model\Poster as PosterModel;
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
     * @Notes:首页推荐 登录情况下
     * @Interface home
     * @return string
     * @author: zy
     * @Time: 2021/07/23
     */
    public function home()
    {
        $uid = $this->uid;
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $is_vip = UsersService::isVip($userinfo);
        $source = input('source');
        // custom_log('邀请人','代理'.print_r($source,true));
        //判断资料是否完善
        $field_c = 'native_place,education,work,income,school,house,cart,expect_education,parents,bro,min_age,min_height';
        $cInfo = ChildrenModel::childrenFind(['uid'=>$uid],$field_c);
        $is_wechat = 0;
        $is_gz = UserModel::wxFind(['unionid'=>$userinfo['unionid']]);
        if($is_gz){
            $is_wechat = 1;
        }
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
        if(date('H') >= 10){ //剩余时间
            $temp_time = strtotime(date('Y-m-d').' 23:59:59')+10*3600;
            $date = date('Ymd');
        }else{
            $temp_time = strtotime(date('Y-m-d').'09:59:59');
            $date = date('Ymd',strtotime('-1 days'));
        }
        $todate = date('Ymd',strtotime($date)+24*3600);
        // dump($todate);exit;
        $last_time = $temp_time - time();
        //取用户需要的支付的类型 1:购买会员 2：购买次数
        $paytype = cache('paytypeuid-'.$uid);
        if(!$paytype){
            $paytype = $userinfo['paytype'];
            cache('paytypeuid-'.$userinfo['paytype'],$paytype,3*24*3600);
        }
        $field = "id,title,type,num,price,create_at,discount,old_price";
        $product = ProductModel::productSelect(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
        }
        //如果推荐购买会员
        //取昨天有没有今日推荐
        $map = [];
        $map['uid'] = $uid;
        $map['date'] = $date;
        $NewRecommend = new RecommendService();
        $tomorrow_exist = $NewRecommend->existTomorrowRecommend($map);
        $num = 17;
        if($tomorrow_exist){
            $num = 15;
        }
        //获取推荐列表
        $recommend = $NewRecommend->getRecommend($uid,$date,$num);
        $tomorrow_yes = db::name('tomorrow_recommend')->where($map)->select();
        //看明日推荐有没有数据
        $map = [];
        $map['uid'] = $uid;
        $map['date'] = $todate;
        $tomorrow_new = db::name('tomorrow_recommend')->where($map)->select();
        $len = count($recommend);
        //没有明日推荐 则从今日的数据中取出两条放到明日推荐里面
        if(!$tomorrow_new){
            $tomorrow_new = array();
            $tomorrow_new[0] = $recommend[$len-1];
            $tomorrow_new[1] = $recommend[$len-2];
            foreach($tomorrow_new as $key => $value){
                $data = array();
                $data['uid'] = $uid;
                $data['recommendid'] = $value['uid'];
                $data['date'] = $todate;
                $data['addtime'] = time();
                $data['is_match'] = $value['is_match'];
                db::name('tomorrow_recommend')->insert($data);
                //更新状态为明日推荐
                $map = array();
                $map['uid'] = $uid;
                $map['date'] = $date;
                $map['recommendid'] = $value['uid'];
                $data = array();
                $data['type'] = 2;
                db::name('recommend_record')->where($map)->update($data);
            }
        }else{
            $temp = array();
            foreach($tomorrow_new as $key => $value){
                $map = array();
                $map['uid'] = $value['recommendid'];
                $temp[$key] = ChildrenModel::childrenFind($map);
            }
            $tomorrow_new = $temp;
        }
        unset($recommend[$len-1]);
        unset($recommend[$len-2]);
        $tomorrow_arr = array();
        foreach($tomorrow_new as $key => $value){
            $temp = $this->userchange($value);
            $tomorrow_arr[$key]['headimgurl'] = $temp['headimgurl'];
            $tomorrow_arr[$key]['first'] = $temp['first'];
            $tomorrow_arr[$key]['remark'] = $temp['remark'];
            $tomorrow_arr[$key]['sex'] = $temp['sex'];
        }
        //f如果昨天有明日推荐则整合
        $temp_tomorrow = array();
        if($tomorrow_yes){
            foreach($tomorrow_yes as $key => $value){
                $map = array();
                $map['uid'] = $value['recommendid'];
                $temp_tomorrow[$key] = ChildrenModel::childrenFind($map);
            }
            $list  = array_merge($temp_tomorrow,$recommend);
        }else{
            $list = $recommend;
        }
        foreach($list as $key => $value){
            $list[$key] = $this->userchange($value);
        }
        foreach($list as $key => $value){
            $map = array();
            $map['uid'] = $uid;
            $map['bid'] = $value['uid'];
            $map['is_del'] = 1;
            $is_collection = CollectionModel::collectionFind($map);
            //1是未收藏 2是收藏了
            if(empty($is_collection)){
                $list[$key]['is_collection'] = 1;
            }else{
                $list[$key]['is_collection'] = 2;
            }
        }
        $children = ChildrenModel::childrenFind(['uid'=>$uid]);
        //如果是会员则不需要出支付的 直接返回十五个
        if($is_vip == 1){
            $data = array();
            $data['uid'] = $uid;
            $data['need_pay'] = 0;
            $data['list'] = $list;
            $data['paytype'] = $paytype;
            $data['tomorrow'] = $tomorrow_arr;
            $data['num'] = count($list);
            $data['last_time'] = $last_time;
            $data['self_sex'] = $children['sex'];
            $data['product'] = $product;
            $data['user_status'] = $userinfo['status'];
            $data['info_status'] = $info_status;
            $data['is_wechat'] = $is_wechat;
            return $this->successReturn($data,'成功',self::errcode_ok);
        }
        //如果不是会员 则获取支付类型
        if($paytype == 2){
            $len = count($list);
            //去掉后三个
            unset($list[$len-1]);
            unset($list[$len-2]);
            unset($list[$len-3]);
            //取出买次数的大数据
            $data = array();
            $data['uid'] = $uid;
            $data['need_pay'] = 1;
            $data['list'] = $list;
            $data['paytype'] = $paytype;
            $data['tomorrow'] = $tomorrow_arr;
            $data['num'] = count($list);
            $data['last_time'] = $last_time;
            $data['self_sex'] = $children['sex'];
            $data['product'] = $product;
            $data['user_status'] = $userinfo['status'];
            $data['info_status'] = $info_status;
            $data['is_wechat'] = $is_wechat;
            return $this->successReturn($data,'成功',self::errcode_ok);
        }
        //是会员
        $len = count($list);
        $pay_recommend = array();
        $pay_recommend[0] = $list[$len-3];
        $pay_recommend[1] = $list[$len-2];
        $pay_recommend[2] = $list[$len-1];
        foreach($pay_recommend as $key => $value){
            $map = array();
            $map['uid'] = $uid;
            $map['date'] = $date;
            $map['recommendid'] = $value['uid'];
            $data = array();
            $data['type'] = 3;
            db::name('recommend_record')->where($map)->update($data);
        }
        $len = count($list);
        //去掉后三个
        unset($list[$len-1]);
        unset($list[$len-2]);
        unset($list[$len-3]);
        $data = array();
        $data['uid'] = $uid;
        $data['need_pay'] = 1;
        $data['list'] = $list;
        $data['tomorrow'] = $tomorrow_arr;
        $data['paytype'] = $paytype;
        $data['pay_recommend'] = $pay_recommend;
        $data['num'] = count($list);
        $data['last_time'] = $last_time;
        $data['self_sex'] = $children['sex'];
        $data['product'] = $product;
        $data['user_status'] = $userinfo['status'];
        $data['info_status'] = $info_status;
        $data['is_wechat'] = $is_wechat;
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
        //根据资料完善情况，随机给用户推荐
        $page = input('param.page')?:1;
        $map['can_recommend'] = 1;
        $map['status'] = 1;
        $sta = $page*5-5;
        $field = 'id,uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro';
        $list = ChildrenModel::childrenSelectPage($map,$field,'',$sta,5);
        if(!$list){
            return $this->errorReturn(self::errcode_fail,'已经到底了，去完善信息我们给您推荐更合适的~~');
        }
        $user = [];
        foreach($list as $key => $value){
            $user[$key] = $this->userchange($value);
        }
        return $this->successReturn($user,'成功',self::errcode_ok);
    }
    /**
     * @Notes:相亲资料详情页
     * @Interface childrenDetails
     * @return string
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function childrenDetails()
    {
        $type = input('type'); //type类型 1未登录 2已登录
        if(empty($type)){
            return $this->errorReturn(self::errcode_fail,'type参数不能为空');
        }
        if($type == 2){
            $session3rd = input('session3rd');
            $data = cache(config('wechat.miniapp.appid') . '_SESSION__'. $session3rd);
            $uid = $data['uid'];
            if(empty($uid)){
                return $this->errorReturn(self::errcode_fail,'session3rd参数不能为空');
            }
        }
        $bid = input("bid"); //要查看的用户id
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        $children = ChildrenModel::childrenFind(['uid'=>$bid]);
        if(empty($children)){
            return $this->errorReturn(self::errcode_fail);
        }
        //子女信息
        $userinfo = UserModel::userFind(['id'=>$bid]);
        $children['realname'] = $userinfo['realname'];
        $children['headimgurl'] = $userinfo['headimgurl'];
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
        if($type == 1){ //未登录
            $children['is_collection'] = 1;
            $children['phone'] = '家长电话';
            $children['is_telcollection'] = 1;
            $children['is_me'] = 1;//不是自己
        }else{
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
        }
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
        //看看有没有以前有没有看过
        $TelCollection = TelModel::telFind(['uid'=>$uid,'bid'=>$bid]);
        if(!empty($TelCollection)){
            $data = $this->TelChange($bid,1);
            $data['status'] = 1;
            $data['count'] = 1;
            return $this->successReturn($data,'成功',self::errcode_ok);
        }
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $is_vip = UsersService::isVip($userinfo);
        //查看是不是会员
        if($is_vip == 1){
            //如果没有就添加记录
            if(empty($TelCollection)){
                $add['uid'] = $uid;
                $add['bid'] = $bid;
                $add['create_at'] = time();
                TelModel::telAdd($add);
            }
            $data = $this->TelChange($bid,1);
            $data['status'] = 1;
            $data['count'] = 1;
            return $this->successReturn($data,'成功',self::errcode_ok);
        }
        //不是会员也没看过
        $data = $this->TelChange($bid,2);
        $data['status'] = 2;//2是看不了
        $data['count'] = $userinfo['count'];//剩余次数
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
    /**
     * @Notes:查看手机号 且添加记录
     * @Interface seeTel
     * @return string
     * @throws \think\Exception
     * @author: zy
     * @Time: 2021/07/22
     */
    public function seeTel()
    {
        $uid = $this->uid;
        $bid = input("bid"); //被查看者的id
        $lockInfo = lock('seetel_'.$uid);
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
        $userinfo = UserModel::userFind(['id'=>$uid]);
        //有次数
        if($userinfo['count'] > 0){
            $result = TelModel::shiwuData($bid,$uid);
            if($result == true){
                $data['three'] = substr($children['phone'],0,3).' '.substr($children['phone'],3,4).' '.substr($children['phone'],7,4);
                $data['status'] = 1;//1是可以看
                return $this->successReturn(['data'=>$data,'count'=>0],'成功',self::errcode_ok);
            }
            return $this->errorReturn(self::errcode_fail,'查看失败');
        }
        return $this->errorReturn(self::errcode_fail,'次数已经用光啦');
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
     * @Notes: 分享图海报
     * @Interface shareInfo
     * @return string
     * @author: zy
     * @Time:
     */
    public function shareInfo()
    {
        $uid = input("uid"); //分享人的uid
        if(!$uid){
            return $this->errorReturn(self::errcode_fail,'分享人uid不能为空');
        }
        cache('shareposter-'.$uid, null);
        $url = cache('shareposter-'.$uid);
        if(!$url){
            $Poster = new Poster();
            $url = $Poster->index($uid);
            cache('shareposter-'.$uid,$url);
        }
        $data['text'] = "这位孩子条件不错，推荐您看看";
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
     * 用户数据转化成前端需要的样式
     * @author zy
    */
    private function userchange($value)
    {
        $education = UsersService::education($value['education']);//学历
        $income = UsersService::income($value['income']);//收入
        if($income){
            $income = '月收入'.$income;
        }
        $cart = UsersService::cart($value['cart']);//车
        $parents = UsersService::parents($value['parents']);//父母状况
        $bro = UsersService::bro($value['bro']);//子女情况
        if(isset($value['id'])){
            $user['id'] = $value['id'];
        }
        $user['uid']  = $value['uid'];
        $user['first']  = $value['sex']==1?'男':'女';
        if ($value['year']){
            $user['first'] = $user['first'].'·'.substr($value['year'],-2).'年('.getShuXiang($value['year']).')' ;
        }
        $work = $value['work'];
        if($value['work'] && $value['income']){
            $work = $value['work'].'·';
        }
        $four = '';
        if($value['hometown']){
            $four = '老家'.$value['hometown'].'·';
        }
        if($value['native_place']){
            $four = $four.$value['native_place'].'户口·';
        }
        if($value['residence']){
            $four = $four.'现居'.$value['residence'];
        }
        switch($value['house']){
            case 0:
                $five = '暂未填写';break;
            case 1:
                $five = '有房·';break;
            case 2:
                $five = '和父母住·';break;
            default:$five = '租房·';break;
        }
        $six = '';
        if ($value['bro']){
            $six = $bro;
            if(!empty($parents)){
                $six = $parents.'·'.$bro;
            }
        }
        $user['first'] = $user['first'].'·'.$education;
        $user['second']= !empty($value['school'])?$value['school']:'';
        $user['three'] = $work.$income;
        $user['four']  = $four;
        $user['five']  = $five.$cart;
        $user['six']   = $six;
        $user['remark'] = $value['remarks'];
        //查询用户父母的名称
        $pare = UserModel::userFind(['id'=>$value['uid']]);
        $user['realname'] = $pare['realname']?$pare['realname'].'家长':'家长';
        $user['headimgurl'] = $pare['headimgurl'];
        $user['user_sex'] = $pare['sex'];
        $user['user_status'] = $pare['status'];
        $user['sex'] = $value['sex'];

        return $user;
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
        $user['four'] = $userinfo['headimgurl'];
        return $user;
    }
}
