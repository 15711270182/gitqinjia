<?php


namespace app\service\controller;

use CURLFile;
use WeMini\Basic;
use WeMini\Qrcode;
use app\wechat\service\MediaService;
use app\wechat\service\WechatService;
use app\api\controller\Base;
use think\Db;
/**
 * 微信消息推送处理
 * Class Wxservice
 * @package app\service\controller
 */
class Wxservice
{
    /**
     * 微信APPID
     * @var string
     */
    protected $appid;

    /**
     * 微信用户OPENID
     * @var string
     */
    protected $openid;

    /**
     * 消息是否加密码
     * @var boolean
     */
    protected $encrypt;


    /**
     * 微信OPENID
     * @var string
     */
    protected $fromOpenid;

    /**
     * 微信消息对象
     * @var array
     */
    protected $receive;

    /**
     * 微信实例对象
     * @var \WeChat\Receive
     */
    protected $wechat;

    /**
     * 强制返回JSON消息
     * @var boolean
     */
    protected $forceJson = false;

    /**
     * 强制客服消息回复
     * @var boolean
     */
    protected $forceCustom = false;

    /**
     * 获取网络出口IP
     * @return mixed
     */
    public function geoip()
    {
        return $this->request->ip();
    }
    //首次关注公众号 执行第一天的推送内容
    public function addTaskRecord($openid = '',$unionid = ''){
        // $openid = 'oJj4v55t8AK7ZxMemRGV8Amb8kRA';
        // $unionid = 'oKvpA6IGOIjxK4X2UvG0pkntko0A';
        if(empty($openid) ||empty($unionid)){
            custom_log("第一天推送", '错误_'.$openid);
            return [];
        }
        $send1 = [
            "touser" => $openid,
            "msgtype" => "text",
            "text" => [
                "content" => '亲爱的家长您好,您是否在为孩子找一个合适的伴侣而烦恼呢？
我们完美亲家是一个家长帮孩子脱单的平台。通过手机就能接触到更多适合孩子的人,
所有用户均为实名认证,效率高,诚意高,脱单快,安全可靠。您离孩子脱单只差一步了哦,
快去看看亲家的资料吧',
            ],
        ];
        $sendRes1 = $this->sendKfMessage($send1);
        // $sendRes1 = 1;
        if($sendRes1){
            $send2 = [
                'msgtype' => 'miniprogrampage',
                'touser' => $openid,
                'miniprogrampage' => [
                    "title" => '帮孩子找对象到完美亲家',
                    "appid" => 'wx70d65d2170dbacd7',
                    "pagepath" => 'pages/home/home',
                    "thumb_media_id" => 'z24GvlpzOWKU8VKZVZzUEeoE4n4pcfYTEkPNX3U6mVw',
                ],
            ];
            $sendRes2 = $this->sendKfMessage($send2);
            if($sendRes2){
                set_time_limit(2);
                $send3 = [
                    "touser" => $openid,
                    "msgtype" => "text",
                    "text" => [
                        "content" => '家长您好，请问您是找女婿还是找儿媳呢？找女婿请回复1，找儿媳请回复2',
                    ],
                ];
                $sendRes3 = $this->sendKfMessage($send3);
            }
            if($sendRes1 && $sendRes2 && $sendRes3){
                custom_log("第一天推送", "成功_" . $openid);
                $rData = Db::name('task_had_reply_record')->where(['openid'=>$openid])->find();
                if(empty($rData)){
                    //添加第一天的推送记录
                    $record_insert['openid'] = $openid;
                    $record_insert['unionid'] = $unionid;
                    $record_insert['uid']     = '4960';
                    $record_insert['create_time']   = date('Y-m-d H:i:s');
                    Db::name('task_had_reply_record')->insertGetId($record_insert);
                }  
            }
           
        }
        custom_log("第一天推送", "失败_" . $openid);
    }
    /**
     * 第一天的消息推送 1.未回复—模版消息(15分钟后) 2.未付费(60分钟后) 3.未付费(70分钟后)
     * @return boolean|string
     */
    public function index_day()
    {

        $data = Db::name('task_had_reply_record')->select();
        // var_dump($data);die;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $mins = floor((time()-strtotime($value['create_time']))%86400/60);
                // var_dump($mins);
                if($value['status'] == 0){ //未回复
                    $push_openid = cache('push_first_'.$value['openid']);
                    // var_dump($push_openid);
                    if($mins > 15 && empty($push_openid)){
                        //发送模版消息  
                        $tip = '今天已为您精准匹配30位相亲对象';
                        $remark = '点击查看资料';
                        $temp_id = 'yittRXCFWxzJSHJG6kWSCaed46Lr1JOdi_O-1lCvT2M';
                        $data = array();
                        $data['first'] = array('value'=>$tip,'color'=>'#FF0000');
                        $data['keyword1'] = array('value'=>'完美亲家','color'=>'#0000ff');
                        $data['keyword2'] = array('value'=>'同城相亲对象','color'=>'#0000ff');
                        $data['remark'] = array('value'=>$remark,'color'=>'#0000ff');
                        $param = [
                            'touser'=>$value['openid'],
                            'template_id'=>$temp_id,
                            'page'=>'pages/home/home',
                            'data'=>$data,
                            'miniprogram' => [
                                'pagepath'=>'pages/home/home',
                                'appid'=>'wx70d65d2170dbacd7',
                            ],
                        ];
                        $res = $this->shiwuSendMsg($param);
                        // $res = 1;
                        if($res){
                            cache('push_first_'.$value['openid'],$value['openid']);
                            custom_log("第一天推送", "未回复_" . $value['openid']);
                        }
                    }
                }
            }
        }
    }
    public function sendKfMessage($data)
    {

        try{
            // custom_log("wxpush", "进入sendKfMessage方法");
            $json = json_encode($data, JSON_UNESCAPED_UNICODE); // php5.4+
            $access_token = cache('accessToken');
            if(empty($access_token)){
                $access_token = $this->get_accessToken();
            }
            // var_dump($access_token);die;
            custom_log("wxpush", "json" . print_r($json, true));
            // custom_log("wxpush", "" . $access_token);
            /*
             * POST发送https请求客服接口api
             */
            $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
            // 以'json'格式发送post的https请求
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            if (!empty($json)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
            $output = curl_exec($curl);
            // var_dump($output);die;
            custom_log("wxpush", "output" . print_r($output, true));
            if (curl_errno($curl)) {
                custom_log('wxpush', 'faile error==' . curl_error($curl));
            }
            curl_close($curl);
            $output_json = json_decode($output,true);
            if($output_json['errcode'] == 40001 || $output_json['errcode'] == 42001){ //token问题
                $this->del_accessToken();
                self::sendKfMessage($data);
            }
            if ($output_json['errcode'] == 0) {
                custom_log("wxpush", "执行结束");
                return true;
                exit();
            }
        } catch (\Exception $e) {
        }


    }
    //清除accessToken
    public function del_accessToken()
    {
        cache('accessToken',NULL);
        return \We::WeChatQrcode(config('wechat.wechat'))->delAccessToken();
    }
    //获取accessToken
    public function get_accessToken()
    {
        $token = \We::WeChatQrcode(config('wechat.wechat'))->getAccessToken();
        cache('accessToken',$token);
        return $token;
    }
    public function uploadImgMedia()
    {
        $access_token = $this->get_accessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $access_token . '&type=image';
        $data['media'] = new CURLFile(realpath('./qrcode.jpg'));
        $data[] = "image/jpeg";
        // 以'json'格式发送post的https请求
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($json)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        $output = curl_exec($curl);
        custom_log("AnswerkefuUpload", "output" . print_r($output, true));
        if (curl_errno($curl)) {
            custom_log('AnswerkefuUpload', 'faile error==' . curl_error($curl));
            echo 'Errno' . curl_error($curl); // 捕抓异常
        }
        $data = json_decode($output, 1);
        $media_id = $data['media_id'];
        cache('Kefu_media_id', $media_id);
        curl_close($curl);
    }
    /**
     * @Notes:发送模板消息事务处理
     * @Interface shiwuSendMsg
     * @param $data
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