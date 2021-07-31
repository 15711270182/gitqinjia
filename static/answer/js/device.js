

(function() {
	if(goPAGE() && is_weixin()){		
		return false;
	}else{
		if(!goPAGE()){			
			console.log("非移动设备！");
		}
		if(!is_weixin()){
			console.log("非微信！");
		}
		window.location.href = "http://understand.wfozlo84237.cn/index/answer/noWeixin.html";
	}
  
})();

//判断设备
function goPAGE() {
    if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
        /*window.location.href="你的手机版地址";*/
		return true;       
    } else {
        /*window.location.href="你的电脑版地址";*/
		return false;       
    }
}

//判断是否是微信浏览器的函数
function is_weixin(){  
    var ua = navigator.userAgent.toLowerCase();  
    if(ua.match(/MicroMessenger/i)=="micromessenger") {  
        return true;  
    } else {  
        return false;  
    }  
}  



