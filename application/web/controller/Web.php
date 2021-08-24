<?php


namespace app\web\controller;

use app\wechat\service\WechatService;
use think\Controller;
use think\Db;

class Web extends Controller
{
    //公众号会员支付授权
    public function index()
    {
        $this->url = $this->request->url(true);
        $this->fans = WechatService::getWebOauthInfo($this->url);

        $info = $this->fans;
        $map['openid'] = $info['openid'];
        $is_have = Db::name('wechat_fans')->where($map)->find();
        if ($is_have) {
            $unionid = $is_have['unionid'];
            $uid = Db::name('userinfo')->where(['unionid' => $unionid])->value('id');
            if ($uid) {
                $url = 'https://testqin.njzec.com/h5/web/openVip?uid=' . $uid . '&openid=' . $map['openid'];
                header("Location:" . $url);
                die;
            }else{
                $url = 'https://testqin.njzec.com/h5/web/stip';
                header("Location:" . $url);
                die;
            }
        }
        $scope = 'snsapi_userinfo';//snsapi_userinfo
        $stip = 'https://' . $_SERVER['HTTP_HOST'] . '/web/web/authBack';
        $url = \We::WeChatOauth(config('wechat.wechat'))->getOauthRedirect($stip, '', $scope);
        header("Location:" . $url);
        exit;
    }

    //授权
    public function authBack()
    {
        $json_obj = \We::WeChatOauth(config('wechat.wechat'))->getOauthAccessToken();
        $access_token = $json_obj['access_token'];
        $openid = $json_obj['openid'];

        $map['openid'] = $openid;
        $is_have = Db::name('wechat_fans')->where($map)->find();
        if (!$is_have) {
            $openid = $json_obj['openid'];
            $user_obj = \We::WeChatOauth(config('wechat.wechat'))->getUserInfo($access_token, $openid);
            $data = array();
            $data['openid'] = $user_obj['openid'];
            $data['unionid'] = $user_obj['unionid'];
            $data['nickname'] = $user_obj['nickname'];
            $data['headimgurl'] = $user_obj['headimgurl'];
            $data['sex'] = $user_obj['sex'];
            $data['province'] = $user_obj['province'];
            $data['city'] = $user_obj['city'];
            $data['country'] = $user_obj['country'];
            $data['language'] = $user_obj['language'];
//            $data['create_time'] = time();
            $data['create_at'] = date('Y-m-d H:i:s');
            $data['appid'] = config('wechat.wechat.appid');
            $userid = Db::name('wechat_fans')->insertGetId($data);
            $is_have = [];
            $is_have = $data;
            $is_have['id'] = $userid;
        }
        $unionid = $is_have['unionid'];
        $uid = Db::name('userinfo')->where(['unionid' => $unionid])->value('id');
        if ($uid) {

            $url = 'https://testqin.njzec.com/h5/web/openVip?uid=' . $uid . '&openid=' . $openid;
            header("Location:" . $url);
            die;
        } else {
            echo "<script> alert('请先使用我们的小程序') </script>";
            die;
        }
    }


}