(function(win, doc){
    function setFontSize() {
        // 获取window 宽度
        // zepto实现 $(window).width()就是这么干的
        var docEl = doc.documentElement;
        var clientWidth = docEl.clientWidth;
        if(clientWidth<=640){
            docEl.style.fontSize = 30 * (clientWidth / 640) + 'px';
        }else{
            docEl.style.fontSize =30 +"px";
        }
    }
    var resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize';
    var timer = null;
    win.addEventListener(resizeEvt, function () {
        clearTimeout(timer);
        timer = setTimeout(setFontSize, 300);
    }, false);
    win.addEventListener("pageshow", function(e) {
        if (e.persisted) {
            clearTimeout(timer);
            timer = setTimeout(setFontSize, 300);
        }
    }, false);
    // 初始化
    setFontSize();
}(window, document));

