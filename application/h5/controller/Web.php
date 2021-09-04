<?php


namespace app\h5\controller;

use app\api\model\Product as ProductModel;
use think\Db;
use think\Controller;
use app\wechat\service\WechatService;
/**
 * 应用入口控制器
 * h5管理器
 * @author Anyon <zy>
 */
class Web extends Controller
{
    public function openVip(){
        $uid = input('uid');
        $userinfo = Db::name('userinfo')->where(['id' => $uid])->find();
        $paytype = $userinfo['paytype'];
        $realname = $userinfo['realname'];
        $headimgurl = $userinfo['headimgurl'];
        $headimgurl = !empty($headimgurl)?$headimgurl:'https://pics.njzec.com/default.png';
        $sex = Db::name('children')->where(['uid' => $uid])->value('sex');
        if (empty($realname)) {
            $name = '家长';
        }else{
            $name = $realname.'家长';
        }
        if(empty($sex)){
            $sex = 1;
        }
        $field = 'id,title,type,num,price,create_at,discount,old_price';
        $product = ProductModel::productSelect(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        // 折算到每天是多少钱
        foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
        }
        $this->assign('sex',$sex);
        $this->assign('paytype',$paytype);
        $this->assign('realname',$name);
        $this->assign('headimgurl',$headimgurl);
        $this->assign('list',$product);
        if($paytype == 1){ //会员
            return $this->fetch('openVip');
        }
        return $this->fetch('openCount');
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

    public function send(){
         return $this->fetch('send');
    }
}