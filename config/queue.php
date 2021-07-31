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
    //Database驱动
    "connector"=>"Database",//数据库驱动
    "expire"=>60,//任务过期时间，单位为秒，禁用为null
    "default"=>"default",//默认队列名称
    "table"=>"jobs",//存储消息的表明，不带前缀
    "dsn"=>[],
];
