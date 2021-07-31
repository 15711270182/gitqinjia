<?php
namespace app\home\controller;
use think\Request;
use think\Config;
use think\Db;
use think\Cache;
use think\Controller;
use service\WechatService;
use service\DataService;
use WeChat\TransfersBank;
use WeChat\Contracts\Tools;

use think\facade\Session;

class Test extends controller
{
    /**
     * 获取落地域名
     */
    public function index()
    {

    	$out_trade_no   = 'th122345661117';
        $project_no     = 10001;
        $amount         = 1;
        $redirect_url   = "http://face.xiaovi.xyz/home/index/index";
       	$str = "amount=".$amount."&out_trade_no=".$out_trade_no."&project_no=".$project_no."&redirect_url=".$redirect_url;
        $str = $str."&key="."66296693a70bf841f763dfd154794cd1";
        $sign = md5($str);  
        $url = "http://unionpay.v1kj.cn/index/index/unionpay?sign=".$sign."&amount=".$amount."&out_trade_no=".$out_trade_no."&project_no=".$project_no."&redirect_url=".urlencode($redirect_url);
        header("Location:".$url);  
                exit; 
        
    }

    /**
     * 获取授权域名
     */
    public function payback()
    {
    	$param = array();
        $param['out_trade_no']  = input('param.out_trade_no');
        $param['project_no']    = "10001";
        $param['amount']        = input('param.amount');
        $param['status']        = 1;
        $param['sign']        = input('param.sign');
        custom_log('pay_back',$param['sign'] );
        if ($param['status'] !== 1) 
        {
        	echo fail;exit;
        }
        $str = "amount=".$param['amount']."&out_trade_no=".$param['out_trade_no']."&project_no=".$param['project_no']."&status=".$param['status'];
        $str                    = $str."&key=66296693a70bf841f763dfd154794cd1";
        $str         = md5($str);
        if ($param['sign'] != $str) 
        {
        	custom_log('pay_back',"验签失败！" );
        	echo fail;exit;
        }

        if ($param['status'] == 1) 
        {
            custom_log('pay_back','开始修改订单数据===');
            
            $map = array();
            $map['order_num'] = $param['out_trade_no'];
            $order = db('payinfo')->where($map)->find();
            custom_log('pay_message','开始修改订单数据==='.$order['id']);


            
            //验证支付金额 
            if((int)$param['amount']!=(int)($order['total_fee'])){
                custom_log('validPay','无效订单==='.print_r($order,true));
                return xml(['return_code' => 'FAIL', 'return_msg' => '未知错误！']);
                /*if(!array_key_exists('coupon_count', $notifyInfo)){
                }*/
            }
             custom_log('pay_message','开始修改订单数据===11111');
            
            if ($order['trade_state'] == 1)
            {
                return xml(['return_code' => 'SUCCESS', 'return_msg' => '订单已更新，无需继续更新']);exit;
            }
         

            $map = array();
            $map['order_num'] = $param['out_trade_no'];
            $data = array();
            $data['trade_state'] = 1;
            $data['pay_time'] = time();
            $res = db('payinfo')->where($map)->update($data);
            if ($order['pay_for'] == "购买鼻子报告") 
            {
                $map =  array();
                $map['reportid'] = $order['report_id'];
                $data = array();
                $data['is_get_nose'] = 1;
                db('report')->where($map)->update($data);

            }elseif ($order['pay_for'] == "事业运程") 
            {
                $map =  array();
                $map['reportid'] = $order['report_id'];
                $data = array();
                $data['is_buy_report1'] = 1;
                db('report')->where($map)->update($data);
            }elseif($order['pay_for'] == "感情运程") 
            {
                $map =  array();
                $map['reportid'] = $order['report_id'];
                $data = array();
                $data['is_buy_report2'] = 1;
                db('report')->where($map)->update($data);
            }elseif($order['pay_for'] == "财富运程") 
            {
                $map =  array();
                $map['reportid'] = $order['report_id'];
                $data = array();
                $data['is_buy_report3'] = 1;
                db('report')->where($map)->update($data);
            }

            custom_log('pay_message','本地订单修改结果==='.print_r($res,true));

            if (!$res)
            {
                return xml(['return_code' => 'FAIL', 'return_msg' => '修改订单失败']);                
            }

            custom_log('pay_message','处理微信返回的数据，并序列化==='.print_r(json_encode($param),true));
            
            
            //修改专栏或测评销量
           
            
            return xml(['return_code' => 'SUCCESS', 'return_msg' => '处理成功！']);
        }
    	
        
    }

