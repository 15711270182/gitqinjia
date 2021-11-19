<?php


namespace app\h5\controller;

use think\Db;
use think\Controller;
use app\wechat\service\WechatService;
use app\api\controller\Base as BaseController;
/**
 * 应用入口控制器
 * h5管理器
 * @author Anyon <zy>
 */
class Hnqx extends Controller
{
    public function openVip(){
        $json_data = input('json_data');
        $data = json_decode($json_data,true);
        $newArr['uid'] = $data['uid'];
        $newArr['openid'] = $data['openid'];
        $newArr['price'] = $data['price'];
        $temp = $newArr;
        ksort($temp);
        reset($temp);
        $tempStr = "";
        foreach ($temp as $key => $value) {
            $tempStr .= $key . "=" . $value . "&";
        }
        $tempStr = substr($tempStr, 0, -1);
        $signature = md5($tempStr);
        if($signature != $data['signature']){
             echo "<script> alert('签名错误') </script>";
             die;
        }
        $jssdk = WechatService::getWebJssdkSign();
        $jssdk['link'] = "https://testqin.njzec.com/web/hnqx/index?money=".$newArr['price'];
        $this->assign('data',$newArr);
        $this->assign('dat',$jssdk);
        return $this->fetch('openVip');
    }
    //新版收银台 测试
    public function pay(){
        $json_data = input('json_data');
        $data = json_decode($json_data,true);
        $newArr['uid'] = $data['uid'];
        $newArr['openid'] = $data['openid'];
        $newArr['price'] = $data['price'];
        $temp = $newArr;
        ksort($temp);
        reset($temp);
        $tempStr = "";
        foreach ($temp as $key => $value) {
            $tempStr .= $key . "=" . $value . "&";
        }
        $tempStr = substr($tempStr, 0, -1);
        $signature = md5($tempStr);
        if($signature != $data['signature']){
             echo "<script> alert('签名错误') </script>";
             die;
        }
        $jssdk = WechatService::getWebJssdkSign();
        $jssdk['link'] = "https://testqin.njzec.com/web/hnqx/test?money=".$newArr['price'];
        $this->assign('data',$newArr);
        $this->assign('dat',$jssdk);
        return $this->fetch('pay');
    }
    //支付成功页面
    public function pay_success(){
        return $this->fetch('pay_success');
    }
    public function stip(){
        $jssdk = WechatService::getWebJssdkSign();
        $jssdk['link'] = "pages/index/index";
        $jssdk['username'] = config('wechat.miniapp.original_id');
        $this->assign('dat',$jssdk);
        return $this->fetch('stip');
    }
    public function jssdk(){
        $jssdk = WechatService::getWebJssdkSign();
        return json_encode($jssdk);
    }
    public function payh5(){
        return $this->fetch('payh5');
    }
}