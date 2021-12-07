<?php
use think\facade\Route;

/**
 * Appapi_new
 */

Route::group('api_new/token',function(){
    Route::any('wxLogin', 'api_new/token/wxLogin'); //用户登录接口
    Route::any('checkLoginStatus', 'api_new/token/checkLoginStatus'); //检验用户登录状态
    Route::any('getPhone', 'api_new/token/getPhone'); //获取手机号

});

Route::group('api_new/index',function(){
    Route::any('home', 'api_new/index/home'); //首页登录推荐数据
    Route::any('getuserlist', 'api_new/index/getuserlist'); //首页未登录拉取用户信息

    Route::any('childrenDetailsNew', 'api_new/index/childrenDetailsNew'); //获取资料详情页 - 新版

    Route::any('SeeTelNew', 'api_new/index/SeeTelNew'); //查看号码
    Route::any('OnclickTel', 'api_new/index/OnclickTel'); //查看号码前的信息
    Route::any('CheckTel', 'api_new/index/CheckTel'); //添加手机号发送验证码
    Route::any('CheckCode', 'api_new/index/CheckCode'); //验证码校验

    Route::any('getVideoList', 'api_new/index/getVideoList'); //获取视频列表
    // Route::any('getVideoInfo ', 'api_new/index/getVideoInfo'); //获取视频详情

    Route::any('shareInfo', 'api_new/index/shareInfo'); //获取分享海报图
    Route::any('ques', 'api_new/index/ques'); //获取常见问题
    Route::any('erwma', 'api_new/index/erwma'); //获取首页二维码地址
    Route::any('subRecord', 'api_new/index/subRecord'); //记录已订阅消息
});
//用户模块
Route::group('api_new/user',function(){
    Route::any('addChildren', 'api_new/user/addChildren'); //添加子女资料
    Route::any('meInformation', 'api_new/user/meInformation'); //获取我的页面用户信息
    Route::any('ChildrenInfo', 'api_new/user/ChildrenInfo'); //我的页面 - 编辑资料页面
    Route::any('ChildrenEdit', 'api_new/user/ChildrenEdit'); //我的页面-修改资料
    Route::any('editRemarks', 'api_new/user/editRemarks');  //编辑资料-填写其他信息

    Route::any('msgList', 'api_new/user/msgList'); //获取 1 我收藏的  2 收藏我的 3联系人 列表
    Route::any('msgDel', 'api_new/user/msgDel'); //消息各列表 注销用户删除功能
    Route::any('collection', 'api_new/user/collection'); //收藏与取消收藏
    Route::any('cancellation', 'api_new/user/cancellation'); //用户撤销注销
    Route::any('shareList', 'api_new/user/shareList');//分享页面详情
    Route::any('report', 'api_new/user/report');//举报

    Route::any('shareDetails', 'api_new/user/shareDetails');//代理列表详细
    Route::any('msgCount', 'api_new/user/msgCount');//未读消息数

    Route::any('identityAuth', 'api_new/user/identityAuth');//身份信息认证
    Route::any('faceAuth', 'api_new/user/faceAuth');//人脸认证结果


});
//订单模块
Route::group('api_new/order',function(){
    Route::any('getpayrecord', 'api_new/order/getpayrecord'); //获取最近购买会员记录
    Route::any('productList', 'api_new/order/productList'); //获取次卡月卡列表
    Route::any('makeorder', 'api_new/order/makeorder'); //生成订单

    Route::any('getauthinfo', 'api_new/order/getauthinfo'); //获取88支付页面数据

});

//红娘牵线模块
Route::group('api_new/Matchmaker',function(){
    Route::any('getNum', 'api_new/Matchmaker/getNum'); //静态页数据
    Route::any('getWxcode', 'api_new/Matchmaker/getWxcode'); //获取客服微信二维码图片
    Route::any('getUserList', 'api_new/Matchmaker/getUserList'); //筛选 获取嘉宾信息列表
    Route::any('getUserDetail', 'api_new/Matchmaker/getUserDetail'); //获取嘉宾详情信息
    Route::any('clickMatch', 'api_new/Matchmaker/clickMatch'); //点击牵线
    Route::any('matchRecord', 'api_new/Matchmaker/matchRecord'); //牵线记录列表

    Route::any('viewCount', 'api_new/Matchmaker/viewCount'); //记录页面浏览时长
});

//意见反馈模块
Route::group('api_new/Feedback',function(){
    Route::any('add', 'api_new/Feedback/add'); //添加意见反馈
    Route::any('count', 'api_new/Feedback/count'); //获取意见反馈次数
});

