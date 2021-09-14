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
        $userinfo = Db::name('userinfo')->where(['id' => $uid])->find();
        $realname = $userinfo['realname'];
        $headimgurl = $userinfo['headimgurl'];
        $headimgurl = !empty($headimgurl)?$headimgurl:'https://pics.njzec.com/default.png';
        if (empty($realname)) {
            $name = '家长';
        }else{
            $name = $realname.'家长';
        }

        $base = new BaseController();
        $priceInfo = $base->getDisPrice($uid);

        $this->assign('realname',$name);
        $this->assign('headimgurl',$headimgurl);
        $this->assign('priceInfo',$priceInfo);

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