<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\api\controller;
use app\api\service\UsersService;
use JiaweiXS\WeApp\WeApp;
use app\wechat\service\WechatService;
use app\api\model\Relation;
use app\api\model\Order as OrderModel;
use app\api\model\Product as ProductModel;
use app\api\model\Children as ChildrenModel;
use app\api\model\User as UserModel;
use app\api\model\TelCollection as Tel;
use think\Db;
use think\Queue;

use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Order extends Base
{
    /**
     * @Notes:获取最近购买会员记录
     * @Interface getpayrecord
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function getpayrecord()
    {
        $list = db::name('userinfo')->where('id','<=',100)->orderRaw('rand()')->field('nickname,headimgurl')->limit(5)->select();

        return $this->successReturn($list,'成功',self::errcode_ok);

    }
    /**
     * @Notes:次卡月卡列表
     * @Interface productList
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function productList()
    {
        $uid = $this->uid;
        $paytype = input("paytype", '', 'htmlspecialchars_decode');
        if(empty($paytype)){
           return $this->errorReturn(self::errcode_fail,'paytype参数不能为空');
        }
        $field = 'id,title,type,num,price,create_at,discount,old_price';
        $product = ProductModel::productSelect(['type'=>$paytype,'is_show'=>'1','is_del'=>'1'],$field,'sort desc');
        if(empty($product)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        // 折算到每天是多少钱
        foreach ($product as $key => $value) {
            $product[$key]['day_price'] = round($value['price']/$value['num']/100, 1);
            if($paytype == 1){
                $product[$key]['month_price'] = round($value['price']/($value['num']/30)/100, 1);
            }
        }
        $map = array();
        $map['uid'] = $uid;
        $children = ChildrenModel::childrenFind($map);
        $list = [
            'sex'=>$children['sex'],
            'data'=>$product
        ];
        return $this->successReturn($list,'成功',self::errcode_ok);
    }

    /**
     * @Notes:生成订单
     * @Interface makeorder
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function makeorder()
    {
        $uid = $this->uid;
        $type = input("type", '', 'htmlspecialchars_decode');
        $order_num = 'xthl_' . time() . createRandStr(8);
        $lockInfo = lock('orderpay_'.$uid);
        if($lockInfo == false){
            return $this->errorReturn(self::errcode_fail,'正在支付中,请勿频繁操作');
        }
        $map['id'] = $type;
        $product = ProductModel::productFind($map);
        if(!$product){
            return $this->errorReturn(self::errcode_fail,'商品不存在!');
        }
        $data['order_number'] = $order_num;
        $data['uid'] = $uid;
        $data['goods_id'] = $type;
        $data['payment'] = $product['price'];
        $data['create_at'] = time();
        $data['pay_time'] = time();
        $orderres = OrderModel::orderAdd($data);
        if(!$orderres){
            return $this->errorReturn(self::errcode_fail,'订单生成失败!');
        }
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $openid = $userinfo['openid'];
        $notify_url = 'https://qin.njzec.com/api/order/orderNotify';
        $options = [
            'body' => '充值',
            'out_trade_no' => $order_num,
            'total_fee' => $data['payment'],
            'openid' => $openid,
            'trade_type' => 'JSAPI',
            'notify_url' =>  $notify_url,
            'spbill_create_ip' => request()->ip(),
        ];
        $pay = WechatService::WePayOrder(config('wechat.miniapp'));
        // 生成预支付码
        $result = $pay->create($options);
        // 创建JSAPI参数签名
        $options = $pay->jsapiParams($result['prepay_id']);
        if($options){
            return $this->successReturn($options,'成功',self::errcode_ok);
        } else {
            return $this->errorReturn(self::errcode_fail,'生成订单失败');
        }
    }

    /**
     * @Notes:订单支付回调方法
     * @Interface orderNotify
     * @return \think\response\Xml
     * @throws \think\Exception
     * @author: zy
     * @Time: 2021/07/22
     */
    public function orderNotify()
    {
        $pay = WechatService::WePayOrder(config('wechat.miniapp'));
        $notifyInfo = $pay->getNotify();
        custom_log('orderH5Notify','支付回调结果'.print_r($notifyInfo,true));
        if(!$notifyInfo){
            return xml(['return_code' => 'FAIL', 'return_msg' => '未知错误！']);
        };
        //支付通知数据获取成功
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS')
        {
            $order_number = $notifyInfo['out_trade_no'];
            $o_data['order_number'] = $order_number;

            $orderInfo = OrderModel::orderFind($o_data);
            if(empty($orderInfo)){
                return 'FAIL';
            }
            if($orderInfo['status']){
                return 'FAIL';
            }

            $total_fee = $notifyInfo['total_fee'];
            //核实用户支付金额
            if($total_fee!=$orderInfo['payment']){
                return 'FAIL';
            }
           //修改订单状态
            OrderModel::orderEdit($o_data,['status'=>1,'pay_time'=>time()]);
            //根据订单类型
            $map = array();
            $map['id'] = $orderInfo['goods_id'];
            $goods = ProductModel::productFind($map);
            custom_log('teet',$goods['type']);
            if ($goods['type'] == 1)
            {
                //购买会员 增加会员时间
                $map = array();
                $map['id'] = $orderInfo['uid'];
                $user = UserModel::userFind($map);
                if ($user['endtime'] <= time())
                {
                    $time = time()+$goods['num']*24*3600;
                }else
                {
                    $time = $user['endtime']+$goods['num']*24*3600;
                }
                custom_log('teet',$time);
                $data = array();
                $data['is_vip'] = 1;
                $data['endtime'] = $time;
                UserModel::userEdit($map,$data);
            }else
            {
                //增加次数
                $map = array();
                $map['id'] = $orderInfo['uid'];
                $res = UserModel::getuserInt($map,'count',$goods['num']);
                if($res){
                    //添加增加记录
                    $params = [
                        'uid' => $orderInfo['uid'],
                        'type' => 1,
                        'count' => $goods['num'],
                        'remarks' => '充值次卡获得次数'.$goods['num'].'次',
                        'create_at' => time()
                    ];
                    Tel::tcountAdd($params);
//                    Db::name('tel_count')->strict(false)->insertGetId($params);
                }
                custom_log('payorder','支付'.print_r($res,true));
            }
            //判断该用户是否有邀请人 如有 奖励邀请人40% 添加明细
            $rInfo = Relation::relationFind(['uid'=>$orderInfo['uid']]);
            custom_log('test111','代理id'.print_r($rInfo,true));
            if(!empty($rInfo)){
                //添加明细
                $awards_money = $orderInfo['payment']*0.4;
                $nickname = UserModel::userValue(['id'=>$orderInfo['uid']],'nickname');
                $remark = '好友'.$nickname.'支付'.($orderInfo['payment']/100).'元,获得奖励'.($awards_money/100).'元' ;
                $aw_add['uid'] = $orderInfo['uid'];
                $aw_add['bid'] = $rInfo['bid'];
                $aw_add['oid'] = $orderInfo['id'];
                $aw_add['pay_time'] = date('Y-m-d H:i:s',$orderInfo['pay_time']);
                $aw_add['total_money'] = $orderInfo['payment'];
                $aw_add['awards_money'] = $awards_money;
                $aw_add['order_type'] = $goods['type'];
                $aw_add['create_time'] = date('Y-m-d H:i:s');
                $aw_add['remark'] = $remark;
                DB::name('invite_awards')->insertGetId($aw_add);
                DB::name('children')->where(['uid'=>$rInfo['bid']])->setInc('balance',$awards_money);
            }
            
            return xml(['return_code' => 'SUCCESS', 'return_msg' => '处理成功！']);
        }
        return xml(['return_code' => 'FAIL', 'return_msg' => '未知错误！']);
    }
}
