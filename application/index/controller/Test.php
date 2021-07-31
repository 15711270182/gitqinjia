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
use app\index\service\UsersService;
use service\ToolsService;
use think\Db;
use function Qiniu\json_decode;
use think\Queue;

use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Test extends Controller
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
        $this::$appid = 'wx5edf4369a4e29312';
        $this::$secret = '6131cf9faa54795b6439130668fe4f15';
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
        $data = array();
        $data['code'] = 200;
        $res = encrypt(json_encode($data),$this::$aes_key);//加密 
        $res = decrypt($res,$this::$aes_key);//解密
        
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
            //静默授权
            $map = array();
            $map['openid'] = $openid;
            $is_have = db::name('userinfo')->where($map)->find();
            if (!$is_have) 
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
            $data['unionid'] = $unionid;
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
        $sta = $page*5-5;

        $list = db::name('children')->where($map)->field('uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart')->limit("$sta,5")->select();
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
    public function home() 
    {
        $uid = 142;
        $num = 15;
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = date('Ymd');
        $is_recommend = db::name('recommend_record')->where($map)->select();
        // dump($is_recommend);exit;
        
        if ($is_recommend) 
        {
            $list = array();
            foreach ($is_recommend as $key => $value) 
            {
                $map = array();
                $map['uid'] = $value['recommendid'];
                $list[$key] = db::name('children')->where($map)->find();
                $list[$key]['is_match'] = $value['is_match'];
             }
             return $list;exit;
        }
        $map = array();
        $map['uid'] = $uid;
        //查询用户详情
        $child = db::name('children')->where($map)->find();
        // dump($child);exit;
        // dump($child);exit;
        //取出昨日推荐的放到今日推荐里面
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = date('Ymd',strtotime('-1 days'));
        $tomorrow = db::name('tomorrow_recommend')->where($map)->select();
        // dump($tomorrow);exit;

        //step1 取出最近和用户发生联系的人的账号 不予推荐
        //近三天推荐
        $three = date('Ymd',strtotime('-3 days'));
        $map = array();
        $map['uid'] = $uid; 
        $three_list = db::name('recommend_record')->where($map)->where('addtime','>=',$three)->select();
        //取出我收藏的
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $collection = db::name('collection')->where($map)->field('bid')->select();
        //取出联系人
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $tel = db::name('tel_collection')->where($map)->field('bid')->select();
        $str = $child['uid'];
        if ($three_list) 
        {
            foreach ($three_list as $key => $value) 
            {
                $str = $str.','.$value['recommendid'];
            }
        }
        if ($tomorrow) 
        {
            foreach ($tomorrow as $key => $value) 
            {
                $str = $str.','.$value['recommendid'];
            } 
        }
        if ($collection)
        {
            foreach ($collection as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }
        
        if ($tel) 
        {
            foreach ($tel as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }

        $connection = '';
        $mate = array();//匹配
        $high = array();//高分
        $plunk = array();//分低的扶持
        //step2 看用户是否有要求
        // dump($child);/
        if ($child['expect_education'] || $child['min_age'] || $child['max_age'] || $child['min_height'] || $child['max_height']) 
        {
            // dump(2);exit;

            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            // dump($str);exit;
            
            $map['sex'] = $child['sex']==1?2:1;
            $temp = $child["residence"]; 
            $map['residence'] = "$temp";

            if ($child['expect_education']) 
            {
                $map['education'] = $child['expect_education'];
            }
            $db->where($map);
            //年龄
            if ($child['min_age'] || $child['max_age']) 
            {
                if ($child['max_age'] == 0) 
                {
                    $child['max_age'] = 80;
                }
                if ($child['min_age'] == 999) 
                {
                    $child['min_age'] = 0;
                }
                $max_year = $this->age2year($child['min_age']);
                $min_year = $this->age2year($child['max_age']);
                $db->where('year','between',"$min_year,$max_year");

            }
            if ($child['min_height'] || $child['max_height']) 
            {
                if ($child['max_height'] == 0) 
                {
                    $child['max_height'] = 250;
                }
                if ($child['min_height'] == 999) 
                {
                    $child['min_height'] = 0;
                }
                $min_height =$child['min_height'];
                $max_height =$child['max_height'];
                $db->where('height','between',"$min_height,$max_height");

            }
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }
            // dump($db);exit;
            $mate = $db->limit(5)->order('today_score desc')->select();
        }
        // dump($mate);exit;
        foreach ($mate as $key => $value) 
        {
            $str = $str.','.$value['uid'];
            $mate[$key]['is_match']  = 2;
        }
        

        //取s剩余的条数不上
        $last_num = $num-count($mate)-count($tomorrow);
        $tomorrow_arr = array();
        // dump($tomorrow);
        foreach ($tomorrow as $key => $value) 
        {
            $map = array();
            $map['uid'] = $value['recommendid']; 
            $tomorrow_arr[$key] = db::name('children')->where($map)->find();
            $tomorrow_arr[$key]['is_match'] = $value['is_match'];
        }
        // dump($tomorrow_arr);exit;

        $db = Db::name('children');
        $map = array();
        $map['is_del'] = 1;
        $map['sex'] = $child['sex']==1?2:1;
        $map['education'] = $child['education'];
        $temp = $child["residence"]; 
        $map['residence'] = "$temp";
        $db->where($map);
        $max_year = $child['year']+10;
        $min_year = $child['year']-10;
        $db->where('year','between',"$min_year,$max_year");
        if ($str)
        {
            $db->where('id','notin',explode(",", $str));
        }

        $high = $db->limit($last_num)->order('today_score desc')->select();
        // dump($high);exit;
        foreach ($high as $key => $value) 
        {
            $high[$key]['is_match'] = 1;
        }
        // dump($high);exit;
        $list  = array();
        $list = array_merge($mate,$high);
        // dump($list);exit;

        $list  = array_merge($list,$tomorrow_arr);
        foreach ($list as $key => $value) 
        {
            $str = $str.','.$value['uid'];
        }
        shuffle($list);
        if (count($list) < 15) 
        {   
            $last_num = 15 - count($list) ;
            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            $map['sex'] = $child['sex']==1?2:1;
            // $map['education'] = $child['education'];
            $db->where($map);
            $max_year = $child['year']+20;
            $min_year = $child['year']-20;
            $db->where('year','between',"$min_year,$max_year");
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }

            $bc = $db->limit($last_num)->order('today_score desc')->select();
            foreach ($bc as $key => $value) 
            {
                $bc[$key]['is_match'] = 1;
            }
            $list  = array_merge($list,$bc);
        }
        // dump($list);exit;

        

        //打乱顺序

        foreach ($list as $key => $value) 
        {
            dump($value['uid']);
            //今天分数-1
            // $map = array();
            // $map['uid'] = $value['uid'];
            // db::name('children')->where($map)->setDec('today_score',5);
            // $data = array();
            // $data['uid'] = $uid;
            // $data['recommendid'] = $value['uid'];
            // $data['date'] = date('Ymd');
            // $data['addtime'] = time();
            // $data['is_match'] = $value['is_match'];
            // db::name('recommend_record')->insert($data);
        }
        exit;
        return $list;

    }
}
