<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

use think\facade\Route;

/**
 * 婚恋API
 */
//==============================================================================================
Route::group('bojin_api',function () {
    Route::post('loveapi/marriageRegister', 'bojin_api/loveapi/marriageRegister');//抖音投放页h5相亲用户注册提交接口

});


/**
 * 官网API
 */
//Route::group('website',function () {
//    Route::post('index/getCertificationInfo', 'website/index/getCertificationInfo');//获取认证信息
//    Route::post('index/getResults', 'website/index/getResults');//获取脱单成果
//    Route::post('index/signUp', 'website/index/signUp');//报名
//
//});

/**
 * AppApi
 */

//Route::group('api/:version/token',function(){
//    Route::get('wxLogin', 'api/:version.token/wxLogin');
//    Route::get('test', 'api/:version.token/test');
//});





