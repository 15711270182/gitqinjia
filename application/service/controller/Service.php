<?php


namespace app\service\controller;

use CURLFile;
use WeMini\Basic;
use WeMini\Qrcode;

class Service
{


    private static $appid;
    private static $secret;
    private static $grant_type;
    private static $url;
    private static $mch_id;
    private static $key;
    private static $appid_id;
    private $no_avatar;
    private static $token;

    public function __construct()
    {

        $this::$appid = config('wechat.miniapp.appid');
        $this::$secret = config('wechat.miniapp.secret');
        $this::$token = 'weixin';
    }

    // 校验服务器地址URL
    public function check_server()
    {
        custom_log('kefu', '$_REQUEST==' . print_r($_REQUEST, true));
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
            echo $echoStr . '+++' . self::$token;
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
        custom_log("ResponseMsg", '1111111');
        //	custom_log("Keywords", print_r($all_keywords, true));
        $postStr = file_get_contents('php://input');
        if (!empty($postStr) && is_string($postStr)) {
            $postArr = json_decode($postStr, true);
            $medai_id = cache('Kefu_media_id');
            $medai_id2 = cache('Kefu_media_id_2');
            custom_log('Answerkefu', '$postArr==' . print_r($postArr, true));
            if (!empty($postArr['MsgType']) && $postArr['MsgType'] == 'event') { // 文本消息
                custom_log('AnswerkefuAuth', '发送文字消息==' . '自动回复');
                $fromUsername = $postArr['FromUserName']; // 发送者openid
//                $context = $postArr['Content'];
//                $data = array(
//                    "touser" => $fromUsername,
//                    "msgtype" => "image",
//                    "image" => array(
//                        "media_id" => $medai_id
//                    )
//                );
                $data1 = array(
                    "touser" => $fromUsername,
                    "msgtype" => "text",
                    "text" => array(
                        "content" => "Hi，铂金之恋小助手很高兴为你服务，咨询哪些问题，请点击选择
1、<a href=\"weixin://bizmsgmenu?msgmenucontent=关注微信公众号&msgmenuid=1\" >关注微信公众号</a>
2、<a href=\"weixin://bizmsgmenu?msgmenucontent=会员&msgmenuid=1\" >如何开通铂金之恋会员？</a>
3、<a href=\"weixin://bizmsgmenu?msgmenucontent=金币&msgmenuid=1\" >如何购买金币？</a>
4、<a href=\"weixin://bizmsgmenu?msgmenucontent=联系人工客服&msgmenuid=1\">以上回到没用，请联系人工客服？</a>"
                    )
                );

//                custom_log('Answerkefu', '发送文字消息==' . print_r($data, true));
//                $this->sendKfMessage($data);
                $this->sendKfMessage($data1);
            }

            if (!empty($postArr['MsgType']) && $postArr['MsgType'] == 'text') { // 文本消息
                custom_log('Answerkefu', '发送文字消息==' . '您好，我是客服小新，有什么能帮助你?');
                $fromUsername = $postArr['FromUserName']; // 发送者openid
                $context = $postArr['Content'];
//                    $data = array(
//                        "touser" => $fromUsername,
//                        "msgtype" => "image",
//                        "image" => array(
//                            "media_id" => $medai_id
//                        )
//                    );
                $string = $context;
                //指定的字符串
                $arr = ['金币','会员', '充值', '买','获取'];

                preg_match_all('#('.implode('|', $arr).')#', $string, $wordsFound);
                //获取匹配到的字符串，array_unique()函数去重。如需获取总共出现次数，则不需要去重
                $wordsFound = array_unique($wordsFound[0]);
                if($wordsFound){
                    $data = array(
                        "touser" => $fromUsername,
                        "msgtype" => "text",
                        "text" => array(
                            "content" => "<a href=\"http://inlove.njzec.com/web/web/index?type=2\" >苹果用户充值金币入口>></a>


<a href=\"http://inlove.njzec.com/web/web/index\" >苹果用户购买会员入口>></a>"
                        )
                    );
                    if(isset($data)){

                        $this->sendKfMessage($data);
                    }
                    die;
                }


                switch ($context) {
                    case '联系人工客服':
                        $data = array(
                            "touser" => $fromUsername,
                            "msgtype" => "image",
                            "image" => array(
                                "media_id" => $medai_id2
                            )
                        );
                        break;
                    case '会员':
                        $data = array(
                            "touser" => $fromUsername,
                            "msgtype" => "image",
                            "image" => array(
                                "media_id" => $medai_id2
                            )
                        );
                        $data1 = array(
                            "touser" => $fromUsername,
                            "msgtype" => "text",
                            "text" => array(
                                "content" => "因为微信相关规定，暂不支持苹果用户在小程序内直接购买会员和金币。如要购买请联系客服"
                            )
                        );
                        break;
                    case '金币':
                        $data = array(
                            "touser" => $fromUsername,
                            "msgtype" => "image",
                            "image" => array(
                                "media_id" => $medai_id2
                            )
                        );
                        $data1 = array(
                            "touser" => $fromUsername,
                            "msgtype" => "text",
                            "text" => array(
                                "content" => "因为微信相关规定，暂不支持苹果用户在小程序内直接购买会员和金币。如要购买请联系客服"
                            )
                        );
                        break;
                    case '1':
                        $data = array(
                            "touser" => $fromUsername,
                            "msgtype" => "image",
                            "image" => array(
                                "media_id" => $medai_id
                            )
                        );
                        $data1 = array(
                            "touser" => $fromUsername,
                            "msgtype" => "text",
                            "text" => array(
                                "content" => "关注公众号的好处：
1.可及时接收异性的喜欢通知和聊天消息
2.每天可增加20%的曝光量
3.每天可多解锁1位嘉宾"
                            )
                        );
                        break;
                    default:
                        $data = array(
                            "touser" => $fromUsername,
                            "msgtype" => "image",
                            "image" => array(
                                "media_id" => $medai_id
                            )
                        );
                        $data1 = array(
                            "touser" => $fromUsername,
                            "msgtype" => "text",
                            "text" => array(
                                "content" => "关注公众号的好处：
1.可及时接收异性的喜欢通知和聊天消息
2.每天可增加20%的曝光量
3.每天可多解锁1位嘉宾"
                            )
                        );
                        break;
                }


//                    custom_log('Answerkefu', '发送文字消息==' . print_r($data, true));
//                    $this->sendKfMessage($data);
                if(isset($data)){
                    $this->sendKfMessage($data);
                }
                if(isset($data1)){
                    $this->sendKfMessage($data1);
                }
            }
        }


    }


