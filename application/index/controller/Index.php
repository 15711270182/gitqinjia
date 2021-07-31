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

namespace app\index\controller;

use think\Controller;
use JiaweiXS\WeApp\WeApp;
use app\wechat\service\WechatService;
use app\index\service\UsersService;
use service\ToolsService;
use think\Db;
use function Qiniu\json_decode;
use think\Queue;
use app\index\model\NewRecommend;

use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Index extends Controller
{   


    private static $appid;
    private static $secret;
    private static $grant_type;
    private static $url;
    private static $mch_id;
    private static $key;
    private $no_avatar;
    private static $token;
    private static $aes_key;

    public function __construct(){
        $this::$appid = 'wx70d65d2170dbacd7';
        $this::$secret = 'ddf67bffae1d48e78a9c8b74be25bd01';
        $this::$grant_type ='authorization_code';
        $this::$url = 'https://api.weixin.qq.com/sns/jscode2session';
        $this::$mch_id = '1610267514';
        $this::$key = 'CBDF911D317C03D8BA81EEFCF79F7AD3';
        
        $this::$token = 'weixin';
        $this->no_avatar = "http://small.ying-ji.com/understand/noheader.png";
        $this::$aes_key = '5b9c2ed3e19c40e5';
    }
    public function index()
    {
        $res = $this->sendConllectionMes(202,479);
        dump($res);exit;
        
    }




    /**
     * 用户收藏了发消息
     */
    public function sendConllectionMes($uid,$touser)
    {
        $map = array();
        $map['id'] = $touser;
        $touser = db::name('userinfo')->where($map)->find();
        $map['id'] = $uid;
        $senduser = db::name('userinfo')->where($map)->find();
        $map = array();
        $map['uid'] = $uid;
        $children = db::name('children')->where($map)->find();
        $map = array();
        $map['unionid'] = $touser['unionid'];
        $map['subscribe'] = 1;
        $mini_user = db::name('wechat_fans')->where($map)->find();
        if (!$mini_user || $mini_user['status'] == 0) // 如果用户注销,公众号不发送模板消息
        {
            return true;
        }
        //


        $openid = $mini_user['openid'];
        $time = date('Y-m-d H:i');
        $tip = '有位家长收藏了您孩子的相亲卡，试试联系ta吧';
        $name =  $senduser['realname'].'家长';
        $phone = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);
        $remark = '帮孩子找对象，首选完美亲家';
        $temp_id = 'aGiyIGwKmygDgnNWl9XGyIFNjSAOvau8Tr5RNjLlkkM'; 
        $data = array();
        $data['first'] = array('value'=>$tip,'color'=>'#FF0000');
        $data['keyword1'] = array('value'=>$name,'color'=>'#0000ff');
        $data['keyword2'] = array('value'=>$phone,'color'=>'#0000ff');
        $data['keyword3'] = array('value'=>$time,'color'=>'#0000ff');
        $data['remark'] = array('value'=>$remark,'color'=>'#0000ff');
        $arr = array();
        $arr['touser'] = $openid;
        $arr['template_id'] = 'aGiyIGwKmygDgnNWl9XGyIFNjSAOvau8Tr5RNjLlkkM';
        // "template_id": "cV1qqXzrXGV6tY3apOHznyyv3EK-EnA3G8aemU0KHTc",
        $arr['page'] = 'pages/message/message';
        $arr['data'] = $data;
        $arr["miniprogram"]["pagepath"] = "pages/message/message";
        $arr['miniprogram']['appid'] = 'wx70d65d2170dbacd7';
        $config = array();
        $config['appid'] = 'wx33665f6f8d16b7c1';
        $config['appsecret'] = '3148bd0bbda1b6aa7d084da6f698ac88';
        
        $temp = WechatService::WeChatTemplate($config);
        // dump($temp);exit;
        $res = $temp->send($arr);
        
        return true;
        
    }

    /**
     * 更新通知
     */
    public function send10Message()
    {
    
        $map = array();
        $map['subscribe'] = 1;
        $mini_user = db::name('wechat_fans')->where($map)->select();
        // dump($mini_user);exit;
        if (!$mini_user) 
        {
            return true;
        }
        //
        foreach ($mini_user as $key => $value) 
        {
             $time = date('Y-m-d H:i');
            $tip = '今日推荐的12位相亲对象';
            // $name =  $senduser['realname'].'家长';
            // $phone = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);
            $remark = '点击查看资料';

            $temp_id = 'yittRXCFWxzJSHJG6kWSCaed46Lr1JOdi_O-1lCvT2M'; 
            $data = array();
            $data['first'] = array('value'=>$tip,'color'=>'#FF0000');
            $data['keyword1'] = array('value'=>'完美亲家','color'=>'#0000ff');
            $data['keyword2'] = array('value'=>'同城相亲对象','color'=>'#0000ff');
            // $data['keyword3'] = array('value'=>$time,'color'=>'#0000ff');
            $data['remark'] = array('value'=>$remark,'color'=>'#0000ff');
            $arr = array();
            $arr['touser'] = $value['openid'];
            $arr['template_id'] = 'yittRXCFWxzJSHJG6kWSCaed46Lr1JOdi_O-1lCvT2M';
            // "template_id": "cV1qqXzrXGV6tY3apOHznyyv3EK-EnA3G8aemU0KHTc",
            $arr['page'] = 'pages/home/home';
            $arr['data'] = $data;
            $arr["miniprogram"]["pagepath"] = "pages/home/home";
            $arr['miniprogram']['appid'] = 'wx70d65d2170dbacd7';
            $config = array();
            $config['appid'] = 'wx33665f6f8d16b7c1';
            $config['appsecret'] = '3148bd0bbda1b6aa7d084da6f698ac88';
            
            $temp = WechatService::WeChatTemplate($config);
            // dump($temp);exit;
            $res = $temp->send($arr);
        }

        // $openid = $mini_user['openid'];
       
        
        return true;
        
    }

    /**
     * 微信授权
     * @author wzs
    */
     public function wxLogin() 
     {
        $code = input("code", '', 'htmlspecialchars_decode');
        $rawData = input("rawData", '', 'htmlspecialchars_decode');
        $signature = input("signature", '', 'htmlspecialchars_decode');
        $encryptedData = input("encryptedData", '', 'htmlspecialchars_decode');
        $iv = input("iv", '', 'htmlspecialchars_decode');
        /**
         * 4.server调用微信提供的jsoncode2session接口获取openid, session_key, 调用失败应给予客户端反馈
         * , 微信侧返回错误则可判断为恶意请求, 可以不返回. 微信文档链接
         * 这是一个 HTTP 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。其中 session_key 是对用户数据进行加密签名的密钥。
         * 为了自身应用安全，session_key 不应该在网络上传输。
         * 接口地址："https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code"
         */
        $params = [
            'appid' => $this::$appid,
            'secret' => $this::$secret,
            'js_code' => $code,
            'grant_type' => $this::$grant_type
        ];

        $res = makeRequest($this::$url, $params);
        $result = json_decode($res['result'],true);
        $session_key = $result['session_key'];
        $openid = $result['openid'];
        $unionid = isset($result['unionid'])?$result['unionid']:'';
        if (!$rawData) 
        {
            custom_log('unionid',$unionid);
            //静默授权
            $map = array();
            $map['openid'] = $openid;
            $is_have = db::name('userinfo')->where($map)->find();
            if (!$is_have) 
            {
                $data = array();
                $data['openid'] = $openid;
                $data['unionid'] = $unionid;
                $data['paytype'] = rand(1,2);
                $data['appid'] = $this::$appid;
                $data['add_time'] = time();
                $userid = db::name('userinfo')->insertGetId($data);
                $data = array();
                $data['code'] = -200;
                $data['msg'] = "该用户尚未注册";
                return json_encode($data);exit;

            }
            $map = arraY();
            $map['is_del'] = 1;
            $map['uid'] = $is_have['id'];
            $is_reg = db::name('children')->where($map)->find();
            if (!$is_reg) 
            {
                $data = array();
                $data['code'] = -200;
                $data['msg'] = "该用户尚未注册";
                return json_encode($data);exit;
            }

            $data = array();
            $data['uid'] = $is_have['id'];
            $data['openid'] = $openid;
            $data['unionid'] = $unionid;
            $data['session_key'] = $session_key;
            $session3rd = randomFromDev(16);
            cache(self::$appid.'_'.$session3rd, $data);
            $data = array();
            $data['session3rd']    = $session3rd;
            return json_encode($data);
        }
        $user_data = json_decode($rawData,true);
        //弹框授权
        $map = array();
        $map['openid'] = $openid;
        $is_have = db::name('userinfo')->where($map)->find();
        if ($is_have) 
        {
            $map = array();
            $map['id'] = $is_have['id'];
            $data = array(); 
            $data['nickname'] = ToolsService::emojiEncode($user_data['nickName']);
            $data['headimgurl'] = $user_data['avatarUrl'];
            $data['sex'] = $user_data['gender'];
            $data['country'] = $user_data['country'];
            $data['province'] = $user_data['province'];
            $data['city'] = $user_data['city'];
            $data['unionid'] = $unionid;
            // $data['paytype'] = rand(1,2);
            $res = db::name('userinfo')->where($map)->update($data);
            $data = array();
            $data['uid'] = $is_have['id'];
            $data['openid'] = $openid;
            $data['unionid'] = $unionid;
            $data['session_key'] = $session_key;
            $session3rd = randomFromDev(16);
            cache(self::$appid.'_'.$session3rd, $data);
            $data = array();
            $data['session3rd']    = $session3rd;
            return json_encode($data);
        }
        $data = array();
        $data['openid'] = $openid;
        $data['unionid'] = $unionid;
        $data['nickname'] = ToolsService::emojiEncode($user_data['nickName']);
        $data['headimgurl'] = $user_data['avatarUrl'];
        $data['sex'] = $user_data['gender'];
        $data['country'] = $user_data['country'];
        $data['province'] = $user_data['province'];
        $data['city'] = $user_data['city'];
        $data['appid'] = $this::$appid;
        $data['add_time'] = time();
        $data['paytype'] = rand(1,2);
        $userid = db::name('userinfo')->insertGetId($data);
        $data = array();
        $data['uid'] = $userid;
        $data['openid'] = $openid;
        $data['unionid'] = $unionid;
        $data['session_key'] = $session_key;
        $session3rd = randomFromDev(16);
        cache(self::$appid.'_'.$session3rd, $data);
        $data = array();
        $data['session3rd']    = $session3rd;
        return json_encode($data);
 
    }


    /**
     * 

     * 验证用户是否登陆
     * 2018年4月14日上午11:44:56 
     * liuxin 285018762@qq.com
     */
    public function checkLogin($session3rd)
    {
        $info = cache(self::$appid.'_'.$session3rd);
        if(empty($info)){
            return ['code'=>404,'msg'=>'用户登陆失效'];
        }
        return $info;
    }

    /**
     * 
     * 获取最近购买会员记录
     *wzs
     */
    public function getpayrecord()
    {
        $list = db::name('userinfo')->where('id','<=',100)->orderRaw('rand()')->field('nickname,headimgurl')->limit(5)->select();
        $data = array();
        $data['code'] = 200;
        $data['list'] = $list;
        return json_encode($data);exit;

    }





    /**
     * 
     * 验证用户是否登陆
     *wzs
     */
    public function checkLoginStatus()
    {
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if(!array_key_exists('openid', $info)||empty($info)){
            return json_encode(['code'=>404,'msg'=>'用户登陆失效,需重新登陆']);
        }
        return json_encode(['code'=>200,'msg'=>'登陆有效']);
    }

    /**
     * 首页没有登录的情况下取用户
     * @author wzs
    */
    public function getuserlist() 
    {
        //根据资料完善情况，随机给用户推荐
        $page = input('param.page')?:1;
        $map = array();
        $map['can_recommend'] = 1;
        $map['status'] = 1;
        $sta = $page*5-5;

        $list = db::name('children')->where($map)->field('uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro')->limit("$sta,5")->select();
        if (!$list) 
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = '已经到底了，去完善信息我们给您推荐更合适的~~';
            return json_encode($data);exit;
        }
        $user = array();
        foreach ($list as $key => $value) 
        {
            $user[$key] = $this->userchange($value);
        }
        $data = array();
        $data['code'] = 200;
        $data['data'] = $user;
        return json_encode($data);exit;

    }

    /**
     * 首页有登录的情况下拉取的 
     * @author wzs
    */
    public function test() 
    {
         $temp = rand(1,10);

            if ($temp<= 5) 
            {
                $paytype = 1;
            }else
            {   
                $paytype = 2;
            }
            cache('paytypeuid-'.$uid,$paytype,3*24*3600);
        $paytype = cache('paytypeuid-160');
        dump($paytype);exit;


    }


    /**
     * 获取用户支付类型
     * @author wzs
    */
    public function getPayType() 
    {
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        $paytype = cache('paytypeuid-'.$uid);
        if (!$paytype) 
        {
            $map = array();
            $map['id'] = $uid;
            $userinfo = db::name('userinfo')->where($map)->find();
            cache('paytypeuid-'.$userinfo['paytype'],$paytype,3*24*3600);
        }
        $data = array();
        $data['code'] = 200;
        $data['paytype'] = $paytype;
        return json_encode($data);exit;
    }


    /**
     * 首页有登录的情况下拉取的 
     * @author wzs
    */
    public function shareInfo() 
    {
        $uid = input("uid", '', 'htmlspecialchars_decode');
        if (!$uid)
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "参数不能为空！";
            return json_encode($data);exit;
        }
        cache('shareposter-'.$uid, null);
        $url = cache('shareposter-'.$uid);

        if (!$url)
        {
            $Poster = new Poster();
            $url = $Poster->index($uid);
            cache('shareposter-'.$uid,$url);
        }
        $data = array();
        $data['text'] = "这位孩子条件不错，推荐您看看";
        $data['img'] = $url;
        $data['code'] = 200;
        return json_encode($data);exit;
    }


    /**
     * 首页有登录的情况下拉取的 
     * @author wzs
    */
    public function homeold() 
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        //剩余时间 
        $temp_time = strtotime(date('Y-m-d').' 23:59:59');
        $last_time = $temp_time - time();
        // $uid = 1;
        //取出今天的推荐
        $list = '';
        // dump($list);exit;
        $is_vip = 0;//是否是会员
        //判断用户是否是会员
        $map = array();
        $map['id'] = $uid;
        $userinfo = db::name('userinfo')->where($map)->find();
        $map = array();
        $map['uid'] = $uid;
        $children = db::name('children')->where($map)->find();

        if ($userinfo['is_vip']== 1 && $userinfo['endtime']>= time()) 
        {
            //会员每天推荐15个
            $is_vip = 1;
        }
        if (!$list) 
        {
            //没有推荐的取出今天推荐的人数
            $num = 15;
            
            
            $data = model('Recommend')->getrecommend($uid,$num);
            $list = array();
            // dump($data);exit;
            foreach ($data as $key => $value) 
            {
                // dump($value);exit;
                $list[$key] = $this->userchange($value);
            }
            // cache('todayrecommend'.date('Ymd').'-'.$uid,$list,3600*24);
        }
        // dump($list);exit;
        //取banner
        $banner = cache('banner');
        if (!$banner) 
        {
            $map = array();
            $map['is_del'] = 1;
            $map['position'] = 1;
            $map['is_show'] = 1;
            $map['is_del'] = 1;
            $banner = db::name('banner')->where($map)->field('id,url,img_url')->order('sort desc')->select();
            cache('banner',$banner,3600*24);
        }
        //去该用户需要的支付的类型 1:购买会员 2：购买次数
        $paytype = cache('paytypeuid-'.$uid);
       $paytype = cache('paytypeuid-'.$uid);
        if (!$paytype) 
        {
            $map = array();
            $map['id'] = $uid;
            $userinfo = db::name('userinfo')->where($map)->find();
            $paytype = $userinfo['paytype'];
            cache('paytypeuid-'.$userinfo['paytype'],$paytype,3*24*3600);
        }
        //如果推荐购买会员

        //如何遇到更合适的详情对象
        $howto = array();
        $howto['is_show'] = 1;
        $howto['url'] = "3435355";
        //取明日推荐
        $tomorrow = cache('tomorrow-uid-'.$uid);
        // dump($tomorrow);exit;
        if (!$tomorrow) 
        {
            $tomorrow = model('Recommend')->gettomorrow($uid);
        }
        $tomorrow_arr = array();
        foreach ($tomorrow as $key => $value) 
        {
            $map = array();
            $map['uid'] = $value['recommendid'];
            $temp_user = db::name('children')->where($map)->find();
            $temp = $this->userchange($temp_user);

            $tomorrow_arr[$key]['headimgurl'] = $temp['headimgurl'];
            $tomorrow_arr[$key]['first'] = $temp['first'];
            $tomorrow_arr[$key]['remark'] = $temp['remark'];
            $tomorrow_arr[$key]['sex'] = $temp['sex'];
        }
        // dump($list);exit;
        foreach ($list as $key => $value) 
        {
            $map = array();
            $map['uid'] = $uid;
            $map['bid'] = $value['uid'];
            $map['is_del'] = 1;
            $is_collection = db::name('collection')->where($map)->find();
            //1是未收藏 2是收藏了
            if (empty($is_collection)) 
            {
                $list[$key]['is_collection'] = 1;
            }else
            {
                $list[$key]['is_collection'] = 2;
            }
        }
        //如果是会员则不需要出支付的 直接返回十五个 
        if ($is_vip == 1) 
        {
            $data = array();
            $data['code'] = 200;
            $data['banner'] = $banner;
            $data['need_pay'] = 0;
            $data['list'] = $list;
            $data['paytype'] = $paytype;
            $data['tomorrow'] = $tomorrow_arr;
            $data['num'] = count($list);
            $data['last_time'] = $last_time;
            $data['self_sex'] = $children['sex'];
            return json_encode($data);exit;
        }
        //如果不是会员 则获取支付类型 

        if ($paytype == 2) 
        {
            $len = count($list);
            //去掉后三个
            unset($list[$len-1]);
            unset($list[$len-2]);
            unset($list[$len-3]);
            //取出买次数的大数据
            $map = array();
            $map['is_del'] = 1;
            $map['is_show'] = 1;
            $map['type'] = 2;
            $pay = db::name('product')->where($map)->order('sort desc')->select();
            $data = array();
            $data['code'] = 200;
            $data['banner'] = $banner;
            $data['need_pay'] = 1;
            $data['list'] = $list;
            $data['paytype'] = $paytype;
            $data['tomorrow'] = $tomorrow_arr;
            $data['num'] = count($list);
            $data['last_time'] = $last_time;
            $data['self_sex'] = $children['sex'];
            return json_encode($data);exit;


        }
        $len = count($list);
        $pay_recommend = array();
        $pay_recommend[0] = $list[$len-3];
        $pay_recommend[1] = $list[$len-2];
        $pay_recommend[2] = $list[$len-1];
        $len = count($list);
        //去掉后三个
        unset($list[$len-1]);
        unset($list[$len-2]);
        unset($list[$len-3]);
        //取出买次数的大数据
        $map = array();
        $map['is_del'] = 1;
        $map['is_show'] = 1;
        $map['type'] = 1;
        $pay = db::name('product')->where($map)->order('sort desc')->select();
        $data = array();
        $data['code'] = 200;
        $data['banner'] = $banner;
        $data['need_pay'] = 1;
        $data['list'] = $list;
        $data['tomorrow'] = $tomorrow_arr;
        $data['paytype'] = $paytype;
        $data['pay_recommend'] = $pay_recommend;
        $data['num'] = count($list);
        $data['last_time'] = $last_time;
        $data['self_sex'] = $children['sex'];
        return json_encode($data);exit;
    }


     /**
     * 首页有登录的情况下拉取的 
     * @author wzs
    */
    public function home() 
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];

        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        $is_vip = 0;//是否是会员
        //判断用户是否是会员
        $map = array();
        $map['id'] = $uid;
        $userinfo = db::name('userinfo')->where($map)->find();
        $map = array();
        $map['uid'] = $uid;
        $children = db::name('children')->where($map)->find();

        if ($userinfo['is_vip']== 1 && $userinfo['endtime']>= time()) 
        {
            //会员每天推荐15个
            $is_vip = 1;
        }
        $h = date('H');

        //剩余时间 
        if ($h >= 10) 
        {
            $temp_time = strtotime(date('Y-m-d').' 23:59:59')+10*3600;
            $date = date('Ymd');
        }else
        {
            $temp_time = strtotime(date('Y-m-d').'09:59:59');
            $date = date('Ymd',strtotime('-1 days'));
            
        }
        $todate = date('Ymd',strtotime($date)+24*3600);
        // dump($todate);exit;
        $last_time = $temp_time - time();
        //取出今天banner
        $list = '';
        $banner = cache('banner');
        if (!$banner) 
        {
            $map = array();
            $map['is_del'] = 1;
            $map['position'] = 1;
            $map['is_show'] = 1;
            $map['is_del'] = 1;
            $banner = db::name('banner')->where($map)->field('id,url,img_url')->order('sort desc')->select();
            cache('banner',$banner,3600*24);
        }
        //去该用户需要的支付的类型 1:购买会员 2：购买次数
        $paytype = cache('paytypeuid-'.$uid);
        // $paytype = cache('paytypeuid-'.$uid);
        if (!$paytype) 
        {

            $paytype = $userinfo['paytype'];
            cache('paytypeuid-'.$userinfo['paytype'],$paytype,3*24*3600);
        }
        $product = Db::name('Product')->where(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'])->field('id,title,type,num,price,create_at,discount,old_price')->order('sort desc')->select();
         foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
        }
        //如果推荐购买会员

        //如何遇到更合适的详情对象
        $howto = array();
        $howto['is_show'] = 1;
        $howto['url'] = "3435355";
        //取昨天有没有今日推荐
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = $date;
        $tomorrow_yes = array();
        $NewRecommend = new NewRecommend();
        $tomorrow_exist = $NewRecommend->existTomorrowRecommend($map);
        $num = 17;
        if ($tomorrow_exist)
        {
            $num = 15;
        }
        //获取推荐列表
       // $recommend = model('Recommend')->getrecommendnew($uid,$date,$num);
        $recommend = $NewRecommend->getRecommend($uid,$date,$num);
        $tomorrow_yes = db::name('tomorrow_recommend')->where($map)->select();
        //看明日推荐有没有数据
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = $todate;
        $tomorrow_new = db::name('tomorrow_recommend')->where($map)->select();
        $len = count($recommend);
        //没有明日推荐 则从今日的数据中取出两条放到明日推荐里面
        if (!$tomorrow_new) 
        {
            $tomorrow_new = array();
            $tomorrow_new[0] = $recommend[$len-1];
            $tomorrow_new[1] = $recommend[$len-2];
            
            // dump($tomorrow_new);exit;
            foreach ($tomorrow_new as $key => $value) 
            {
                // dump($value);exit;
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

            
        }else
        {
            $temp = array();
            foreach ($tomorrow_new as $key => $value) 
            {
                $map = array();
                $map['uid'] = $value['recommendid'];
                $temp[$key] = db::name('children')->where($map)->find();
            }
            
            $tomorrow_new = $temp;

        }
        unset($recommend[$len-1]);
            unset($recommend[$len-2]);
        //

        $tomorrow_arr = array();
        foreach ($tomorrow_new as $key => $value) 
        {
            // $map = array();
            // $map['uid'] = $value['recommendid'];
            // $temp_user = db::name('children')->where($map)->find();
            $temp = $this->userchange($value);

            $tomorrow_arr[$key]['headimgurl'] = $temp['headimgurl'];
            $tomorrow_arr[$key]['first'] = $temp['first'];
            $tomorrow_arr[$key]['remark'] = $temp['remark'];
            $tomorrow_arr[$key]['sex'] = $temp['sex'];
        }
        //f如果昨天有明日推荐则整合
        $temp_tomorrow = array();
        if ($tomorrow_yes) 
        {
            foreach ($tomorrow_yes as $key => $value) 
            {
                $map = array();
                $map['uid'] = $value['recommendid'];
                $temp_tomorrow[$key] = db::name('children')->where($map)->find();
            }
            $list  = array_merge($temp_tomorrow,$recommend);
        }else
        {
            $list = $recommend;
        }
        // dump($list);exit;
        
        // dump($list);exit;
        // dump($tomorrow_arr);exit;
        foreach ($list as $key => $value) 
        {
            $list[$key] = $this->userchange($value);
        }
        foreach ($list as $key => $value) 
        {
            $map = array();
            $map['uid'] = $uid;
            $map['bid'] = $value['uid'];
            $map['is_del'] = 1;
            $is_collection = db::name('collection')->where($map)->find();
            //1是未收藏 2是收藏了
            if (empty($is_collection)) 
            {
                $list[$key]['is_collection'] = 1;
            }else
            {
                $list[$key]['is_collection'] = 2;
            }
        }
        // foreach ($tomorrow_arr as $key => $value) 
        // {
        //     $tomorrow_arr[$key] = $this->userchange($value);
        // }
        //如果是会员则不需要出支付的 直接返回十五个 
        if ($is_vip == 1) 
        {

            $data = array();
            $data['code'] = 200;
            $data['uid'] = $uid;
            $data['banner'] = $banner;
            $data['need_pay'] = 0;
            $data['list'] = $list;
            $data['paytype'] = $paytype;
            $data['tomorrow'] = $tomorrow_arr;
            $data['num'] = count($list);
            $data['last_time'] = $last_time;
            $data['self_sex'] = $children['sex'];
            $data['product'] = $product;
            $data['user_status'] = $userinfo['status'];
            return json_encode($data);exit;
        }
        //如果不是会员 则获取支付类型 

        if ($paytype == 2) 
        {
            $len = count($list);
            //去掉后三个
            unset($list[$len-1]);
            unset($list[$len-2]);
            unset($list[$len-3]);
            //取出买次数的大数据
            // $map = array();
            // $map['is_del'] = 1;
            // $map['is_show'] = 1;
            // $map['type'] = 2;
            // $pay = db::name('product')->where($map)->order('sort desc')->select();
            $data = array();
            $data['uid'] = $uid;
            $data['code'] = 200;
            $data['banner'] = $banner;
            $data['need_pay'] = 1;
            $data['list'] = $list;
            $data['paytype'] = $paytype;
            $data['tomorrow'] = $tomorrow_arr;
            $data['num'] = count($list);
            $data['last_time'] = $last_time;
            $data['self_sex'] = $children['sex'];
            $data['product'] = $product;
            $data['user_status'] = $userinfo['status'];
            return json_encode($data);exit;


        }
        //是会员
        $len = count($list);
        $pay_recommend = array();
        $pay_recommend[0] = $list[$len-3];
        $pay_recommend[1] = $list[$len-2];
        $pay_recommend[2] = $list[$len-1];
        // dump($)
        foreach ($pay_recommend as $key => $value) 
        {
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
        // //取出买次数的大数据
        // $map = array();
        // $map['is_del'] = 1;
        // $map['is_show'] = 1;
        // $map['type'] = 1;
        // $pay = db::name('product')->where($map)->order('sort desc')->select();
        $data = array();
        $data['uid'] = $uid;
        $data['code'] = 200;
        $data['banner'] = $banner;
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
        return json_encode($data);exit;
    }


    /**
     * 用户数据转化成前端需要的样式
     * @author wzs
    */
    private function userchange($value) 
    {
        $user['uid']  = $value['uid'];
        $user['first'] = $value['sex']==1?'男':'女';
        if ($value['year']) 
        {
            $user['first'] = $user['first'].'·'.substr($value['year'],-2).'年('.$this->getShuXiang($value['year']).')' ;
        }
        // if ($value['height']) 
        // {
        //     $user['first'] = $user['first'].'·高'.$value['height'];
        // }
        
        switch ($value['education']) {
            case '1':
                $user['first'] = $user['first'].'·中专及以下';
                break;
            case '2':
                $user['first'] = $user['first'].'·高中';
                break;
            case '3':
                $user['first'] = $user['first'].'·大专';
                break;
            case '4':
                $user['first'] = $user['first'].'·本科';
                break;
            case '5':
                $user['first'] = $user['first'].'·研究生';
                break;
            case '6':
                $user['first'] = $user['first'].'·博士';
                break;
            default:
                $user['first'] = $user['first'].'';
                break;
        }
        $user['second'] = '';
        if ($value['school']) 
        {
            $user['second'] = $value['school'];
        }
         if($value['work'] && $value['income'])
        {
            $user['three'] = $value['work'].'·';
        }else{
            $user['three'] = $value['work'];
        }
        if ($value['income']) 
        {
            switch ($value['income']) {
                case '1':
                    $user['three'] = $user['three'].'月收入5000以下'; 
                    break;
                case '2':
                    $user['three'] = $user['three'].'月收入5000-8000';

                    break;
                case '3':
                    $user['three'] = $user['three'].'月收入8000-12000';
                    break;
                case '4':
                    $user['three'] = $user['three'].'月收入12000-20000';
                    break;
                case '5':
                    $user['three'] = $user['three'].'月收入20000-30000';
                    break;
                case '6':
                    $user['three'] = $user['three'].'月收入30000以上';
                    break;
                
                default:
                    $user['three'] = $user['three'].'月收入暂未填写';
                    break;
            }
        }
        $user['four'] = '';
        if ($value['hometown']) 
        {
            $user['four'] = '老家'.$value['hometown'].'.';
        }
        if ($value['native_place']) 
        {
            $user['four'] = $user['four'].$value['native_place'].'户口.';
        }
        if ($value['residence']) 
        {
            $user['four'] = $user['four'].'现居'.$value['residence'];
        }
        $user['five'] = '';
        if ($value['house'] == 0) {
            $user['five'] = '暂未填写';
            if($value['cart'] == 0){
                $user['five'] = $user['five'].'·暂未填写';
            }elseif ($value['cart'] == 1)
            {
                $user['five'] = $user['five'].'·有车';
            }elseif ($value['cart'] == 2)
            {
                $user['five'] = $user['five'].'·近期购车';
            }else
            {
                $user['five'] = $user['five'].'·暂无车';
            }
        }elseif ($value['house'] == 1)
        {
            $user['five'] = '有房';
            if($value['cart'] == 0){
                $user['five'] = $user['five'].'·暂未填写';
            }elseif ($value['cart'] == 1)
            {
                $user['five'] = $user['five'].'·有车';
            }elseif ($value['cart'] == 2) 
            {
                $user['five'] = $user['five'].'·近期购车';
            }else
            {
                $user['five'] = $user['five'].'·暂无车';
            }

        }elseif ($value['house'] == 2)  
        {
            $user['five'] = '和父母住';
            if($value['cart'] == 0){

                $user['five'] = $user['five'].'·暂未填写';
            }elseif ($value['cart'] == 1)
            {
                $user['five'] = $user['five'].'·有车';
            }elseif ($value['cart'] == 2) 
            {
                $user['five'] = $user['five'].'·近期购车';
            }else
            {
                $user['five'] = $user['five'].'·暂无车';
            }

        }else{
            $user['five'] = '租房';

            if($value['cart'] == 0){
                $user['five'] = $user['five'].'·暂未填写';
            }elseif ($value['cart'] == 1)
            {
                $user['five'] = $user['five'].'·有车';
            }elseif ($value['cart'] == 2) 
            {
                $user['five'] = $user['five'].'·近期购车';
            }else
            {
                $user['five'] = $user['five'].'·暂无车';
            }
        }  
        $user['six'] = '';
        if ($value['parents']) 
        {
            switch ($value['parents']) {
                case '1':
                    $user['six'] = '父母健在';
                    break;
                case '2':
                    $user['six'] = '单亲家庭';
                    break;
                case '3':
                    $user['six'] = '父亲健在';
                    break;
                case '4':
                    $user['six'] = '母亲健在';
                    break;
                default:
                    $user['six'] = '';
                    break;
            }
        } 
        if ($value['bro']) 
        {
            if ($user['six']) 
            {
                switch ($value['bro']) {
                    case '1':
                        $user['six'] = $user['six'].'·独生子女';
                        break;
                    case '2':
                        $user['six'] = $user['six'].'·老大';
                        break;
                    case '3':
                        $user['six'] = $user['six'].'·老二';
                        break;
                    case '4':
                        $user['six'] = $user['six'].'·老三';
                        break;
                    default:
                        $user['six'] = $user['six'].'·暂未填写';
                        break;

                } 
            }else
            {
                switch ($value['bro']) {
                    case '1':
                        $user['six'] = $user['six'].'独生子女';
                        break;
                    case '2':
                        $user['six'] = $user['six'].'老大';
                        break;
                    case '3':
                        $user['six'] = $user['six'].'老二';
                        break;
                    case '4':
                        $user['six'] = $user['six'].'老三';
                        break;
                    default:
                        $user['six'] = $user['six'].'暂未填写';
                        break;

                } 
            }
        }
        
        
        
        $user['remark'] = $value['remarks'];
        //查询用户父母的名称
        $map = array();
        $map['id'] = $value['uid'];
        $pare = db::name('userinfo')->where($map)->find();
        $user['realname'] = $pare['realname']?$pare['realname'].'家长':'家长';
        $user['headimgurl'] = $pare['headimgurl'];
        $user['user_sex'] = $pare['sex'];
        $user['user_status'] = $pare['status'];
        $user['sex'] = $value['sex'];
        return $user; 
    }

     /**
     * 根据年龄获取属相
     * @author wzs
    */
    public function getShuXiang($year) 
    {
        // $year = 1990;

        $data = array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪');
 
        $index = ($year-1900)%12;

        return $data[$index];

    }



     /**
     * 生成订单
     * @author wzs
    */
    public function makeorder() 
    {
         //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        // $uid = 1;
        $type = input("type", '', 'htmlspecialchars_decode');

        $order_num = 'xthl_' . time() . createRandStr(8);

        $map = array();
        $map['id'] = $type;
        $product = db::name('product')->where($map)->find();
        if (!$product) 
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "商品不存在!";
            return json_encode($data);exit;
        }
        $data = array();
        $data['order_number'] = $order_num;
        $data['uid'] = $uid;
        $data['goods_id'] = $type;
        $data['payment'] = $product['price'];
        $data['create_at'] = time();
        $data['pay_time'] = time();
        $orderres = db::name('order')->insertGetId($data);
        if (!$orderres) 
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "订单生成失败!";
            return json_encode($data);exit;
        }
        $map = array();
        $map['id'] = $uid;
        $userinfo = db::name('userinfo')->where($map)->find();
        $openid = $userinfo['openid'];
            #
        $weapp = new WeApp($this::$appid, $this::$secret, './little/' . $this::$appid . '/');
        $payObj = $weapp->getPayObj(self::$mch_id, self::$key);
       // $notify_url = 'https://safe.ailife.net.cn/index/Answerplus/orderNotify';
        $notify_url = 'https://qin.njzec.com/index/api/orderNotify';
        
        $result = $payObj->pay($order_num, $data['payment'], $notify_url, $openid, '如有问题，请进入喜糖婚恋小程序联系客服给您处理！');
        if ($result) 
        {
            $data = array();
            $data['code'] = 200;
            $data['msg'] = '生成订单成功';
            $data['pay'] = $result;
            return json_encode($data);
        } else {
            $data = array();
            $data['code'] = - 200;
            $data['msg'] = '生成订单失败';
            return json_encode($data);
        }
    }


    /**
     * 我收藏的 
     * @author LH
    */

    public function meCollection()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        //获取参数 uid 

        $collection = Db::name('Collection')->where(['uid'=>$uid,'is_del'=>'1'])->order('create_at desc')->select();
        if(empty($collection)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }

        foreach ($collection as $key => $value) {
            
           
            //被收藏用户子女资料
            $Childr = Db::name('Children')->where(['uid'=>$value['bid']])->field('uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro')->find();

            //userchagnge 数据转化
            $collection[$key] = $this->userchange($Childr);

            //时间转化 
            if(time() - $value['create_at']<172800){
                //获取今天00:00
                $todaystart = strtotime(date('Y-m-d'.'00:00:00',time()));
                if($value['create_at']<$todaystart){
                    $collection[$key]['create_time'] = '昨天';
                }else{
                    $collection[$key]['create_time'] = '今天';
                }
            }else{
                $collection[$key]['create_time'] = date("m月d日",$value['create_at']);
            }

            $is_collection = Db::name('Collection')->where(['uid'=>$uid,'bid'=>$value['bid'],'is_del'=>'1'])->find();
            if(empty($is_collection))
            {
                $collection[$key]['is_collection'] = 1;
            }else{
                $collection[$key]['is_collection'] = 2;
            }



        }

        return json_encode(['code' => 200, 'data' =>$collection]);

    }




    /**
     * 收藏我的 
     * @author LH
    */

    public function collectionMe()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        //获取参数 uid 

        $collection = Db::name('Collection')->where(['bid'=>$uid,'is_del'=>'1'])->order('create_at desc')->select();
        if(empty($collection)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }

        foreach ($collection as $key => $value) {
            
            //收藏我的用户子女资料
            $Childr = Db::name('Children')->where(['uid'=>$value['uid']])->field('uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro')->find();

            //userchagnge 数据转化
            $collection[$key] = $this->userchange($Childr);

             //时间转化 
            if(time() - $value['create_at']<172800){
                //获取今天00:00
                $todaystart = strtotime(date('Y-m-d'.'00:00:00',time()));
                if($value['create_at']<$todaystart){
                    $collection[$key]['create_time'] = '昨天';
                }else{
                    $collection[$key]['create_time'] = '今天';
                }
                
            }else{
                $collection[$key]['create_time'] = date("m月d日",$value['create_at']);
            }

            //看看对方我有么有收藏对方 1是没有 2是有

            $is_collection = Db::name('Collection')->where(['uid'=>$uid,'bid'=>$value['uid'],'is_del'=>'1'])->find();
            if(empty($is_collection))
            {
                $collection[$key]['is_collection'] = 1;
            }else{
                $collection[$key]['is_collection'] = 2;
            }

            

        }
        return json_encode(['code' => 200, 'data' =>$collection]);
    }



    /**
     * 联系人 
     * @author LH
    */

    public function contacts()
    {
         
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }


        $collection = Db::name('TelCollection')->where(['uid'=>$uid,'is_del'=>'1'])->order('create_at desc')->select();
        if(empty($collection)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }

        foreach ($collection as $key => $value) {
            
            //被收藏用户子女资料
            $Childr = Db::name('Children')->where(['uid'=>$value['bid']])->field('uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro')->find();

            $collection[$key] = $this->userchange($Childr);
            //时间转化 
            if(time() - $value['create_at']<172800){
                 //获取今天00:00
                $todaystart = strtotime(date('Y-m-d'.'00:00:00',time()));
                if($value['create_at']<$todaystart){
                    $collection[$key]['create_time'] = '昨天';
                }else{
                    $collection[$key]['create_time'] = '今天';
                }
            }else{
                $collection[$key]['create_time'] = date("m月d日",$value['create_at']);
            }

            //看看对方我有么有收藏对方 1是没有 2是有

            $is_collection = Db::name('Collection')->where(['uid'=>$uid,'bid'=>$value['bid'],'is_del'=>'1'])->find();
            if(empty($is_collection))
            {
                $collection[$key]['is_collection'] = 1;
            }else{
                $collection[$key]['is_collection'] = 2;
            }
           


        }
        return json_encode(['code' => 200, 'data' =>$collection]);


    }

    /**
     * 用户信息和子女资料 
     * @author LH
    */

    public function meInformation()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        // return json_encode(['code' => 404, 'data' => $session3rd]);


        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        //获取参数 uid 

        $children = Db::name('Children')->where(['uid'=>$uid])->field('uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro')->find();

        if(empty($children)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }
        //数据转化
        $data = $this->userchange($children);

        $userinfo = Db::name('userinfo')->where(['id'=>$uid])->find();
        if ($userinfo['is_vip'] == 1 && $userinfo['endtime'] >= time()) 
        {
            $data['is_vip'] = 1;
        }else
        {
            $data['is_vip'] = 0;
        }
        $last_num = $userinfo['count']?$userinfo['count']:0;
        $map = array();
        // $map['is_del'] = 1;
        $map['uid'] = $uid;
        $map['status'] = 1;
        $money = db::name('order')->where($map)->sum('payment');
        $data['pay_money'] = $money/100;

        return json_encode(['code' => 200, 'data' =>$data,'last_num'=>$last_num,'wechat_url'=>'http://mp.weixin.qq.com/s?__biz=Mzg3ODYzMjk5OA==&mid=100000006&idx=1&sn=085429b461d09aa0f663db416f363230&chksm=4f11884f786601595841cbdde10c0f56a0aaf384f08c8db9e258cba3e21bbe9af176faa7fe97#rd']);
    }

    /**
     * product 列表
     * @author LH
    */
    public function productList()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        $paytype = input("paytype", '', 'htmlspecialchars_decode');
        if(empty($paytype))
        {
            //去该用户需要的支付的类型 1:会员 2：购买次数
            $paytype = cache('paytypeuid-'.$uid);
            if (!$paytype) 
            {
                $temp = rand(1,10);
                if ($temp<= 5) 
                {
                    $paytype = 1;
                }else
                {   
                    $paytype = 2;
                }
                cache('paytypeuid-'.$uid,$paytype,3*24*3600);
            }
        }
        
        //is_show = 1 is_del=1
        $product = Db::name('Product')->where(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'])->field('id,title,type,num,price,create_at,discount,old_price')->order('sort desc')->select();
        if(empty($product)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }
        // 折算到每天是多少钱
        foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
        }
        $map = array();
        $map['uid'] = $uid;
        $children = db::name('children')->where($map)->find();
        $sex = $children['sex'];
        return json_encode(['code' => 200, 'data' =>$product,'sex'=>$sex]);
    }


    /**
     * 子女资料修改 //第一次完善的加分
     * @author LH
    */
    public function childrenEdit()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $field = input("field", '', 'htmlspecialchars_decode');//要修改的字段 field 
        $value = input("values", '', 'htmlspecialchars_decode');   //对应的值

        if(empty($field)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        if(empty($value)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }
        //0过不了 empty 的判断 
        if($value == '/')
        {
            $value = 0;
        }

        $res = Db::name('Children')->where(['uid'=>$uid])->find();
        //第一次完善加分  1 school 学校 2 hometown 家乡 3 native_place 户籍 5 work 职业 6 house 房子 7 cart 车子

        if($field == 'school' and empty($res[$field])){   $type = 1; }

        if($field == 'hometown' and empty($res[$field])){   $type = 2; }

        if($field == 'native_place' and empty($res[$field])){   $type = 3; }

        if($field == 'work' and empty($res[$field])){   $type = 5; }

        if($field == 'house' and $res[$field] == 0){   $type = 6; }

        if($field == 'cart' and $res[$field] == 0){   $type = 7; }

        if (!empty($type)) {
            userscore($uid,$type);
        }





        //修改的数据
        $update[$field] = $value;
        
      
        if($field == 'realname'){
            //修改用户真实姓名
            $userinfo = Db::name('Userinfo')->where(['id'=>$uid])->find();

            if (empty($userinfo)){
                return json_encode(['code' => -200, 'msg' => '暂无数据']);
            }

            $res = Db::name('Userinfo')->where(['id'=>$uid])->update($update);

            if($res){
                return json_encode(['code' => 200, 'msg' => '修改成功']);
            }else{
                return json_encode(['code' => 200, 'msg' => '请勿重复修改']);
            } 

        }else{

           //修改子女资料表
        
            $children = Db::name('Children')->where(['uid'=>$uid])->find();

            if (empty($children)){
                return json_encode(['code' => -200, 'msg' => '暂无数据']);
            }

            $res = Db::name('Children')->where(['uid'=>$uid])->update($update);

            if($res){
                return json_encode(['code' => 200, 'msg' => '修改成功']);
            }else{
                return json_encode(['code' => 200, 'msg' => '请勿重复修改']);
            } 
        }
    }

    /**
     * 收藏与取消收藏
     * @author LH
    */

    public function collection()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $bid = input("bid", '', '');//要收藏或取消收藏id的用户id
        $type  = input("type", '', '');//1是收藏 2是取消收藏

        if(empty($bid)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }
        if(empty($type)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        if($type == 1){
            $collection = Db::name('Collection')->where(['uid'=>$uid,'bid'=>$bid])->find();
            $b_user_info = Db::name('userinfo')->where('id', $bid)->find();
            if ($b_user_info['status'] == 0) return json_encode(['code'=>-200, 'msg'=> '该用户已经注销!']);
            //如果没有就添加收藏
            if(empty($collection))
            {
                $this->sendConllectionMes($uid,$bid);
                $params = array();
                $params = [
                    'uid' => $uid,
                    'bid' => $bid,
                    'create_at' => time()
                ];
                Db::name('Collection')->insert($params);
                return json_encode(['code' => 200, 'msg' => '收藏成功']);
            }
            //如果有 那就更改is_del=1
            $update['is_del'] = 1;
            Db::name('Collection')->where(['id'=>$collection['id']])->update($update);
           
            return json_encode(['code' => 200, 'msg' => '收藏成功']);
            //发送收藏

        }

        if($type == 2){
            //取消收藏 
            $collection = Db::name('Collection')->where(['uid'=>$uid,'bid'=>$bid])->find();

            if(empty($collection))
            {
                return json_encode(['code' => 200, 'msg' => '取消收藏成功']);
            }
            //如果有 那就更改is_del=2
            $update['is_del'] = 2;
            Db::name('Collection')->where(['id'=>$collection['id']])->update($update);
            return json_encode(['code' => 200, 'msg' => '取消收藏成功']);

        }
    }

    /**
     * 查看手机号添加记录
     *$uid 用户的id $bid被查看者的id
     * @author LH
    */
    public function addTelCollection($uid,$bid)
    {

        $TelCollection = Db::name('TelCollection')->where(['uid'=>$uid,'bid'=>$bid])->find();
        //如果没有就添加记录
        if(empty($TelCollection))
        {
            $params = array();
            $params = [
                'uid' => $uid,
                'bid' => $bid,
                'create_at' => time()
            ];
            $res = Db::name('TelCollection')->insert($params);
            return $res;
        }

    }

    /**
     * 查看手机号的 数据拼接
     * $bid被查看者的id
     *type 1是可看手机号 2是看不了手机号
     * @author LH
    */
    public function telChange($bid,$type)
    {
        
        $userinfo = Db::name('Userinfo')->where(['id'=>$bid])->find();
        $children = Db::name('Children')->where(['uid'=>$bid])->find();
        $xing = '';
        if ($userinfo['realname']) 
        {
            $xing = mb_substr( $userinfo['realname'],0,1);
        }
        $user = array();
        $user['first'] = $xing.'家长';
        $user['two'] = $children['sex']==1?'儿子':'女儿';
        if ($children['year']) 
        {
            $user['two'] = $user['two'].'/'.substr($children['year'],-2).'年' ;
        }
        switch ($children['education']) {
            case '1':
                $children['education_test'] = '中专及以下';
                break;
            case '2':
                $children['education_test'] = '高中';
                break;
            case '3':
                $children['education_test'] = '大专';
                break;
            case '4':
                $children['education_test'] = '本科';
                break;
            case '5':
                $children['education_test'] = '研究生';
                break;
            case '6':
                $children['education_test'] = '博士';
                break;
            default:
                $children['education_test'] = '';
                break;
        }
        if ($children['education_test']) 
        {
            $user['two'] = $user['two'].'/'.$children['education_test'];
        }
        
        if ($children['residence']) 
        {
            $user['two'] = $user['two'].'/现居'.$children['residence'];
        }
        if($type == 1){
            $user['three'] = $children['phone'];
        }else{
            $user['three'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);
        }
        $user['three'] = substr($user['three'],0,3).' '.substr($user['three'],3,4).' '.substr($user['three'],7,4);
        $user['four'] = $userinfo['headimgurl'];
        return $user;

    }
 
    //点击{查看手机号}前的大概信息
    public function onclickTel()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        
        $bid = input("bid",'');

        if(empty($bid)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }
        //看看有没有以前有没有看过
        $TelCollection = Db::name('TelCollection')->where(['uid'=>$uid,'bid'=>$bid])->find();
        if(!empty($TelCollection))
        {
            $data = $this->TelChange($bid,1);
            $data['status'] = 1;//1是可以看 
            $data['count'] = 1; //方便前端判断
            return json_encode(['code' => 200, 'data' => $data]);
        }

        $userinfo = Db::name('Userinfo')->where(['id'=>$uid])->find();
        //查看是不是会员
        if($userinfo['is_vip'] == 1 && $userinfo['endtime']>=time())
        {
            $this->AddTelCollection($uid,$bid);
            $data = $this->TelChange($bid,1);
            $data['status'] = 1;//1是可以看 
            $data['count'] = 1; //方便前端判断
            return json_encode(['code' => 200, 'data' => $data]);

        }

        // dump($userinfo);exit;
      
        //不是会员也没看过
        $data = $this->TelChange($bid,2);
        $data['status'] = 2;//2是看不了 
        $data['count'] = $userinfo['count'];//剩余次数
        return json_encode(['code' => 200, 'data' => $data]);




    }



    /**
     * 查看手机号添加记录
     *$uid 用户的id $bid被查看者的id
     * @author LH
    */

    public function seeTel()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        
        $bid = input("bid",'');

        if(empty($bid)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }
       

        $userinfo = Db::name('Userinfo')->where(['id'=>$uid])->find();
        
        //不是会员就看看次数
        if($userinfo['count']>0){
            $children = Db::name('Children')->where(['uid'=>$bid])->find();
            //减少一次
            Db::name('Userinfo')->where(['id'=>$uid])->setDec('count',1);
            $biduser = Db::name('Userinfo')->where(['id'=>$bid])->find();
            //记录次数
            $params = [
                'uid' => $uid,
                'type' => 2,
                'count' => 1,
                'remarks' => '查看'.$biduser['nickname'].'手机号消耗一次次数',
                'create_at' => time()

            ];
            Db::name('TelCount')->insert($params);

            //添加记录
            $this->AddTelCollection($uid,$bid);
            $data['three'] = $children['phone'];
            $data['three'] = substr($data['three'],0,3).' '.substr($data['three'],3,4).' '.substr($data['three'],7,4);
            $data['status'] = 1;//1是可以看 
            return json_encode(['code' => 200, 'data' => $data, 'count'=>0]);
        }
        //不是会员也没次数

        $data = $this->TelChange($bid,2);
        $data['status'] = 2;//2是看不了 
        return json_encode(['code' => -200, 'msg' => '次数已经用光啦']);


    }

    /**
     *获取视频列表
     *$uid 用户的id
     * @author LH
    */
    public function getVideoList()
    {
        $map = array();
        $map['is_del'] = 1;
        $map['is_online'] =1;
        $list = db::name('video')->where($map)->field('id,img,title')->order('id desc')->select();
        $data = array();
        $data['code'] = 200;
        $data['list'] = $list;
        return json_encode($data);exit;
    }


    /**
     *获取视频详情
     *$uid 用户的id
     * @author LH
    */
    public function getVideoInfo()
    {
        $id = input("id", '', 'htmlspecialchars_decode');
        if (!$id) 
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "参数不能为空！";
            return json_encode($data);exit;
        }
        $map = array();
        $map['is_del'] = 1;
        $map['is_online'] =1;
        $map['id'] = $id;
        $info = db::name('video')->where($map)->find();
        if (!$info) 
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "视频不存在";
            return json_encode($data);exit;
        }
        Db::name('video')->where($map)->setInc('play_count',1);
        $data = array();
        $data['code'] = 200;
        $data['info'] = $info;
        return json_encode($data);exit;
    }

    /**
     *用户子女资料页面接口
     *$uid 用户的id
     * @author LH
    */
    public function getCommConfig()
    {
        $data = array();
        $data['code'] = 200;
        $data['video_show'] = 2;
        $data['wechat_url'] = 'http://mp.weixin.qq.com/s?__biz=Mzg3ODYzMjk5OA==&mid=100000006&idx=1&sn=085429b461d09aa0f663db416f363230&chksm=4f11884f786601595841cbdde10c0f56a0aaf384f08c8db9e258cba3e21bbe9af176faa7fe97#rd';
        return json_encode($data);exit;

    }
     


    /**
     *用户子女资料页面接口
     *$uid 用户的id
     * @author LH
    */
    public function childrenInfo()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }


        $children = Db::name('Children')->where(['uid'=>$uid])->find();
        if(empty($children)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }
        $userinfo = Db::name('Userinfo')->where(['id'=>$uid])->find();

        $children['realname'] = $userinfo['realname'];

        $children['headimgurl'] = $userinfo['headimgurl'];

        $children['sex_test'] = $children['sex']==1?'男':'女';

        $children['phone'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);

        switch ($children['education']) {
            case '1':
                $children['education_test'] = '中专及以下学历';
                break;
            case '2':
                $children['education_test'] = '高中学历';
                break;
            case '3':
                $children['education_test'] = '大专学历';
                break;
            case '4':
                $children['education_test'] = '本科学历';
                break;
            case '5':
                $children['education_test'] = '研究生学历';
                break;
            case '6':
                $children['education_test'] = '博士学历';
                break;
            default:
                $children['education_test'] = '暂未填写';
                break;
        }
        switch ($children['income']) {
            case '1':
                $children['income_test'] = '5000以下'; 
                break;
            case '2':
                $children['income_test'] = '5000-8000';

                break;
            case '3':
                $children['income_test'] =  '8000-12000';
                break;
            case '4':
                $children['income_test'] =  '12000-20000';
                break;
            case '5':
                $children['income_test'] =  '20000-30000';
                break;
            case '6':
                $children['income_test'] =  '30000以上';
                break;
            
            default:
                $children['income_test'] =  '暂未填写';
                break;
        }
        switch ($children['house']) {
            case '1':
                $children['house_test'] = '已购房';
                break;
            case '2':
                $children['house_test'] = '父母同住';
                break;
            case '3':
                $children['house_test'] = '租房';
                break;
            default:
                $children['house_test'] =  '暂未填写';
                break;
        }
        switch ($children['cart']) {
            case '1':
                $children['cart_test'] = '已购车';
                break;
            case '2':
                $children['cart_test'] = '近期购车';
                break;
            case '3':
                $children['cart_test'] = '无车';
                break;
            default:
                $children['cart_test'] =  '暂未填写';
                break;
        }
        //要求
        switch ($children['expect_education']) {
            case '1':
                $children['expect_education_test'] = '不限学历';
                break;
            case '2':
                $children['expect_education_test'] = '中专以上';
                break;
            case '3':
                $children['expect_education_test'] = '高中以上';
                break;
            case '4':
                $children['expect_education_test'] = '大专以上';
                break;
            case '5':
                $children['expect_education_test'] = '本科以上';
                break;
            case '6':
                $children['expect_education_test'] = '研究生以上';
                break;
            default:
                $children['expect_education_test'] = '暂未填写';
                break;
        }

        switch ($children['parents']) {
            case '1':
                $children['parents_test'] = '父母健在';
                break;
            case '2':
                $children['parents_test'] = '单亲家庭';
                break;
            case '3':
                $children['parents_test'] = '父亲健在';
                break;
            case '4':
                $children['parents_test'] = '母亲健在';
                break;
            default:
                $children['parents_test'] = '暂未填写';
                break;
        }

        switch ($children['bro']) {
            case '1':
                $children['bro_test'] = '独生子女';
                break;
            case '2':
                $children['bro_test'] = '老大';
                break;
            case '3':
                $children['bro_test'] = '老二';
                break;
            case '4':
                $children['bro_test'] = '老三';
                break;
            default:
                $children['bro_test'] = '暂未填写';
                break;
        }

        $children['min_age_test'] = $children['min_age']==999?'不限':$children['min_age'];

        $children['max_age_test'] = $children['max_age']==999?'不限':$children['max_age'];

        $children['min_height_test'] = $children['min_height']==999?'不限':$children['min_height'];

        $children['max_height_test'] = $children['max_height']==999?'不限':$children['max_height'];
        $map = array();
        $map['unionid'] = $userinfo['unionid'];
        $children['is_wechat'] = 0;
        $is_wechat = db::name('wechat_fans')->where($map)->find();
        if ($is_wechat) 
        {
            $children['is_wechat'] = 1;
        }


        return json_encode(['code' => 200, 'data' => $children]);

    }

    /**
     *判断手机号在不在我们的库里
     *$tel 手机号
     * @author LH
    */

    public function checkTel()
    {
        

        $tel = input("tel", '', 'htmlspecialchars_decode');



        // $tel = '17551014491';

        if(empty($tel)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }
        $checkphone  = preg_phone($tel);
        if(!$checkphone){
            return json_encode(['code' => -200, 'msg' => '手机号格式不正确']);
        }
        $children =  Db::name('Children')->where(['phone'=>$tel])->find();
        if(empty($children)){
            //没有 可以注册 发验证码
            $code = cache($tel);
            if(empty($code)){
                $code = rand(1000,9999);
                cache($tel,$code,300);
            }
            $data = sendTemplateSMS($tel,$code,'5分钟','969357');
            $data = get_object_vars(json_decode($data)); //stdclass 转化 数组

            if ($data['statusCode'] == '000000') 
            {
               return json_encode(['code' => 200, 'msg' => '验证码发送成功']);
            }
            return json_encode(['code' => -200, 'msg' => '短信次数超过5次,请换个手机号']);
            
        }

        return json_encode(['code' => -200, 'msg' => '手机号已经注册过,请换个手机号']);
        

    }


    /**
     *修改手机号验证 验证码  成功把手机号存进去
     *$tel 手机号 $code
     * @author LH
    */
    public function checkCode()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $tel = input("tel", '', 'htmlspecialchars_decode');

        $code = input("code", '', 'htmlspecialchars_decode');
        if(empty($tel)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        if(empty($code)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        $checkcode = cache($tel);

        if(empty($checkcode))
        {
            return json_encode(['code' => -200, 'msg' => '验证码过期']);
        }

        if($checkcode == $code)
        {
            //验证通过 手机号存入数据库
            $update['phone'] = $tel;
            Db::name('children')->where(['uid'=>$uid])->update($update);

            return json_encode(['code' => 200, 'msg' => '手机号添加成功']);
        }
        //验证不通过
        return json_encode(['code' => -200, 'msg' => '验证码错误']);



    }

    /**
     *相亲资料详情页没有登录
     *$bid 要查看的用户id
     * @author LH
    */

    public function childrenDetailsNologin()
    {
        //登陆验证
        $bid = input("bid", '', 'htmlspecialchars_decode');

        if(empty($bid)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        $children = Db::name('Children')->where(['uid'=>$bid])->find();

        if(empty($children)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }

        $userinfo = Db::name('Userinfo')->where(['id'=>$bid])->find();

        $children['realname'] = $userinfo['realname'];

        $children['headimgurl'] = $userinfo['headimgurl']; 

        $children['sui'] = date('Y') - $children['year'];
        $children['shuxiang'] = $this->getShuXiang($children['year']);
        $children['user_status'] = $userinfo['status'];

        $children['year'] = substr($children['year'],-2).'年';

        switch ($children['education']) {
            case '1':
                $children['education'] = '中专及以下学历';
                break;
            case '2':
                $children['education'] = '高中学历';
                break;
            case '3':
                $children['education'] = '大专学历';
                break;
            case '4':
                $children['education'] = '本科学历';
                break;
            case '5':
                $children['education'] = '研究生学历';
                break;
            case '6':
                $children['education'] = '博士学历';
                break;
            default:
                $children['education'] = '暂未填写';
                break;
        }
        switch ($children['expect_education']) {
            case '1':
                $children['expect_education'] = '不限学历';
                break;
            case '2':
                $children['expect_education'] = '中专以上';
                break;
            case '3':
                $children['expect_education'] = '高中以上';
                break;
            case '4':
                $children['expect_education'] = '大专以上';
                break;
            case '5':
                $children['expect_education'] = '本科以上';
                break;
            case '6':
                $children['expect_education'] = '研究生以上';
                break;
            default:
                $children['expect_education'] = '暂未填写';
                break;
        }
        switch ($children['income']) {
            case '1':
                $children['income'] = '5000以下'; 
                break;
            case '2':
                $children['income'] = '5000-8000';

                break;
            case '3':
                $children['income'] =  '8000-12000';
                break;
            case '4':
                $children['income'] =  '12000-20000';
                break;
            case '5':
                $children['income'] =  '20000-30000';
                break;
            case '6':
                $children['income'] =  '30000以上';
                break;
            
            default:
                $children['income'] =  '暂未填写';
                break;
        }
        switch ($children['house']) {
            case '1':
                $children['house'] = '已购房';
                break;
            case '2':
                $children['house'] = '父母同住';
                break;
            case '3':
                $children['house'] = '租房';
                break;
            default:
                $children['house'] =  '暂未填写';
                break;
        }
        switch ($children['cart']) {
            case '1':
                $children['cart'] = '已购车';
                break;
            case '2':
                $children['cart'] = '近期购车';
                break;
            case '3':
                $children['cart'] = '无车';
                break;
            default:
                $children['cart'] =  '暂未填写';
                break;
        }
        switch ($children['parents']) {
            case '1':
                $children['parents_test'] = '父母健在';
                break;
            case '2':
                $children['parents_test'] = '单亲家庭';
                break;
            case '3':
                $children['parents_test'] = '父亲健在';
                break;
            case '4':
                $children['parents_test'] = '母亲健在';
                break;
            default:
                $children['parents_test'] = '暂未填写';
                break;
        }

        switch ($children['bro']) {
            case '1':
                $children['bro_test'] = '独生子女';
                break;
            case '2':
                $children['bro_test'] = '老大';
                break;
            case '3':
                $children['bro_test'] = '老二';
                break;
            case '4':
                $children['bro_test'] = '老三';
                break;
            default:
                $children['bro_test'] = '暂未填写';
                break;
        }

        //看看对方我有么有收藏对方 1是没有 2是有
        $children['is_collection'] = 1;

        //审核团队信息

        $team = Db::name('team')->where(['id'=>$children['team_id']])->find();

        $children['sh_id'] = $team['id']; //审核队员几号

        $children['sh_headimg'] = $team['headimg']; //审核队员头像

        $children['sh_name'] = $team['name']; //审核队员名字

        $children['sh_time'] = rand(10,20); //审核队员时间

        //判断用户有没有查看过手机号 1是没有 2是有 
        $children['phone'] = '家长电话';
        $children['is_telcollection'] = 1;
        $children['is_me'] = 1;//不是自己
        return json_encode(['code' => 200, 'data' => $children]);



    }




    /**
     *相亲资料详情页
     *$bid 要查看的用户id
     * @author LH
    */

    public function childrenDetails()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $bid = input("bid", '', 'htmlspecialchars_decode');

        if(empty($bid)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        $children = Db::name('Children')->where(['uid'=>$bid])->find();

        if(empty($children)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }

        $userinfo = Db::name('Userinfo')->where(['id'=>$bid])->find();

        $children['realname'] = $userinfo['realname'];

        $children['headimgurl'] = $userinfo['headimgurl']; 

        $children['sui'] = date('Y') - $children['year'];
        $children['shuxiang'] = $this->getShuXiang($children['year']);

        $children['year'] = substr($children['year'],-2).'年';
        $children['user_status'] = $userinfo['status'];

        switch ($children['education']) {
            case '1':
                $children['education'] = '中专及以下学历';
                break;
            case '2':
                $children['education'] = '高中学历';
                break;
            case '3':
                $children['education'] = '大专学历';
                break;
            case '4':
                $children['education'] = '本科学历';
                break;
            case '5':
                $children['education'] = '研究生学历';
                break;
            case '6':
                $children['education'] = '博士学历';
                break;
            default:
                $children['education'] = '暂未填写';
                break;
        }
        switch ($children['expect_education']) {
            case '1':
                $children['expect_education'] = '不限学历';
                break;
            case '2':
                $children['expect_education'] = '中专以上';
                break;
            case '3':
                $children['expect_education'] = '高中以上';
                break;
            case '4':
                $children['expect_education'] = '大专以上';
                break;
            case '5':
                $children['expect_education'] = '本科以上';
                break;
            case '6':
                $children['expect_education'] = '研究生以上';
                break;
            default:
                $children['expect_education'] = '暂未填写';
                break;
        }
        switch ($children['income']) {
            case '1':
                $children['income'] = '5000以下'; 
                break;
            case '2':
                $children['income'] = '5000-8000';

                break;
            case '3':
                $children['income'] =  '8000-12000';
                break;
            case '4':
                $children['income'] =  '12000-20000';
                break;
            case '5':
                $children['income'] =  '20000-30000';
                break;
            case '6':
                $children['income'] =  '30000以上';
                break;
            
            default:
                $children['income'] =  '暂未填写';
                break;
        }
        switch ($children['house']) {
            case '1':
                $children['house'] = '已购房';
                break;
            case '2':
                $children['house'] = '父母同住';
                break;
            case '3':
                $children['house'] = '租房';
                break;
            default:
                $children['house'] =  '暂未填写';
                break;
        }
        switch ($children['cart']) {
            case '1':
                $children['cart'] = '已购车';
                break;
            case '2':
                $children['cart'] = '近期购车';
                break;
            case '3':
                $children['cart'] = '无车';
                break;
            default:
                $children['cart'] =  '暂未填写';
                break;
        }
        switch ($children['parents']) {
            case '1':
                $children['parents_test'] = '父母健在';
                break;
            case '2':
                $children['parents_test'] = '单亲家庭';
                break;
            case '3':
                $children['parents_test'] = '父亲健在';
                break;
            case '4':
                $children['parents_test'] = '母亲健在';
                break;
            default:
                $children['parents_test'] = '暂未填写';
                break;
        }

        switch ($children['bro']) {
            case '1':
                $children['bro_test'] = '独生子女';
                break;
            case '2':
                $children['bro_test'] = '老大';
                break;
            case '3':
                $children['bro_test'] = '老二';
                break;
            case '4':
                $children['bro_test'] = '老三';
                break;
            default:
                $children['bro_test'] = '暂未填写';
                break;
        }

        //看看对方我有么有收藏对方 1是没有 2是有

        $is_collection = Db::name('Collection')->where(['uid'=>$uid,'bid'=>$bid,'is_del'=>'1'])->find();
        if(empty($is_collection))
        {
            $children['is_collection'] = 1;
        }else{
            $children['is_collection'] = 2;
        }

        //审核团队信息

        $team = Db::name('team')->where(['id'=>$children['team_id']])->find();

        $children['sh_id'] = $team['id']; //审核队员几号

        $children['sh_headimg'] = $team['headimg']; //审核队员头像

        $children['sh_name'] = $team['name']; //审核队员名字

        $children['sh_time'] = rand(10,20); //审核队员时间

        //判断用户有没有查看过手机号 1是没有 2是有

        $is_telcollection = Db::name('TelCollection')->where(['uid'=>$uid,'bid'=>$bid,'is_del'=>'1'])->find();

        if($uid == $bid)
        {
            $children['is_telcollection'] = 2;
            $children['is_me'] = 2;
            return json_encode(['code' => 200, 'data' => $children]);
        }

        if(empty($is_telcollection))
        {
            $children['phone'] = '家长电话';
            $children['is_telcollection'] = 1;
        }else{
            $children['is_telcollection'] = 2;
        }
        $children['is_me'] = 1;//不是自己
        $is_vip = 0;//不是会员

        if ($userinfo['is_vip']== 1 && $userinfo['endtime'] >= time()) 
        {
            $is_vip = 1;
        }
        //统计总消费
        

        return json_encode(['code' => 200, 'data' => $children]);



    }

    /**
     *更多信息编辑
     *$session3rd
     * @author wzs
    */

    public function editRemarks()
    {
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        // //来源 推荐人id
        $remark = input("remarks", '', 'htmlspecialchars_decode');
        if (!$remark) 
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "数据不能为空";
            return json_encode($data);exit;
        }
        $map = array();
        $map['uid'] = $uid;
        $data = array();
        $data['remarks'] = $remark;
        $res = db::name('children')->where($map)->update($data);
        if ($res) 
        {
            $data = array();
            $data['code'] = 200;
            $data['msg'] = "更新成功";
            return json_encode($data);exit;
        }else
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "更新失败";
            return json_encode($data);exit;
        }

    }

   

    /**
     *用户添加子女资料
     *$session3rd
     * @author LH
    */

    public function addChildren()
    {
        
        // //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        // //来源 推荐人id
        $source = input("source", '', 'htmlspecialchars_decode');


        $params = array();
        $params = input("post.", '', 'htmlspecialchars_decode');
        unset($params['session3rd']);//去除不要的信息,存进数据库
        unset($params['source']);
        $map = array();
        $map['uid'] = $uid;
        $is_have = db::name("children")->where($map)->find();
        if ($is_have) 
        {
            return json_encode(['code' => -200, 'msg' => '孩子资料已完善']);
        }
        //添加审核员id team_id
        $count = Db::name('team')->count();
        $params['team_id'] = rand(1,$count);
        $params['uid'] = $uid;
        $params['create_at'] = time();

        //用户基本资料存进数据库
        Db::name('Children')->insert($params);

        //如果来源不为空且没有被其他人推荐过 给推荐者增加次数 并添加来源relation 添加 查看手机号次数流水记录 tel_count
        $count = Db::name('Relation')->where(['uid'=>$uid])->count();
        if(!empty($source) && $count == 0)
        {
            custom_log('liuhao2','来源==='.$source.',有没有被推荐过='.$count);
            //增加次数1
            Db::name('Userinfo')->where(['id'=>$source])->setInc('count',1);

            //添加来源 关系表 relation
            $relation = [
                'uid' => $uid,
                'bid' => $source,
                'create_at' => time()
            ];
            Db::name('Relation')->insert($relation);

            $userinfo = Db::name('Userinfo')->where(['id'=>$uid])->find();

            $telcount = [
                'uid' => $source,
                'type' => 1,
                'count' => 1,
                'remarks' => '推荐'.$userinfo['nickname'].'注册增加一次次数',
                'create_at' => time()

            ];
            Db::name('TelCount')->insert($telcount);

        }
        
        return json_encode(['code' => 200, 'msg' => '添加成功','data'=>$params]);
    }


    /**
     *添加手机号验证 验证码  成功把手机号存进去
     *$tel 手机号 $code
     * @author LH
    */
    public function oneCheckCode()
    {
       

        $tel = input("tel", '', 'htmlspecialchars_decode');

        $code = input("code", '', 'htmlspecialchars_decode');
        if(empty($tel)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        if(empty($code)){
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        $checkcode = cache($tel);

        if(empty($checkcode))
        {
            return json_encode(['code' => -200, 'msg' => '验证码过期']);
        }

        if($checkcode == $code)
        {
            return json_encode(['code' => 200, 'msg' => '验证码正确']);
        }
        //验证不通过
        return json_encode(['code' => -200, 'msg' => '验证码错误']);



    }
    /**
     *用户举报
     *uid type create_at
     * @author LH
    */
    public function report()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }

        $uid = $info['uid'];  //用户id

        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $type = input("type", '', 'htmlspecialchars_decode'); //举报类型

        if (empty($type)) {
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        $bid = input("bid", '', 'htmlspecialchars_decode'); // 被举报的用户id

        if (empty($bid)) {
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        $params = array();
        $params = [
            'uid' => $uid,
            'type' =>$type,
            'bid' =>$bid,
            'create_at' =>time()
        ];
        $res = Db::name('Report')->insert($params);
        if($res)
        {
            return json_encode(['code' => 200, 'msg' => '反馈成功']);
        }
        return json_encode(['code' => -200, 'msg' => '失败']);
    }


    /**
     *获取常见问题
     *$session3rd $type
     * @author LH
    */

    public function ques()
    {
        
        //登陆验证
        // $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        // $info = $this->checkLogin($session3rd);
        // if (!array_key_exists('openid', $info)) {
        //     return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        // }
        // $uid = $info['uid'];
        // if (empty($uid)) {
        //     return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        // }

        $type = input("type", '', 'htmlspecialchars_decode');

        if (empty($type)) {
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }
        
        $res = Db::name('ques')->where(['id'=>$type])->field('content')->find();
        return json_encode(['code' => 200,'data'=>$res]);

    }

    /**
     *分享页面信息详情
     *$session3rd 
     * @author LH
    */
    public function shareList()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $map = array();
        $map['bid'] = $uid;
        $sharelist = Db::name('Relation')->where($map)->field('uid,create_at')->order('create_at desc')->select();
        if(empty($sharelist))
        {
            return json_encode(['code' => -200, 'msg' => '暂无分享']);
        }
        foreach ($sharelist as $key => $value) {
            $pare = db::name('userinfo')->where(['id'=>$value['uid']])->find();
            $sharelist[$key]['nickname'] = $pare['nickname'];
            $sharelist[$key]['headimgurl'] = $pare['headimgurl'];
            $sharelist[$key]['sex'] = $pare['sex'];
            $sharelist[$key]['create_at'] = date('Y-m-d H:i',$sharelist[$key]['create_at']);
        }
        return json_encode(['code' => 200,'data'=>$sharelist]);
    }

     /**
     *用户获取自己的uid
     *$session3rd 
     * @author LH
    */

    public function getUserid()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        return json_encode(['code' => 200, 'data' => $uid]);
    }


     /**
     *获取轮播图
     *$session3rd  type代表哪个页面
     * @author LH
    */

    public function banner()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $type = input("type", '', 'htmlspecialchars_decode');

        if (empty($type)) {
            return json_encode(['code' => -200, 'msg' => '缺少参数']);
        }

        
        $map = array();
        $map['position'] = $type;
        $map['is_show'] = 1;
        $map['is_del'] = 1;
        $banner = db::name('banner')->where($map)->field('id,url,img_url')->order('sort desc')->select();

        if(empty($banner))
        {
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }

        return json_encode(['code' => 200, 'data' => $banner]);
    }

    /**
     * 用户撤销注销
     * @author tao
     * @date 2021/07/13
     */
    public function cancellation()
    {
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }

        $user_info = Db::name('userinfo')->where('id', $uid)->find();
        $up_date['status'] = 1;
        Db::startTrans();
        try{
            Db::name('userinfo')->where('id', $user_info['id'])->update($up_date);
            Db::name('children')->where('uid', $user_info['id'])->update($up_date);
            Db::name('wechat_fans')->where('unionid', $user_info['unionid'])->update($up_date);
            Db::commit();
            return json_encode(['code' => 200, 'msg' => '撤销注销成功!']);
        } catch (\Exception $e) {
            Db::rollback();
            return json_encode(['code' => 404, 'msg' => '撤销注销失败!']);
        }
    }


        /**
     * @Notes:type  1 我收藏的  2 收藏我的 3联系人
     * @Interface msgList
     * @author: zy
     * @Time: 2021/07/20
     */
    public function msgList(){
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        $type = input('type') ? : 1;
        $where = "is_del = 1 and is_show = 0";
        if($type == 2){
            $where .= " and bid = '{$uid}'";
        }else{
            $where .= " and uid = '{$uid}'";
        }
        $table = 'Collection';
        if($type == 3){
           $table = 'TelCollection';
        }
        $collection = Db::name($table)->where($where)->order('create_at desc')->select();
        if(empty($collection)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }
        $field = 'id,uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro';
        foreach ($collection as $key => $value) {
            //被收藏用户子女资料
            $where_c['uid'] = $value['bid'];
            $where_s['is_del'] = 1;
            $where_s['uid'] = $uid;
            $where_s['bid'] = $value['bid'];
            if($type == 2){ //收藏我的
                $where_c['uid'] = $value['uid'];
                $where_s['bid'] = $value['uid'];
            }
            $Childr = Db::name('Children')->where($where_c)->field($field)->find();
            $collection[$key] = $this->userchange($Childr);
            if(time() - $value['create_at']<172800){
                $todaystart = strtotime(date('Y-m-d'.'00:00:00',time()));
                if($value['create_at']<$todaystart){
                    $collection[$key]['create_time'] = '昨天';
                }else{
                    $collection[$key]['create_time'] = '今天';
                }
            }else{
                $collection[$key]['create_time'] = date("m月d日",$value['create_at']);
            }
            $is_collection = Db::name('Collection')->where($where_s)->find();//我收藏的 收藏我的 联系人
            if(empty($is_collection)){
                $collection[$key]['is_collection'] = 1;
            }else{
                $collection[$key]['is_collection'] = 2;
            }
            $collection[$key]['id'] = $value['id'];
        }
        return json_encode(['code' => 200, 'data' =>$collection]);
    }

    /**
     * @Notes:id 要删除的id
     * @Interface msgDel
     * @author: zy
     * @Time: 2021/07/20
     */
    public function msgDel(){
        //登陆验证
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $info = $this->checkLogin($session3rd);
        if (!array_key_exists('openid', $info)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        }
        $uid = $info['uid'];
        if (empty($uid)) {
            return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        }
        $id = input('id') ? : 0;
        $type = input('type') ? : 0;
        if (empty($id)) {
            return json_encode(['code' => 404, 'msg' => 'id不能为空']);
        }
        if (empty($type)) {
            return json_encode(['code' => 404, 'msg' => 'type不能为空']);
        }
        $table = 'Collection';
        if($type == 3){
           $table = 'TelCollection';
        }
        $find = Db::name($table)->where(['id'=>$id])->find();
        if(empty($find)){
            return json_encode(['code' => -200, 'msg' => '暂无数据']);
        }
        if($find['is_show'] == 1){
            return json_encode(['code' => -200, 'msg' => '该数据已经被删除']);
        }
        $res = Db::name($table)->where(['id'=>$id])->update(['is_show'=>1]);
        if(!$res){
            return json_encode(['code' => -200, 'msg' =>'删除失败']);
        }
        return json_encode(['code' => 200, 'msg' =>'删除成功']);

    }

    

}
