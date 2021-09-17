<?php


namespace app\web\controller;

use think\Controller;
use think\Db;

/**
 * Class 短链接获取长链接类
 * @package app\web\controller
 */
class Api extends Controller
{
    //统一获取长链接方法
    public function longUrl(){
        $map['short_url'] = $_GET['code'];
        var_dump($map);die;
        $data = DB::name('long_short_url')->where($map) ->find();
        $url = $data['long_url'];
        header("location:$url");
    }
    /**
     * 由长连接生成短链接操作
     *
     * 算法描述：使用6个字符来表示短链接，我们使用ASCII字符中的'a'-'z','0'-'9','A'-'Z'，共计62个字符做为集合。
     *           每个字符有62种状态，六个字符就可以表示62^6（56800235584），那么如何得到这六个字符，
     *           具体描述如下：
     *        1. 对传入的长URL+设置key值 进行Md5，得到一个32位的字符串(32 字符十六进制数)，即16的32次方；
     *        2. 将这32位分成四份，每一份8个字符，将其视作16进制串与0x3fffffff(30位1)与操作, 即超过30位的忽略处理；
     *        3. 这30位分成6段, 每5个一组，算出其整数值，然后映射到我们准备的62个字符中, 依次进行获得一个6位的短链接地址。
     *
     */
    public function shortUrl($long_url)
    {
        error_reporting(E_ALL);
        ini_set('display_errors','on');
        $key = 'v1kj008'; //自定义key值
        $base32 = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        // 利用md5算法方式生成hash值
        $hex = hash('md5', $long_url.$key);
        $hexLen = strlen($hex);
        $subHexLen = $hexLen / 8;
        $output = array();
        for( $i = 0; $i < $subHexLen; $i++ )
        {
            // 将这32位分成四份，每一份8个字符，将其视作16进制串与0x3fffffff(30位1)与操作
            $subHex = substr($hex, $i*8, 8);
            $idx = 0x3FFFFFFF & (1*(filter_var('0x'.$subHex, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX)));
//            var_dump(0x3FFFFFFF & (1*(0x5fcaeeb1)));die;
            // 这30位分成6段, 每5个一组，算出其整数值，然后映射到我们准备的62个字符
            $out = '';
            for( $j = 0; $j < 6; $j++ )
            {
                $val = 0x0000003D & $idx;
                $out .= $base32[$val];
                $idx = $idx >> 5;
            }
            $output[$i] = $out;
        }
        return $output;
    }


}