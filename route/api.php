<?php
use think\facade\Route;

/**
 * AppApi
 */

Route::group('api/token',function(){
    Route::any('wxLogin', 'api/token/wxLogin'); //用户登录接口
    Route::any('checkLoginStatus', 'api/token/checkLoginStatus'); //检验用户登录状态
    Route::any('getPhone', 'api/token/getPhone'); //获取手机号

});

Route::group('api/index',function(){
    Route::any('home', 'api/index/home'); //首页登录推荐数据
    Route::any('getuserlist', 'api/index/getuserlist'); //首页未登录拉取用户信息

//    Route::any('childrenDetailsNologin', 'api/index/childrenDetailsNologin'); //获取资料详情页(未登录)
    Route::any('childrenDetails', 'api/index/childrenDetails'); //获取资料详情页
    Route::any('childrenDetailsNew', 'api/index/childrenDetailsNew'); //获取资料详情页 - 新版

    Route::any('SeeTel', 'api/index/SeeTel'); //查看号码
    Route::any('SeeTelNew', 'api/index/SeeTelNew'); //查看号码
    Route::any('OnclickTel', 'api/index/OnclickTel'); //查看号码前的信息
    Route::any('CheckTel', 'api/index/CheckTel'); //添加手机号发送验证码
    Route::any('CheckCode', 'api/index/CheckCode'); //验证码校验

    Route::any('getVideoList', 'api/index/getVideoList'); //获取视频列表
    // Route::any('getVideoInfo ', 'api/index/getVideoInfo'); //获取视频详情

    Route::any('shareInfo', 'api/index/shareInfo'); //获取分享海报图
    Route::any('ques', 'api/index/ques'); //获取常见问题
    Route::any('erwma', 'api/index/erwma'); //获取首页二维码地址
    Route::any('subRecord', 'api/index/subRecord'); //记录已订阅消息
});
//用户模块
Route::group('api/user',function(){
    Route::any('addChildren', 'api/user/addChildren'); //添加子女资料
    Route::any('meInformation', 'api/user/meInformation'); //获取我的页面用户信息
    Route::any('ChildrenInfo', 'api/user/ChildrenInfo'); //我的页面 - 编辑资料页面
    Route::any('ChildrenEdit', 'api/user/ChildrenEdit'); //我的页面-修改资料
    Route::any('editRemarks', 'api/user/editRemarks');  //编辑资料-填写其他信息

    Route::any('msgList', 'api/user/msgList'); //获取 1 我收藏的  2 收藏我的 3联系人 列表
    Route::any('msgDel', 'api/user/msgDel'); //消息各列表 注销用户删除功能
    Route::any('collection', 'api/user/collection'); //收藏与取消收藏
    Route::any('cancellation', 'api/user/cancellation'); //用户撤销注销
    Route::any('shareList', 'api/user/shareList');//分享页面详情
    Route::any('report', 'api/user/report');//举报

    Route::any('shareDetails', 'api/user/shareDetails');//代理列表详细
    Route::any('msgCount', 'api/user/msgCount');//未读消息数

});
//订单模块
Route::group('api/order',function(){
    Route::any('getpayrecord', 'api/order/getpayrecord'); //获取最近购买会员记录
    Route::any('productList', 'api/order/productList'); //获取次卡月卡列表
    Route::any('makeorder', 'api/order/makeorder'); //生成订单
});

//红娘牵线模块
Route::group('api/Matchmaker',function(){
    Route::any('getNum', 'api/Matchmaker/getNum'); //静态页数据
    Route::any('getWxcode', 'api/Matchmaker/getWxcode'); //获取客服微信二维码图片
    Route::any('getUserList', 'api/Matchmaker/getUserList'); //筛选 获取嘉宾信息列表
    Route::any('getUserDetail', 'api/Matchmaker/getUserDetail'); //获取嘉宾详情信息
    Route::any('clickMatch', 'api/Matchmaker/clickMatch'); //点击牵线
    Route::any('matchRecord', 'api/Matchmaker/matchRecord'); //牵线记录列表

    Route::any('viewCount', 'api/Matchmaker/viewCount'); //记录页面浏览时长
});


