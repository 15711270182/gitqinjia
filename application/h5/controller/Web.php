<?php


namespace app\h5\controller;

use app\api\model\Product as ProductModel;
use app\api_new\model\Order as OrderModel;
use app\api_new\model\User as UserModel;
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
    //公众号支付 次卡/月卡支付
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
        $field = 'id,title,type,num,price,create_at,discount,old_price,source';
        $product = ProductModel::productSelect(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        // 折算到每天是多少钱
        foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
            if($value['source'] == 2){
                unset($product[$key]);
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
    //支付认证诚意金 88元
    public function openAuth(){
        $uid = input('uid');
        $field = 'id,title,price';
        $product = ProductModel::productFind(['type'=>2,'source'=>2,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        //视频信息
        $map = [];
        $map['auth_status'] = 1;
        $map['status'] = 1;
        $map['is_del'] = 1;
        $map['is_ban'] = 1;
        $cList = Db::name('children')->field("uid,auth_status,video_url,video_cover_url")->where($map)->where("video_url <> ''")->order('id desc')->limit(4)->select();
        foreach ($cList as $key => $value) {
            $pare = UserModel::userFind(['id'=>$value['uid']],'realname,headimgurl');
            $cList[$key]['realname'] = $pare['realname']?$pare['realname'].'家长':'家长';
            $cList[$key]['headimgurl'] = $pare['headimgurl'];
        }

        //认证信息
        $map = [];
        $map['auth_status'] = 1;
        $map['status'] = 1;
        $map['is_del'] = 1;
        $map['is_ban'] = 1;
        $aList = Db::name('children')->field("uid")->where($map)->order('id desc')->limit(5)->select();
        foreach ($aList as $key => $value) {
            $pare = UserModel::userFind(['id'=>$value['uid']],'realname,headimgurl');
            $realname = $pare['realname']?$pare['realname'].'家长':'家长';
            $aList[$key]['realname'] = $realname.'已经完成了实名认证';
            $aList[$key]['headimgurl'] = $pare['headimgurl'];
        }
        $is_pay = 0; //未支付
        $oInfo = OrderModel::orderFind(['uid'=>$uid,'status'=>1,'source'=>2]);
        if($oInfo){
            $is_pay = 1; //已支付
        }
        $this->assign('is_pay',$is_pay);
        $this->assign('id',$product['id']);
        $this->assign('price',sprintf('%.2f',$product['price']/100));
        $this->assign('auth_list',$aList);
        $this->assign('video_list',$cList);
        
        return $this->fetch('openAuth');
    }

    public function stip(){
        $jssdk = WechatService::getWebJssdkSign();
        $jssdk['link'] = "pages/home/home";
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

    public function msg(){
         return $this->fetch('msg');
    }
    public function demo(){
         return $this->fetch('demo');
    }
}