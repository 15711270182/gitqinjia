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
        $uid = input('uid');
        $priceInfo = getDisPrice($uid);
        $this->assign('uid',$uid);
        $this->assign('priceInfo',$priceInfo);
//        $jssdk = WechatService::getWebJssdkSign();
//        $this->assign('dat',$jssdk);
        return $this->fetch('openVip');
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
}