<?php

/**
 * 官网API
 */

use think\facade\Route;

Route::group('website',function () {
    Route::post('index/getCertificationInfo', 'website/index/getCertificationInfo');//获取认证信息
    Route::post('index/getResults', 'website/index/getResults');//获取脱单成果
    Route::post('index/signUp', 'website/index/signUp');//报名

});