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
use mGQrCodeReader\MGQrCodeReader;
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
class Recommend extends Controller
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
        $this::$appid = 'wx4c60e929df966b9f';
        $this::$secret = 'd2a37bbcf9755a9702b1f8f16f485354';
        $this::$grant_type ='authorization_code';
        $this::$url = 'https://api.weixin.qq.com/sns/jscode2session';
        $this::$mch_id = '1572838091';
        $this::$key = 'CBDF911D317C03D8BA81EEFCF79F7AD3';
        $this::$token = 'weixin';
        $this->no_avatar = "http://small.ying-ji.com/understand/noheader.png";
        $this::$aes_key = '5b9c2ed3e19c40e5';
    }

    /**
     *假数据添加
     * wzs
     */
    public function index()
    {
        $MGQrCodeReader = new \MGQrCodeReader\MGQrCodeReader();
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEk8TwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyRll3YllYQldlOWsxMDAwME0wN0EAAgS8b8lgAwQAAAAA';
        $res =  $MGQrCodeReader->read($url);
        dump($res);exit;
        
        
    }


     /**
     *假数据添加
     * wzs
     */
    public function test()
    {
        $res = model('Recommend')->getrecommend(142,15);
        
        
    }
    /**
    /**
     * 首页有登录的情况下拉取的 
     * @author wzs
    */
    public function home() 
    {
        //登陆验证
        // $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        // $info = $this->checkLogin($session3rd);
        // if (!array_key_exists('openid', $info)) {
        //     return json_encode(['code' => 404, 'msg' => '用户登陆失效,需重新登陆']);
        // }
        $uid = 142;
        // if (empty($uid)) {
        //     return json_encode(['code' => 404, 'msg' => '用户登陆失败,需重新登陆']);
        // }
        //剩余时间 
        $temp_time = strtotime(date('Y-m-d').' 12:00:00')+24*3600;
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
            foreach ($data as $key => $value) 
            {
                $list[$key] = $this->userchange($value);
            }
            // cache('todayrecommend'.date('Ymd').'-'.$uid,$list,3600*24);
        }
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
        if (!$paytype) 
        {
            $temp = rand(1,10);
            if ($temp<= 5) 
            {
                $paytype = 1;
            }else
            {   
                $paytype == 2;
            }
            cache('paytypeuid-'.$uid,$paytype,3*24*3600);
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
            $user['first'] = $user['first'].'/'.substr($value['year'],-2).'年' ;
        }
        $user['second'] = '高'.$value['height'];
        if ($value['residence']) 
        {
            $user['second'] = $user['second'].'/现居'.$value['residence'];
        }
        if ($value['native_place']) 
        {
            $user['second'] = $user['second'].'/'.$value['residence'].'户口';
        }
        if ($value['hometown']) 
        {
            $user['second'] = $user['second'].'/'.$value['hometown'].'人';
        }
        $user['three'] = '';
        switch ($value['education']) {
            case '1':
                $user['three'] = $user['three'].'中专及以下学历';
                break;
            case '2':
                $user['three'] = $user['three'].'高中学历';
                break;
            case '3':
                $user['three'] = $user['three'].'大专学历';
                break;
            case '4':
                $user['three'] = $user['three'].'本科学历';
                break;
            case '5':
                $user['three'] = $user['three'].'研究生学历';
                break;
            case '6':
                $user['three'] = $user['three'].'博士学历';
                break;
            default:
                $user['three'] = $user['three'].'中专及以下学历';
                break;
        }
        if($value['work'] && $value['income'])
        {
            $user['four'] = $value['work'].'/';
        }else{
            $user['four'] = $value['work'];
        }
        if ($value['income']) 
        {
            switch ($value['income']) {
                case '1':
                    $user['four'] = $user['four'].'月收入5000以下'; 
                    break;
                case '2':
                    $user['four'] = $user['four'].'月收入5000-8000';

                    break;
                case '3':
                    $user['four'] = $user['four'].'月收入8000-12000';
                    break;
                case '4':
                    $user['four'] = $user['four'].'月收入12000-20000';
                    break;
                case '5':
                    $user['four'] = $user['four'].'月收入20000-30000';
                    break;
                case '6':
                    $user['four'] = $user['four'].'月收入30000以上';
                    break;
                
                default:
                    $user['four'] = $user['four'].'月收入暂未填写';
                    break;
            }
        }
        $user['five'] = '';
        if ($value['house'] == 1)  
        {
            $user['five'] = '有房';
            if ($value['cart'] == 1) 
            {
                $user['five'] = $user['five'].'|有车';
            }else
            {
                $user['five'] = $user['five'].'|暂无车';
            }

        }else{
            $user['five'] = '无房';
            if ($value['cart'] == 1) 
            {
                $user['five'] = $user['five'].'|有车';
            }else
            {
                $user['five'] = $user['five'].'|暂无车';
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
        $user['sex'] = $value['sex'];


        return $user; 
    }








}
