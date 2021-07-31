<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

return [
    'service_url' => 'https://service.thinkadmin.top',
    /*'appid'       => 'wx2d14aed06eaac512',
    'appsecret'   => '9fcddfc2bce1c37ea12da928640deb72',*/
    'appid'       => 'wxdc005bcf898174ae',
    'appsecret'   => '1887cec4c8e4288628d0dbe276520db6',
    'mch_id'      => '1459911802',
    'mch_key'     => '312f8526ebba8daf31d4c421aaa00783',
    // 小程序支付参数
    'miniapp'     => [
        'appid'      => 'wx70d65d2170dbacd7',
        'appsecret'  => 'ddf67bffae1d48e78a9c8b74be25bd01',
        'mch_id'     => '1610267514',
        'mch_key'    => 'CBDF911D317C03D8BA81EEFCF79F7AD3',
        'cache_path' => env('runtime_path') . 'wechat' . DIRECTORY_SEPARATOR,
        'url'        =>'https://api.weixin.qq.com/sns/jscode2session',
    ],
];