    function sendKfMessage($data)
    {

        try{
            custom_log("Answerkefu", "进入sendKfMessage方法");
            $json = json_encode($data, JSON_UNESCAPED_UNICODE); // php5.4+
            $access_token = $this->get_accessToken();
            custom_log("Answerkefu", "json" . print_r($json, true));
            custom_log("Answerkefu", "" . $access_token);
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
            custom_log("Answerkefu", "output" . print_r($output, true));
            if (curl_errno($curl)) {
                custom_log('Answerkefu', 'faile error==' . curl_error($curl));
//                echo 'Errno' . curl_error($curl); // 捕抓异常
            }
            curl_close($curl);
            if ($output == 0) {
                custom_log("Answerkefu", "执行结束");
//                echo 'success';
//            exit();
            }
        } catch (\Exception $e) {
//
        }


    }

    public function get_accessToken()
    {
//        \We::config()->

//        try{
            @$token = \We::WeMiniQrcode(config('wechat.miniapp'))->getAccessToken();

            return $token;
//        } catch (\Exception $e) {
//
//        }

    }

    public function uploadImgMedia()
    {
//        Kefu_media_id_2
        $access_token = $this->get_accessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $access_token . '&type=image';
        $data['media'] = new CURLFile(realpath('./newqrcode2.jpg'));
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
    public function uploadImgMedia2()
    {
//        Kefu_media_id_2
        $access_token = $this->get_accessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $access_token . '&type=image';
        $data['media'] = new CURLFile(realpath('./newqrcode.png'));
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
        cache('Kefu_media_id_2', $media_id);
        curl_close($curl);
    }


}