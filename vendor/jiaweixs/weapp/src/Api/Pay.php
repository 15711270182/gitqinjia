<?php
/**
 * Created by PhpStorm.
 * User: Jiawei
 * Date: 2017/7/29
 * Time: 21:18
 */

namespace JiaweiXS\WeApp\Api;


class Pay extends BaseApi
{   
    protected $mch_id;
    protected $key;
    protected $ssl_cer_path;
    protected $error;
//     protected $openid;
//     protected $out_trade_no;
//     protected $body;
//     protected $total_fee;
    
    /**
     * 
     * @param 商户号  $mch_id
     * @param 商户密钥 $key
     * @param 证书路径 $ssl_path
     * 2018年5月14日下午4:00:52 
     * liuxin 285018762@qq.com
     */
    public function __construct($mch_id, $key,$app_id,$appSecret,$ssl_path=null) {
        $this->appid  = $app_id;
        $this->secret  = $appSecret;
        $this->mch_id = $mch_id;
        $this->key = $key;
        if(!empty($ssl_path)){
            $this->ssl_cer_path = $ssl_path ;
        }
    }
    
    /**
     * 小程序支付
     * @param 订单号 $out_trade_no
     * @param 订单金额 $total_fee
     * @param 回调url $notify_url
     * @param openid $openid
     * 2018年5月14日下午3:58:07 
     * liuxin 285018762@qq.com
     */
    public function pay($out_trade_no,$total_fee,$notify_url,$openid,$body='默契考验') {
        //统一下单接口
        $return = $this->weixinapp($out_trade_no,$total_fee,$notify_url,$openid,$body);
        return $return;
    }
    
    
    //统一下单接口
    private function unifiedorder($out_trade_no,$total_fee,$notify_url,$openid,$body='默契考验') {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = array(
            'appid' => $this->appid, //小程序ID
            'body' => $body,
            'mch_id' => $this->mch_id, //商户号
            'nonce_str' => $this->createNoncestr(), //随机字符串
            'notify_url' => $notify_url, //通知地址  确保外网能正常访问
            'openid' => $openid, //用户id
            'out_trade_no'=> $out_trade_no,
            'spbill_create_ip' => '127.0.0.1', //终端IP
            //            'total_fee' => floatval(0.01 * 100), //总金额 单位 分
            'total_fee' => $total_fee,
            //            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //终端IP
            'trade_type' => 'JSAPI'//交易类型
        );
        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $res = $this->postXmlCurl($xmlData, $url, 60);
        $return = $this->xmlToArray($res);
        return $return;
    }
    
    
    
    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private  function postXmlCurl($xml, $url, $second=30, $useCert=false, $sslcert_path='', $sslkey_path='')
    {   
        
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
       
        if($useCert == true){
            if(stripos($url,"https://")!==FALSE){
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            }    else    {
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
            } 
            
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $sslcert_path);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $sslkey_path);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            $this->error = $error;
            return false;
        }
    }
    
    private  function arrayToXml($data, $content = '')
    {
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = 'item';
            $content .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $content .= self::_arr2xml($val);
            } elseif (is_string($val)) {
                $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
            } else {
                $content .= $val;
            }
            $content .= "</{$key}>";
        }
        return "<xml>" . $content . "</xml>";
    }
    
    
    //xml转换成数组
    private function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }
    
    
    //微信小程序接口
    private function weixinapp($out_trade_no,$total_fee,$notify_url,$openid,$body='小程序支付') {
        //统一下单接口
        $unifiedorder = $this->unifiedorder($out_trade_no,$total_fee,$notify_url,$openid,$body);
        if($unifiedorder['return_code']!='SUCCESS'||$unifiedorder['result_code']!='SUCCESS'){
            custom_log('payError',print_r($unifiedorder,true));
            $this->error = $unifiedorder['return_msg'].'，请查看相应日志';
            return false;
        }
        $prepay_id = $unifiedorder['prepay_id'];
        $parameters = array(
            'appId' => $this->appid, //小程序ID
            'timeStamp' => '' . time() . '', //时间戳
            'nonceStr' => $this->createNoncestr(), //随机串
            'package' => 'prepay_id=' . $prepay_id, //数据包
            'signType' => 'MD5'//签名方式
        );
        //签名
        $parameters['paySign'] = $this->getSign($parameters);

        return $parameters;
    }
    
    
    //作用：产生随机字符串，不长于32位
    private function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    
    //作用：生成签名
    private function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
       // echo $String;die();
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        
        return $result_;
    }
    
    
    ///作用：格式化参数，签名过程需要使用
    private function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
    /**
     * 获取微信支付通知
     * @return array
     * 
     */
    public function getNotify()
    {   
        $xml = file_get_contents('php://input');
        custom_log('notify','xml==='.$xml);
        if(!$xml){
            $this->error = 'post数据为空';
            return false;
        }
        $wx_back = $this->xmlToArray($xml);
        custom_log('notify','wx_back_array==='.print_r($wx_back,true));
        if(empty($wx_back)){
            custom_log('notify','xml数据解析错误');
            $this->error = 'xml数据解析错误';
            return false;
        }
        if($wx_back['return_code'] == 'FAIL'){
            custom_log('notify','$wx_back FAIL'.print_r($wx_back,true));
            $this->error = $wx_back;
            return false;
        }
        if($wx_back['result_code'] == 'FAIL'){
             $this->error = $wx_back;
             custom_log('notify','$wx_back FAIL'.print_r($wx_back,true));
            return false;
        }
        $wx_back_sign = $wx_back['sign'];
        unset($wx_back['sign']);
        $checkSign = $this->getSign($wx_back);
        if($checkSign != $wx_back_sign){
            $this->error = '签名失败';
            custom_log('notify','$wx_back FAIL'.print_r($wx_back,true));
            return false;
        }
        custom_log('notify','$wx_back success'.print_r($wx_back,true));
        return $wx_back;
    }
    
   /**
     * 申请退款
     * @param array $options
     * @return array
     * @throws InvalidResponseException
     */
    public function createRefund(array $options)
    {
       
        //total_fee
        $total_fee = $options['total_fee'];
        $refund_fee = $options['refund_fee'];
        $refund_desc = $options['refund_desc'];
        $orderNum = $options['orderNum'];
        $refundNum = $options['refundNum'];
             
        $parameters=array(
            'appid'=>$this->appid,//商户账号appid
            'mch_id'=> $this->mch_id,//商户号
            'nonce_str'=>$this->createNoncestr(),//随机字符串
            'out_refund_no'=> $refundNum,//退款订单号
            'sign_type' => 'MD5',
            'out_trade_no'=> $orderNum,//商户订单号
         
            'total_fee'=>$total_fee,//金额
            'refund_fee'=> $refund_fee,//金额
            'refund_desc'=> $refund_desc ,//退款详情信息
        );
       
        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    
        $ssl_path = $this->ssl_cer_path;
        // $ssl_path = '/www/web/littlemoqi/public_html/config/7021_cert';
        $result = $this->postXmlCurl($xmlData, $url,30,true,$ssl_path.'/apiclient_cert.pem',$ssl_path.'/apiclient_key.pem');
       
        $return=$this->xmltoarray($result);
        //返回来的结果
        return $return;
                
    }
    
    /**
     * 查询退款
     * @param array $options
     * @return array
     * @throws InvalidResponseException
     */
    public function queryRefund(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        return $this->callPostApi($url, $options);
    }


    /**
     * 企业付款到零钱带真实姓名
     * @param array $options
     * @return array
     * @throws InvalidResponseException
     */
    public function createTransfersnew(array $options,$name=FALSE)
    {    
        
        $amount = $options['amount'];
        $description = $options['desc'];
        $openid = $options['openid'];
        $orderNum = $options['orderNum'];
        $ip  = $options['ip'];
        $total_amount =  100* $amount;
        $desc =  $options['desc'];
        $rename =  $options['rename'];
        
        $parameters=array(
            'mch_appid'=>$this->appid,//商户账号appid
            'mchid'=> $this->mch_id,//商户号
            'nonce_str'=>$this->createNoncestr(),//随机字符串
            'partner_trade_no'=> $orderNum,//商户订单号
            'openid'=> $openid,//用户openid
            'check_name'=>'FORCE_CHECK',//校验用户姓名选项,
            're_user_name'=> $rename,//收款用户姓名
            'amount'=>$total_amount,//金额
            'desc'=> $desc,//企业付款描述信息
            'spbill_create_ip'=>$ip ,//Ip地址
        );
        if($name){
            $parameters['check_name'] = 'CHECK';
            $parameters['re_user_name'] = $name;
        }
         //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $url='https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers'; //调用接口
        
        $ssl_path = $this->ssl_cer_path;
        $result = $this->postXmlCurl($xmlData, $url,30,true,$ssl_path.'/apiclient_cert.pem',$ssl_path.'/apiclient_key.pem');
        $return=$this->xmltoarray($result);
        //返回来的结果
       return $return;
    }
    
    
    /**
     * 企业付款到零钱
     * @param array $options
     * @return array
     * @throws InvalidResponseException
     */
    public function createTransfers(array $options,$name=FALSE)
    {    
        
        $amount = $options['amount'];
        $description = $options['desc'];
        $openid = $options['openid'];
        $orderNum = $options['orderNum'];
        $ip  = $options['ip'];
        $total_amount =  100* $amount;
        $desc =  $options['desc'];
        
        $parameters=array(
            'mch_appid'=>$this->appid,//商户账号appid
            'mchid'=> $this->mch_id,//商户号
            'nonce_str'=>$this->createNoncestr(),//随机字符串
            'partner_trade_no'=> $orderNum,//商户订单号
            'openid'=> $openid,//用户openid
            'check_name'=>'NO_CHECK',//校验用户姓名选项,
            're_user_name'=> 'test',//收款用户姓名
            'amount'=>$total_amount,//金额
            'desc'=> $desc,//企业付款描述信息
            'spbill_create_ip'=>$ip ,//Ip地址
        );
        if($name){
            $parameters['check_name'] = 'CHECK';
            $parameters['re_user_name'] = $name;
        }
         //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $url='https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers'; //调用接口
        
        $ssl_path = $this->ssl_cer_path;
        $result = $this->postXmlCurl($xmlData, $url,30,true,$ssl_path.'/apiclient_cert.pem',$ssl_path.'/apiclient_key.pem');
        $return=$this->xmltoarray($result);
        //返回来的结果
       return $return;
    }
    
    /**
     * 查询企业付款到零钱
     * @param string $partner_trade_no 商户调用企业付款API时使用的商户订单号
     * @return array
     * @throws InvalidResponseException
     */
    public function queryTransfers($partner_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
        return $this->callPostApi($url, ['partner_trade_no' => $partner_trade_no], true);
    }
    
    public function getError() {
        return $this->error;
    }

}