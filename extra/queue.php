<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'connector' => 'redis',
    "expire"=>60,//任务过期时间默认为秒，禁用为null
    "default"=>"default",//默认队列名称
    'host'       => '172.16.236.139',     // redis 主机ip
    'port'       => 6379,            // redis 端口
    'password'   => '',              // redis 密码
    "select"=>5,//Redis数据库索引
    "timeout"=>0,//Redis连接超时时间
    "persistent"=>false,//是否长连接
];
