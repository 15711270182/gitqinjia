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
use think\facade\Cache;
use function Qiniu\json_decode;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Api extends Controller
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


     /**
     * 
     * 订单支付回调方法
     *wzs
     */
    public function orderNotify()
    {
        custom_log('teet',111);  
        $weapp = new WeApp($this::$appid, $this::$secret,'./little/'.$this::$appid.'/');
        $payObj = $weapp->getPayObj(self::$mch_id,self::$key);
        
        // 支付状态完全成功，可以更新订单的支付状态了
        $notifyInfo = $payObj->getNotify();
        if(!$notifyInfo){
            return xml(['return_code' => 'FAIL', 'return_msg' => '未知错误！']);
        };
        custom_log('teet',222);  
        //支付通知数据获取成功
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS')
        {
            $order_number = $notifyInfo['out_trade_no'];
            $o_data['order_number'] = $order_number;
            
            $orderInfo = db::name('order')->where($o_data)->find();

            if(empty($orderInfo)){
                return 'FAIL';
            }
            if($orderInfo['status']){
                return 'FAIL';
            }
            
            $total_fee = $notifyInfo['total_fee'];
            //核实用户支付金额
            if($total_fee!=$orderInfo['payment']){
                return 'FAIL';
            }
           //修改订单状态 
            db('order')->where($o_data)->update(['status'=>1,'pay_time'=>time()]);
            //根据订单类型
            $map = array();
            $map['id'] = $orderInfo['goods_id'];
            $goods = db::name('product')->where($map)->find();
            custom_log('teet',$goods['type']);  
            if ($goods['type'] == 1) 
            {
                //购买会员 增加会员时间
                $map = array();
                $map['id'] = $orderInfo['uid'];
                $user = db::name('userinfo')->where($map)->find(); 
                if ($user['endtime'] <= time()) 
                {
                    $time = time()+$goods['num']*24*3600;
                }else
                {
                    $time = $user['endtime']+$goods['num']*24*3600;
                }
                custom_log('teet',$time);  
                $data = array();
                $data['is_vip'] = 1;
                $data['endtime'] = $time;
                db::name('userinfo')->where($map)->update($data);
            }else
            {
                custom_log('teet',666);  
                //增加次数
                $map = array();
                $map['id'] = $orderInfo['uid'];
           
                $res = db::name('userinfo')->where($map)->setInc('count',$goods['num']);
                if($res){
                    //添加增加记录
                    $params = [
                        'uid' => $orderInfo['uid'],
                        'type' => 1,
                        'count' => $goods['num'],
                        'remarks' => '充值次卡获得次数'.$goods['num'].'次',
                        'create_at' => time()
                    ];
                    // Tel::tcountAdd($params);
                   Db::name('tel_count')->strict(false)->insertGetId($params);
                }
                custom_log('payorder','支付'.print_r($res,true));
            }
            return xml(['return_code' => 'SUCCESS', 'return_msg' => '处理成功！']);
        }
        return xml(['return_code' => 'FAIL', 'return_msg' => '未知错误！']);
    }




    public function check_server()
    {
        // custom_log('kefu','$_REQUEST=='.print_r($_REQUEST,true));
        if (isset($_GET['echostr'])) {
            $this->valid();
        } else {
            $this->responseMsg();
        }
    }

     public function valid()
    {
         
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            header('content-type:text');
            echo $echoStr;
            exit();
        } else {
            echo $echoStr . '+++' .self::$token;
            exit();
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
    
        $token = self::$token;
        $tmpArr = array(
            $token,
            $timestamp,
            $nonce
        );
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
    
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    


    public function responseMsg()
    {
        $postStr = file_get_contents('php://input');
        custom_log('tttt2',$postStr);
        if (!empty($postStr) && is_string($postStr)) 
        {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $RX_TYPE = trim($postObj->MsgType);
            $fromUsername = trim($postObj->FromUserName);
            $toUserName = trim($postObj->ToUserName);
            $user= $this->slogin($fromUsername);

            if ($RX_TYPE == 'text') 
            {
                $resultStr = $this->handletext($fromUsername,$toUserName);
            }elseif ($RX_TYPE == 'event') 
            {
               $event = trim($postObj->Event);
               if ($event == "subscribe") 
               {
                    $resultStr = $this->handletext($fromUsername,$toUserName);
                   // $resultStr = $this->handleevent($fromUsername,$toUserName,$user['uid']);
                   // $resultStr = $this->sendtu($fromUsername,$toUserName,$user['uid']);
               }elseif ($event=='CLICK') 
               {
                   // $resultStr = $this->handleevent($fromUsername,$toUserName,$user['uid']);
                   // $resultStr = $this->sendtu($fromUsername,$toUserName,$user['uid']);
               }
            }else
            {
                $resultStr = "Unknow msg type: ".$RX_TYPE;
            }
            echo $resultStr;exit;
        } else {
            custom_log('Answerkefu','不知道=='.'您好，我是客服小新，有什么能帮助你?');
            exit();
        }
        
        
    }
     /**
     *
     * @param array $data
     * 2018年4月25日上午11:16:08
     * liuxin 285018762@qq.com
     */
    public function handletext($id,$t) 
    {

        $time = time();
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $id, $t, $time, 'text', ' 欢迎关注完美亲家');
        echo $resultStr;

    }


    public function slogin($openid) 
    {
        // $openid = 'oJj4v5w6qe1bCQgOlgOJ0sD8Vz1w';
        $map = array();
        $map['openid'] = $openid;
        $is_have = db::name('wechat_user')->where($map)->find();
        if (!$is_have) 
        {
            $access_token = $this->getaccesstoken();
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid;
            $json = file_get_contents($url);
            $userinfo = json_decode($json,true);
            $map = array();
            $map['unionid'] = $userinfo['unionid'];
            $app_user = db::name('userinfo')->where($map)->find();
            if (!$app_user) 
            {
                return 1;exit;
            }

            $data = array();
            $data['openid'] = $userinfo['openid'];
            $data['uid'] = $app_user['id'];
            $data['unionid'] = $userinfo['unionid'];
        
            $data['addtime'] = date('Y-m-d H:i:s',time());

            $userid = db('wechat_user')->insertGetId($data);
            $map = array();
            $map['id'] = $app_user['id'];
            db::name('userinfo')->where($map)->setInc('count',3);
            //添加增加记录
            $params = [
                'uid' => $app_user['id'],
                'type' => 1,
                'count' => 1,
                'remarks' => '关注公众号增加1次',
                'create_at' => time()
            ];
            Db::name('tel_count')->strict(false)->insertGetId($params);
            return 1;
        }
       
        

    }
    /**
     *
     * @param array $data
     * 2018年4月25日上午11:16:08
     * liuxin 285018762@qq.com
     */
    public function getaccesstoken() 
    {
        $accesstoken = cache('wx33665f6f8d16b7c1-accesstoken');
        if (!$accesstoken) 
        {
            //获取accesstoken
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx33665f6f8d16b7c1&secret=3148bd0bbda1b6aa7d084da6f698ac88';
            $res = file_get_contents($url);
            $arr = json_decode($res,true);
            $accesstoken = $arr['access_token'];
            cache('wx33665f6f8d16b7c1-accesstoken',$arr['access_token'],6200);
        }
        return $accesstoken;
    }
	

}
