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

namespace app\api\service;

use think\Db;
use think\Request;

/**
 * Class Send
 * @package app\store\controller\api
 */
class Send
{
    /**
     * 获取短信配置
     * @return array
     */
    private function config()
    {
        return config('send');
    }

    /**
     * 生成签名
     */
    public function signature($post_data,$type=0)
    {
        if($type == 1){ //铂金之恋
            $appid = config('send.bojin.appid');
            $appkey = config('send.bojin.appsecret');
        }else{ //完美亲家
            $appid = config('send.qinjia.appid');
            $appkey = config('send.qinjia.appsecret');
        }
        
        //整理生成签名所需参数
        $temp = $post_data;
        unset($temp['vars']);
        ksort($temp);
        reset($temp);
        $tempStr = "";
        foreach ($temp as $key => $value) {
            $tempStr .= $key . "=" . $value . "&";
        }
        $tempStr = substr($tempStr, 0, -1);
        //生成签名
        $post_data['signature'] = md5($appid . $appkey . $tempStr . $appid . $appkey);
        return $post_data;
    }
     /**
     * 生成时间戳
     */
    public function getTimestamp(){
        $url = config('send.main_url')."service/timestamp.json";
        //通过接口获取时间戳
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST           => 0
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        $timestamp = $output['timestamp'];
        return $timestamp;
    }

    /**
     * @Notes:发送短信
     * @Interface sendMsg
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function sendMsg($to,$project_id,$vars,$type=0){
        if($type == 1){ //铂金之恋
            $appid = config('send.bojin.appid');
            $appkey = config('send.bojin.appsecret');
        }else{ //完美亲家
            $appid = config('send.qinjia.appid');
            $appkey = config('send.qinjia.appsecret');
        }
        $m_url = config('send.main_url')."message/xsend.json";
        //通过接口获取时间戳
        $timestamp = self::getTimestamp();
        $post_data = [
            "appid"        => $appid,
            "to"           => $to,
            "project"      => $project_id,
            "timestamp"    => $timestamp,
            "sign_type"    => 'md5',
            "sign_version" => 2,
            "vars"         => $vars ,
        ];
        $post_data = self::signature($post_data,$appid,$appkey);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $m_url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $post_data
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
