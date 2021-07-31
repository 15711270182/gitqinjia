<?php
namespace service;
use think\Db;
use think\db\Query;
use service\AopSdk;
use alipay\AlipayTradeWapPayRequest;


class AlipayTradeService {

	//支付宝网关地址
	public $gateway_url = "https://openapi.alipay.com/gateway.do";

	//支付宝公钥
	public $alipay_public_key;

	//商户私钥
	public $private_key;

	//应用id
	public $appid;

	//编码格式
	public $charset = "UTF-8";

	public $token = NULL;
	
	//返回数据格式
	public $format = "json";

	//签名方式
	public $signtype = "RSA";

	function __construct(){
		$alipay_config = array (	
		//应用ID,您的APPID。
		'app_id' => "2018022702281408",

		//商户私钥，您的原始格式RSA私钥
		'merchant_private_key' => "MIIEogIBAAKCAQEAv+MdckJ/Mm9Vf5jk+sckmrm9aS8M6YNHPWC4yfGcralL7vfF0yr9pPLaTerQKHHJhoumTXKWkiIy/Yp1Do7Pvnv1wyM9ck0+w7VPUwY7ppmzWZKRgKY3aa/+0PoVx0jCqwP4ZJOh289GaBrXf6b57KFL5Z6g9jVtliXygQMVRjxnj7LHx2eFXfSQl8bQZ93qX5uWNE6rYx7NqLZ6Hz6IdiOFGKRKbkh4EnWJDNuqlhmCoO1oh/yYCfHbI7Mbd9ehHTzpIK1KSPi18jv9wh9r6gexqCUgRhvwB6y7EGFvgGMkZjMmP625gE1HKF+/7hKjPNIUWhTfnt0sf8iib2K6TQIDAQABAoIBAAQd3T3cS1pLpSvtncv7hb+ECJo/FinUVSzt7Ej41AGtxiFEU4wqOfLV+vT8+qZDeq1WRaUXtj9AWJOz6rr7OV2+zxD2qpTPL2+HbkI7uf/jAEQFrvVxm3K7Ad593wW9e9+rYCLYP/q1Qa9uE/17GZWICFbOxmlB0C4OdltqM4SkMZm7jL9lVNIikSaKyLxT+7jzLOzG/UMSnC3YCa8uitU30fYP/W06dfrTGdQkibSGQZPqw76d4d8dlwCNc9QFbuL8P25baFaAKw8VhZwNwKCFn87J65fI6Nf+Dk4lD0k2ItxCNlsV/OnIWDvCo46ciOxj3A6FkyoRlwGhfrjXUEECgYEA4SsEv9C/SaXOGEUF7aDwKE/cQ9Zu2ZqzEGm65D+RqpZHNp36dxgahhEJgCTc5T/mHWbnJiT90c5XFHxGYvXb/tBGZ4jfOtLxUIsLT+oKetbmIsDU578G5Hp0ICqsOaxk9VSxFe0o9ZYeCCIjoxyCMykK892OBL7/4394jTOg3FUCgYEA2il6O92EJIK7LL3TltSiu7Nz9vkE9Lr4cFOzNGwygibAaopUtOPbD/8KUppdHUS6elDnXizN44wuPJ0lRvzWUzP+cRiQBxorhwqW4URaAcac8j4IDITOSO3sFB+5UdGMjgpk0dDRPNsGdUOeMbY8ZytOJYPgfnHAr3hkaUquXhkCgYBMs+/RO9X9y5qST+j+EuXchZ/eCA0I2ZcID0xX9oOzna+ynkw1B6P5aZJX8bbB7WuBNo2lQ9KnBuhJFTCRA3mmquJg4JJSoosLyeHXnj1lrREGY7PjIgLCECjA0GiM2PonTGtqsbhTOIkQcji7lrmPnfqaKi331eyrXb/+MckpZQKBgHkOdwNtIfxYhqCHHTge+cYKCBlNiRB8B4vdBh3axBQwiKkV5XcS0OYJcaLwgSbSkl95MUmytvTDPozn7l17wzocKd578L/gJ7MhjyOlGATQPxq0jSbVMtqJG2z3RZA/JS1UWymKI/EO4ICFauzO4KmnABAVI6dGW9OCjMVYaXVRAoGAEeZN9g7GKWwrjPt2CdLjmDRxtPjw3g3q/B54LlY173rD4BXbFK+08oBLdWs5itDTr0iZeGUkx5zGXQSqns+zPr2tiuZ8XmZzV2wWOualNJld2MbQEZj5HffKbiGnPCNOx+GnYaH7KBOreYCMzJFPpAcIwAVQnfKl8X8XW+/t+OE=",

		
		//异步通知地址
		'notify_url' => "http://question.v1kj.cn/ali/wappay/notify_url.php",
		
		//同步跳转
		'return_url' => "http://question.v1kj.cn/ali/wappay/return_url.php",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAv+MdckJ/Mm9Vf5jk+sckmrm9aS8M6YNHPWC4yfGcralL7vfF0yr9pPLaTerQKHHJhoumTXKWkiIy/Yp1Do7Pvnv1wyM9ck0+w7VPUwY7ppmzWZKRgKY3aa/+0PoVx0jCqwP4ZJOh289GaBrXf6b57KFL5Z6g9jVtliXygQMVRjxnj7LHx2eFXfSQl8bQZ93qX5uWNE6rYx7NqLZ6Hz6IdiOFGKRKbkh4EnWJDNuqlhmCoO1oh/yYCfHbI7Mbd9ehHTzpIK1KSPi18jv9wh9r6gexqCUgRhvwB6y7EGFvgGMkZjMmP625gE1HKF+/7hKjPNIUWhTfnt0sf8iib2K6TQIDAQAB",
		);
		$this->gateway_url = $alipay_config['gatewayUrl'];
		$this->appid = $alipay_config['app_id'];
		$this->private_key = $alipay_config['merchant_private_key'];
		$this->alipay_public_key = $alipay_config['alipay_public_key'];
		$this->charset = $alipay_config['charset'];
		$this->signtype=$alipay_config['sign_type'];

		if(empty($this->appid)||trim($this->appid)==""){
			throw new Exception("appid should not be NULL!");
		}
		if(empty($this->private_key)||trim($this->private_key)==""){
			throw new Exception("private_key should not be NULL!");
		}
		if(empty($this->alipay_public_key)||trim($this->alipay_public_key)==""){
			throw new Exception("alipay_public_key should not be NULL!");
		}
		if(empty($this->charset)||trim($this->charset)==""){
			throw new Exception("charset should not be NULL!");
		}
		if(empty($this->gateway_url)||trim($this->gateway_url)==""){
			throw new Exception("gateway_url should not be NULL!");
		}

	}
	function AlipayWapPayService($alipay_config) {
		$this->__construct($alipay_config);
	}

