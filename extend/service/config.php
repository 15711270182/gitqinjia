<?php
namespace service;
$config = array (	
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