    /**
     * 获取落地域名
     */
    public function delfile()
    {
        $file = env('root_path')."/vendor/zoujingli/wechat-developer/WeChat/Cache/wxdc005bcf898174ae_ticket_jsapi";
        $res = unlink($file);
        $file = env('root_path')."/vendor/zoujingli/wechat-developer/WeChat/Cache/wxdc005bcf898174ae_access_token";
        $res = unlink($file);
        dump($res);exit;

    }

     /**
     * 获取落地域名
     */
    public function sendmess()
    {
        $openid = ['oZvi75v4M6MzzIq2bv4lK2Ao4j3s','oZvi75v463otvNUtBIvJtP4IWqfc','oZvi75n9qbI4Ycly6-U2RtlTlp4c','oZvi75mbwd6vzgMtqT3rSbxhBAw8'];
        foreach ($openid as $key => $value) 
        {
            $url = "http://notify.v1kj.cn/home/msg/mynotice?openid=".$value."&mold=0";
            $res = file_get_contents($url);
            dump($url);
            dump($res);
        }
        
        dump($res);exit;
    }

    /**
     * 获取授权域名
     */
    public function deldel()
    {
        $map = array();
        $map['nickname'] = "智伟";
        $res = db('userinfo')->where($map)->delete();
        dump(cookie('openid'));
        cookie('openid',null);
        cookie('userid',null);
        if (cookie('openid'))
        {
            echo "删除失败！";
        }else
        {
            echo "删除成功！！";
        }
    }

    /**
     * 获取落地域名
     */
    public function checkorder()
    {
        $userid = 39;
        $map = array();
        $map['userid'] = $userid;
        $map['trade_state'] = 0;
        $map['pay_type'] = 1;
        $time = time()-3600;
        $where = "addtime >= ".$time;
        $list = db('payinfo')->where($map)->where($where)->select();
        dump($list);
        foreach ($list as $key => $value) 
        {
            $options = [
            'out_trade_no'             => $value['order_num']
            ];
            $result = WechatService::pay()->queryOrder($options);
            dump($result);exit;
        }
        

    }

    /**
     * 获取落地域名
     */
    public function paybank()
    {
        $data = array();
        $data['amount'] = 100;
        $data['bank_code'] = 1001;
        $data['desc'] = "test";
        $data['enc_bank_no'] = "6214850252829228";
        $data['enc_true_name'] = "康金金";
  
        $data['partner_trade_no'] = time();
        $options = array();
        $options['appid'] = "wxdc005bcf898174ae";
        $options['mch_id'] = "1503865101";
        $options['mch_key'] = "8e0b4e036b6e3ffd9c4b57b7e36086ab";
        $option['ssl_p12'] = "/www/wwwroot/mianxiang/config/answer_cert/face/apiclient_cert.p12";
        $option['ssl_cer'] = "/www/wwwroot/mianxiang/config/answer_cert/face/apiclient_cert.pem";
        $option['ssl_key'] = "/www/wwwroot/mianxiang/config/answer_cert/face/apiclient_key.pem";
        
        $bank = new TransfersBank($options);
    
        
        $res  = $bank->create($data);
        // dump($res
        dump($res);exit;
           
        

    }


   

   


  

  
}