	/**
	 * alipay.trade.wap.pay
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @param $return_url 同步跳转地址，公网可访问
	 * @param $notify_url 异步通知地址，公网可以访问
	 * @return $response 支付宝返回的信息
 	*/
	function wapPay($builder,$return_url,$notify_url) {
	
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
	
		$request = new AlipayTradeWapPayRequest();
	
		$request->setNotifyUrl($notify_url);
		$request->setReturnUrl($return_url);
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request,true);
		// $response = $response->alipay_trade_wap_pay_response;
		return $response;
	}

	 function aopclientRequestExecute($request,$ispage=false) {

		$aop = new AopClient ();
		$aop->gatewayUrl = $this->gateway_url;
		$aop->appId = $this->appid;
		$aop->rsaPrivateKey =  $this->private_key;
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$aop->apiVersion ="1.0";
		$aop->postCharset = $this->charset;
		$aop->format= $this->format;
		$aop->signType=$this->signtype;
		// 开启页面信息输出
		$aop->debugInfo=true;
		if($ispage)
		{
			$result = $aop->pageExecute($request,"post");
			echo $result;
		}
		else 
		{
			$result = $aop->Execute($request);
		}
        
		//打开后，将报文写入log文件
		$this->writeLog("response: ".var_export($result,true));
		return $result;
	}

	/**
	 * alipay.trade.query (统一收单线下交易查询)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
 	*/
	function Query($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeQueryRequest();
		$request->setBizContent ( $biz_content );

		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_query_response;
		var_dump($response);
		return $response;
	}
	
	/**
	 * alipay.trade.refund (统一收单交易退款接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Refund($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeRefundRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_refund_response;
		var_dump($response);
		return $response;
	}

	/**
	 * alipay.trade.close (统一收单交易关闭接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Close($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeCloseRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_close_response;
		var_dump($response);
		return $response;
	}
	
	/**
	 * 退款查询   alipay.trade.fastpay.refund.query (统一收单交易退款查询)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function refundQuery($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeFastpayRefundQueryRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		var_dump($response);
		return $response;
	}
	/**
	 * alipay.data.dataservice.bill.downloadurl.query (查询对账单下载地址)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function downloadurlQuery($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new alipaydatadataservicebilldownloadurlqueryRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_data_dataservice_bill_downloadurl_query_response;
		var_dump($response);
		return $response;
	}

	/**
	 * 验签方法
	 * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
	 * @return boolean
	 */
	function check($arr){
		$aop = new AopClient();
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$result = $aop->rsaCheckV1($arr, $this->alipay_public_key, $this->signtype);
		return $result;
	}
	
	//请确保项目文件有可写权限，不然打印不了日志。
	function writeLog($text) {
		// $text=iconv("GBK", "UTF-8//IGNORE", $text);
		//$text = characet ( $text );
		file_put_contents ( dirname ( __FILE__ ).DIRECTORY_SEPARATOR."./../../log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
	}
	

	/** *利用google api生成二维码图片
	 * $content：二维码内容参数
	 * $size：生成二维码的尺寸，宽度和高度的值
	 * $lev：可选参数，纠错等级
	 * $margin：生成的二维码离边框的距离
	 */
	function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
		$content = urlencode($content);
		$image = '<img src="http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content.'"  widht="'.$size.'" height="'.$size.'" />';
		return $image;
	}
}

?>