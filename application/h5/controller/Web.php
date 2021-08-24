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
        $paytype = Db::name('userinfo')->where(['id' => $uid])->value('paytype');
        $field = 'id,title,type,num,price,create_at,discount,old_price';
        $product = ProductModel::productSelect(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        // 折算到每天是多少钱
        foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
        }
        if($paytype == 1){ //会员
            $this->assign('list',$product);
            $this->assign('paytype',$paytype);
            return $this->fetch('openVip');
        }
        $this->assign('paytype',$paytype);
        $this->assign('list',$product);
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
}