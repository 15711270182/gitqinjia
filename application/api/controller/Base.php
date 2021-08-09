<?php
/**
 * Created by PhpStorm.
 * User: it-joker
 * Date: 2018/8/13
 * Time: 13:45
 */
namespace app\api\controller;
use app\api\service\UsersService;
use app\wechat\service\WechatService;
use think\Controller;
use think\db;

class Base extends Controller
{
    const errcode_ok=200;//请求成功
    const errcode_fail=-200;//请求失败
    const errcode_noenough_fail=201;//请求失败
    const errcode_code_fail=400;//CODE码失效
    const errcode_login_fail=401;//session3rd失效

    public  $uid =0;
    public  $unionid =0;
    public function initialize()
    {
        $session3rd = $this->request->param('session3rd');
        $debug_uid =  $this->request->param('debug_uid');
        $action =   $this->request->action();
        $controller=$this->request->controller();
        $api = $controller.'/'.$action;
        if($controller != 'Token' && $api != 'Index/getuserlist' && $api != 'Index/childrendetails' && $api != 'Index/checktel' && $api != 'Index/checkcode' && $api != 'Index/getvideolist' && $api != 'Index/getvideoinfo' && $api != 'Index/shareinfo' && $api != 'Index/ques' && $api !='User/pushsubuser' && $api !='Index/sendtjmsg') { //不需要检测的接口
            if (!in_array($action, ['session', 'auth', 'getcdntoken', 'getphone', 'paymemberh5', 'ordernotify', 'payscoreh5', 'pageeditor'])) {
                //判断当前用户登录状态
                $user = UsersService::check($session3rd,$debug_uid);
                if (!$user) {
                    echo $this->errorReturn(self::errcode_login_fail);
                    die;
                }
                $this->uid = $user['uid'];
                $this->unionid = $user['unionid'];
                //判断当前用户使用状态
                sysoplog($action, '$session3rd===' . $session3rd);
            }
        }
    }

    /**
     * 返回请求失败信息
     * @param array $data
     * @param int $code
     * @param string $message
     */
    public function errorReturn($code = 1,$message = '系统繁忙，请稍后再试',$data = [])
    {
        if($code!=1&&($message=='系统繁忙，请稍后再试'||$message=='')){
            $error_msg = config('errorcode.error_msg');
            $message = isset($error_msg[$code])?$error_msg[$code]:'系统繁忙，请稍后再试';
        }
        if (empty($data)){
            $data = (object)[];
        }

        $response = [
            'data'=>$data,
            'errcode'=>$code,
            'errmsg'=>$message
        ];
        return json_encode($response);
    }

    /**
     * 返回请求成功信息
     *
     * @param array $data
     * @param int $code
     * @param string $message
     */
    public function successReturn($data = [], $message = '请求成功', $code = 200)
    {
        if (empty($data)){
            $data = (object)[];
        }
        $response = [
            'data'=>$data,
            'errcode'=>$code,
            'errmsg'=>$message
        ];

        return json_encode($response);
    }
    /**
     * @Notes:微信文字校验
     * @Interface msgSecCheck
     * @author: LiYang
     * @Time: 2020/12/27   15:47
     */
    public function msgSecCheck($content)
    {
        try {
            $res = \We::WeMiniSecurity(config('wechat.miniapp'))->msgSecCheck($content);
            $res = json_decode($res, 1);
            custom_log('msgSecCheck', 'res' . json_encode($res));
            return true;
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code == 0) {
                return true;
            }
            return false;
        }

    }
    /**
     *
     * 调用微信检验图片接口
     * 2018年4月14日上午11:44:56
     * liuxin 285018762@qq.com
     */
    function imgSecCheck($img)
    {

        $obj = new \CURLFile(realpath($img));
        $obj->setMimeType("image/jpeg");
        $info = \We::WeMiniSecurity(config('wechat.miniapp'))->imgSecCheck($obj);
        return $info;
    }

    /**
     * @Notes:发送模板消息事务处理
     * @Interface shiwuSendMsg
     * @param $message
     * @return bool
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function shiwuSendMsg($data,$type=0)
    {
        try {
            if($type == 2){
                $temp = WechatService::WeMiniNewtmpl(config('wechat.miniapp'));
            }else{
                $config['appid'] = 'wx33665f6f8d16b7c1';
                $config['appsecret'] = '3148bd0bbda1b6aa7d084da6f698ac88';
                $temp = WechatService::WeChatTemplate($config);
            }
            $temp->send($data);
            //模板消息推送成功录入
            return true;
        } catch (\Exception $e) {
            custom_log('新版发送订阅消息参数', json_encode($data) . $e->getMessage());
            return false;
        }
    }

}