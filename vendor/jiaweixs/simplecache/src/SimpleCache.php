<?php

/**
 * User: JiaweiXS
 * Date: 2017/7/29
 */

namespace JiaweiXS;

class SimpleCache
{
    private static $cacheTime;
    private static $appid;


    public static function init($appid,$cache_time=600){
        self::$appid = $appid;
        self::$cacheTime = $cache_time;
    }

    public static function get($key,$default=''){

        $data = self::readAndRender();
        self::checkTimeoutAndSave($data);

        if(isset($data[$key])){
            return $data[$key]['value'];
        }else{
            return $default;
        }
    }

    public static function set($key,$value,$time = false,$invalid = false){
        if(!$time) $time = self::$cacheTime;

        $data = self::readAndRender();
        $data[$key] = ['value'=>$value,'time'=>time()+$time];
        if($invalid){
            $data[$key] = ['value'=>$value,'time'=>$time];
        }
        return self::checkTimeoutAndSave($data);
    }

    private static function readAndRender(){
        $appid =  self::$appid;
        $json = cache('TOEKN_'.$appid);
        $data = json_decode($json,true);
        if(!is_array($data)){
            $data = [];
        }
        return $data;
    }

    private static function checkTimeoutAndSave(&$data){
        $cur_time = time();
        foreach($data as $k=>$v){
            if($cur_time>$data[$k]['time']){
                unset($data[$k]);
            }
        }
        $content = json_encode($data);
        if(cache('TOEKN_'.self::$appid,$content)){
            return true;
        }else{
            return false;
        }
    }
}
