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

namespace app\api_new\controller;
use app\api_new\service\UsersService;
use JiaweiXS\WeApp\WeApp;
use app\wechat\service\WechatService;
use app\api_new\model\Relation;
use app\api_new\model\Order as OrderModel;
use app\api_new\model\Product as ProductModel;
use app\api_new\model\Children as ChildrenModel;
use app\api_new\model\User as UserModel;
use app\api_new\model\TelCollection as Tel;
use app\api_new\service\ScoreService;
use think\Db;
use think\Queue;

use think\facade\Cache;
/**
 * 牵线支付控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Orderhnqx extends Base
{
    /**
     * @Notes:生成订单 h5
     * @Interface makeorderh5
     * @return string
     * @author: zy
     * @Time: 2021/08/24
     */
    public function makeorderh5()
    {
        $distcount_id = input("distcount_id", '', 'htmlspecialchars_decode') ?: '';
        $uid = input('uid');
        if (empty($uid)) return $this->errorReturn(self::errcode_fail,'uid参数错误');
        $openid = input('openid', '', 'htmlspecialchars_decode');//公众号的openid
        if (empty($openid)) return $this->errorReturn(self::errcode_fail,'openid参数错误');
//        $price = input('activity_price', '', 'htmlspecialchars_decode');
        $price = input('price', '', 'htmlspecialchars_decode');
        if (empty($price)) return $this->errorReturn(self::errcode_fail,'price参数错误');

        $order_num = 'xthl_' . time() . createRandStr(8);
        $lockInfo = lock('hnqxpay_'.$uid);
        if($lockInfo == false){
            return $this->errorReturn(self::errcode_fail,'操作过于频繁,请稍后重试!');
        }
//        $priceInfo = getDisPrice($uid);
//        $activity_price = $priceInfo['activity_price'];
//        if($activity_price != $price){
//            return $this->errorReturn(self::errcode_fail,'支付价格与实际价格不符');
//        }
        $data['order_number'] = $order_num;
        $data['uid'] = $uid;
        $data['distcount_id'] = $distcount_id;
        $data['payment'] = $price*100;
        $data['create_at'] = time();
        $data['pay_time'] = time();
        $data['source'] = 3;
        $orderres = OrderModel::orderAdd($data);
        if(!$orderres){
            return $this->errorReturn(self::errcode_fail,'订单生成失败!');
        }
        $notify_url = 'https://testqin.njzec.com/api_new/orderhnqx/orderNotify';
        //微信支付数据  请求统一下单接口
        $options = [
            'body' => '充值',
            'out_trade_no' => $order_num,
            'total_fee' => $data['payment'],
            'openid' => $openid,
            'trade_type' => 'JSAPI',
            'notify_url' =>  $notify_url,
            'spbill_create_ip' => request()->ip(),
        ];
        $pay = WechatService::WePayOrder(config('wechat.wechat'));
        // 生成预支付码
        $result = $pay->create($options);
        // 创建JSAPI参数签名
        $options = $pay->jsapiParams($result['prepay_id']);

        return $this->successReturn($options,'成功',self::errcode_ok);
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
            $data = [];
            $data['is_pair_vip'] = 1;
            //1元，1条红线，有效期3天  1599元，10条红线，有效期6个月  1999元，20条红线，有效期12个月
            // if($orderInfo['payment'] == 100){
            //     $time = time() + 3*24*3600;
            //     $pair_last_num = 1;
            // }elseif($orderInfo['payment'] == 159900){
            //     $time = time() + 30*6*24*3600;
            //     $pair_last_num = 10;
            // }else{
            //     $time = time() + 30*12*24*3600;
            //     $pair_last_num = 20;
            // }
            // $data['pair_vip_time'] = date('Y-m-d H:i:s',$time);
            // $data['pair_last_num'] = $pair_last_num;

            // UserModel::userEdit(['id'=>$orderInfo['uid']],$data);

            return xml(['return_code' => 'SUCCESS', 'return_msg' => '处理成功！']);
        }
        return xml(['return_code' => 'FAIL', 'return_msg' => '未知错误！']);
    }
}
