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
use think\Db;
use service\ToolsService;
use think\cache\driver\Redis;
/**
 * 打印输出数据到文件
 * @param mixed $data 输出的数据
 * @param bool $force 强制替换
 * @param string|null $file
 */
function p($data, $force = false, $file = null)
{
    is_null($file) && $file = env('runtime_path') . date('Ymd') . '.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
    $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
}

/**
 *
 * @param string $dir
 * @return boolean
 * 2018年12月26日下午8:37:33
 * liuxin 285018762@qq.com
 */
function mkDirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkDirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}

/**
 * 产生随机字符串
 * @param int $length 指定字符长度
 * @param int $type 生成类型
 * @param string $prefix 字符串前缀
 * @param string $postfix 字符串后缀
 * @return string
 */
function createRandStr($length = 32, $type = 0, $prefix = "", $postfix = '')
{
    switch ($type) {
        case 1:
            $chars = "abcdefghijklmnopqrstuvwxyz";
            break;
        case 2:
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            break;
        case 3:
            $chars = "0123456789";
            break;
        default:
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            break;
    }
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    $str = $prefix . $str . $postfix;

    return $str;
}
/**
 * 写日志文件
 *2016年12月28日上午10:04:53
 * @param unknown $filename
 * @param string $string
 * @author 285018762@qq.com <飞鱼>
 */
 function custom_log($filename, $string = null)
    {
        $strings = date('Y-m-d H:i:s', time()) . '  ' . $string . "\r";

        $real_filename = $filename . '-' . date('Y-m-d') . '.log';
        $dir = env('root_path') . '/Log/';
        if (!is_dir($dir)) {
            mkdir($dir, 0700);
        }
        $path = $dir . $real_filename;

        file_put_contents($path, $strings, FILE_APPEND);
        chmod($path, 0755);

    }
/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if (mb_strlen($str) > $length) {
        return $suffix ? $slice . '...' : $slice;
    }
    return $slice;
}

/**
 * 创建多级文件夹目录
 * @param unknown $path
 * 2018年6月13日下午2:36:54
 * liuxin 285018762@qq.com
 */
function createDirs($path)
{
    if (is_dir($path)) {
        return $path;
        //echo "抱歉，目录 " . $path . " 已存在！";
    } else {
        //第3个参数“true”意思是能创建多级目录，iconv防止中文目录乱码
        $res = mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        if ($res) {
            return $path;
        } else {
            return false;
        }
    }

}
/**
 * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
 * @param string $user_name 姓名
 * @return string 格式化后的姓名
 */
function substr_cut_name($user_name)
{
    $strlen = mb_strlen($user_name, 'utf-8');
    $firstStr = mb_substr($user_name, 0, 1, 'utf-8');
    $lastStr = mb_substr($user_name, -1, 1, 'utf-8');
    return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
}
/**
 * 验证手机号格式是否正确
 * @param string $mobile 手机号
 */
function preg_phone($mobile)
{
    if (preg_match("/^1[3456789]\d{9}$/ims", $mobile)) {
        return true;
    }
    return false;
}
 function curl_get($get_user_info_url){
     $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$get_user_info_url);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    $user_obj = json_decode($res,true);
     return $user_obj;
 }
/**
 * 读取/dev/urandom获取随机数
 * @param $len
 * @return mixed|string
 */
function randomFromDev($len) {
    $fp = @fopen('/dev/urandom','rb');
    $result = '';
    if ($fp !== FALSE) {
        $result .= @fread($fp, $len);
        @fclose($fp);
    }
    else
    {
        trigger_error('Can not open /dev/urandom.');
    }
    // convert from binary to string
    $result = base64_encode($result);
    // remove none url chars
    $result = strtr($result, '+/', '-_');

    return substr($result, 0, $len);

    return rand(0,99999999999);
}

/**
 * @Notes:将资源写入文件生成图片
 * @Interface createFile
 * @param $path
 * @param $filename
 * @param $img_content
 * @return bool|string
 * @author: LiYang
 * @Time: 2020/11/6   9:53
 */
function createFile($path, $filename, $img_content)
{
    if (empty($path) || empty($filename)) {
        return false;
    }
    if (empty($img_content)) {
        return false;
    }
    if (!is_dir($path)) {
        mkdir($path, 0700, true);
    }
    $path_file = $path . $filename . '.jpg';
    file_put_contents($path_file, $img_content);
    return $path_file;
}
/**
 * @Notes:截取中文字符串
 * @param $string
 * @param $start 截断开始处
 * @param $length，要截取的字数
 * @param $encoding ，网页编码，如utf-8,GB2312,GBK
 * @return bool|string
 * @Time: 2020/11/6   9:53
 */
function getString($string, $start, $length,$encoding)
{
    $str = mb_substr($string,$start,$length,$encoding);
   
    return $str;
}


function sendHttpRequest($url, $url_param = null, $body_param = null, $is_post = true)
{
    if ($url_param) {
        $url_param = '?' . http_build_query($url_param);
    }
    if ($body_param) {
        $body_param = json_encode($body_param, JSON_UNESCAPED_UNICODE);
    }
    $ch = curl_init($url . $url_param);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($is_post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body_param);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

//将生日转化为年龄
function getage($birthday){
     $age = strtotime($birthday);
     if($age === false){
      return false; 
     }
     list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
     $now = strtotime("now");
     list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
     $age = $y2 - $y1;
     if((int)($m2.$d2) < (int)($m1.$d1))
      $age -= 1;
     return $age;
}
/**
 * 根据时间戳计算星座
 * @param $time
 * @return mixed
 */
function get_constellation($time)
    {
        $time = strtotime($time);
        $y   = date("Y").'-';
        $his = ' 00:00:00';
        $birth_month = date("m", $time);
        $birth_date  = date("d", $time);

        $userTime = strtotime($y.$birth_month.'-'.$birth_date.$his);

        $januaryS   = strtotime($y.'01-20'.$his);
        $januaryE   = strtotime($y.'02-18'.$his);
        $februaryS  = strtotime($y.'02-19'.$his);
        $februaryE  = strtotime($y.'03-20'.$his);
        $marchS     = strtotime($y.'03-21'.$his);
        $marchE     = strtotime($y.'04-19'.$his);
        $aprilS     = strtotime($y.'04-20'.$his);
        $aprilE     = strtotime($y.'05-20'.$his);
        $mayS       = strtotime($y.'05-21'.$his);
        $mayE       = strtotime($y.'06-21'.$his);
        $juneS      = strtotime($y.'06-22'.$his);
        $juneE      = strtotime($y.'07-22'.$his);
        $julyS      = strtotime($y.'07-23'.$his);
        $julyE      = strtotime($y.'08-22'.$his);
        $augustS    = strtotime($y.'08-23'.$his);
        $augustE    = strtotime($y.'09-22'.$his);
        $septemberS = strtotime($y.'09-23'.$his);
        $septemberE = strtotime($y.'10-23'.$his);
        $octoberS   = strtotime($y.'10-24'.$his);
        $octoberE   = strtotime($y.'11-22'.$his);
        $novemberS  = strtotime($y.'11-23'.$his);
        $novemberE  = strtotime($y.'12-21'.$his);

        if($userTime >= $januaryS && $userTime <= $januaryE){
            $constellation = '水瓶座';
        }elseif($userTime >= $februaryS && $userTime <= $februaryE){
            $constellation = '双鱼座';
        }elseif($userTime >= $marchS && $userTime <= $marchE){
            $constellation = '白羊座';
        }elseif($userTime >= $aprilS && $userTime <= $aprilE){
            $constellation = '金牛座';
        }elseif($userTime >= $mayS && $userTime <= $mayE){
            $constellation = '双子座';
        }elseif($userTime >= $juneS && $userTime <= $juneE){
            $constellation = '巨蟹座';
        }elseif($userTime >= $julyS && $userTime <= $julyE){
            $constellation = '狮子座';
        }elseif($userTime >= $augustS && $userTime <= $augustE){
            $constellation = '处女座';
        }elseif($userTime >= $septemberS && $userTime <= $septemberE){
            $constellation = '天秤座';
        }elseif($userTime >= $octoberS && $userTime <= $octoberE){
            $constellation = '天蝎座';
        }elseif($userTime >= $novemberS && $userTime <= $novemberE){
            $constellation = '射手座';
        }else{
            $constellation = '摩羯座';
        }

        return $constellation;
    }

/**
   * 年龄转生日(模糊结果)
   * @parameter int age(年龄)
   * @parameter string symbol(分隔符)
   * @return string (yyyy*mm*dd)
   * @author he
   */
function agetobirthday($age,$symbol='-'){
     $age = $age==0?25:$age;
     $nowyear = date("Y",time());
     $year = $nowyear-$age;
     $monthArr = [];
     for ($i=1;$i<13;$i++){
         $monthArr[] = $i<10?'0'.$i:$i;
     }
     $dayArr = [];
     for ($i=1;$i<29;$i++){
         $dayArr[] = $i<10?'0'.$i:$i;
     }
     $month_key = array_rand($monthArr,1);
     $month = $monthArr[$month_key];
     $date_tmp_stamp = strtotime($year.'-'.$month);
     $day = '';
     if( $month=='02' && date("t",$date_tmp_stamp)=='29' ) {
         $dayArr = array_merge($dayArr,['29']);
         $day_key = array_rand($dayArr,1);
         $day = $dayArr[$day_key];
     } else if ( $month=='02' && date("t",$date_tmp_stamp)=='28' ){
         $day_key = array_rand($dayArr,1);
         $day = $dayArr[$day_key];
     } else if( in_array($month, ['01','03','05','07','08','10','12']) ) {
         $dayArr = array_merge($dayArr,['29','30','31']);
         $day_key = array_rand($dayArr,1);
         $day = $dayArr[$day_key];
     } else {
         $dayArr = array_merge($dayArr,['29','30']);
         $day_key = array_rand($dayArr,1);
         $day = $dayArr[$day_key];
     }
//     return  $year.$symbol.$month.$symbol.$day;
    return $year;
}

/*
 * ==============================
 * @description    取得两个时间戳相差的年龄
 * @before         较小的时间戳
 * @after          较大的时间戳
 * @return str     返回相差年龄y岁
**/
function datediffage($before, $after) {
    $before = strtotime($before);
    $after = strtotime($after);
    if ($before>$after) {
        $b = getdate($after);
        $a = getdate($before);
    }
    else {
        $b = getdate($before);
        $a = getdate($after);
    }
    $n = array(1=>31,2=>28,3=>31,4=>30,5=>31,6=>30,7=>31,8=>31,9=>30,10=>31,11=>30,12=>31);
    $y=$m=$d=0;
    if ($a['mday']>=$b['mday']) { //天相减为正
        if ($a['mon']>=$b['mon']) {//月相减为正
            $y=$a['year']-$b['year'];$m=$a['mon']-$b['mon'];
        }else { //月相减为负，借年
            $y=$a['year']-$b['year']-1;$m=$a['mon']-$b['mon']+12;
        }
        $d=$a['mday']-$b['mday'];
    }else {  //天相减为负，借月
        if ($a['mon']==1) { //1月，借年
            $y=$a['year']-$b['year']-1;$m=$a['mon']-$b['mon']+12;$d=$a['mday']-$b['mday']+$n[12];
        }else {
            if ($a['mon']==3) { //3月，判断闰年取得2月天数
                $d=$a['mday']-$b['mday']+($a['year']%4==0?29:28);
            }else {
                $d=$a['mday']-$b['mday']+$n[$a['mon']-1];
            }
            if ($a['mon']>=$b['mon']+1) { //借月后，月相减为正
                $y=$a['year']-$b['year'];$m=$a['mon']-$b['mon']-1;
            }else { //借月后，月相减为负，借年
                $y=$a['year']-$b['year']-1;$m=$a['mon']-$b['mon']+12-1;
            }
        }
    }
    return $y==0?'':$y;
}
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length) {
        return mb_substr($text, 0, $length, 'utf8').'...';
    } else {
        return $text;
    }

}


/**
 * 发起http请求
 * @param string $url 访问路径
 * @param array $params 参数，该数组多于1个，表示为POST
 * @param int $expire 请求超时时间
 * @param array $extend 请求伪造包头参数
 * @param string $hostIp HOST的地址
 * @return array    返回的为一个请求状态，一个内容
 */
function makeRequest($url, $params = array(), $expire = 0, $extend = array(), $hostIp = '')
{
    if (empty($url)) {
        return array('code' => '100');
    }

    $_curl = curl_init();
    $_header = array(
        'Accept-Language: zh-CN',
        'Connection: Keep-Alive',
        'Cache-Control: no-cache'
    );
    // 方便直接访问要设置host的地址
    if (!empty($hostIp)) {
        $urlInfo = parse_url($url);
        if (empty($urlInfo['host'])) {
            $urlInfo['host'] = substr(DOMAIN, 7, -1);
            $url = "http://{$hostIp}{$url}";
        } else {
            $url = str_replace($urlInfo['host'], $hostIp, $url);
        }
        $_header[] = "Host: {$urlInfo['host']}";
    }

    // 只要第二个参数传了值之后，就是POST的
    if (!empty($params)) {
        curl_setopt($_curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($_curl, CURLOPT_POST, true);
    }

    if (substr($url, 0, 8) == 'https://') {
        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    curl_setopt($_curl, CURLOPT_URL, $url);
    curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($_curl, CURLOPT_USERAGENT, 'API PHP CURL');
    curl_setopt($_curl, CURLOPT_HTTPHEADER, $_header);

    if ($expire > 0) {
        curl_setopt($_curl, CURLOPT_TIMEOUT, $expire); // 处理超时时间
        curl_setopt($_curl, CURLOPT_CONNECTTIMEOUT, $expire); // 建立连接超时时间
    }

    // 额外的配置
    if (!empty($extend)) {
        curl_setopt_array($_curl, $extend);
    }

    $result['result'] = curl_exec($_curl);
    $result['code'] = curl_getinfo($_curl, CURLINFO_HTTP_CODE);
    $result['info'] = curl_getinfo($_curl);
    if ($result['result'] === false) {
        $result['result'] = curl_error($_curl);
        $result['code'] = -curl_errno($_curl);
    }

    curl_close($_curl);
    return $result;
}

/**
 *
 *验证字符串中是否存在手机号
 * wzs
 */
function checkphone($str)
{
    $patterns  = array(
        "/(1[3578]{1}[0-9])[0-9]{4}([0-9]{4})/",
        "/(0[0-9]{2,3}[\-]?[2-9])[0-9]{3,4}([0-9]{3}[\-]?[0-9]?)/i",
        "/(\d{5,10})/",
        "/([a-z0-9\-_\.])[a-z0-9\-_\.]{4}(([a-z0-9\-_\.])@[a-z0-9]+\.[a-z0-9\-_\.]+)/",
        "/([a-z0-9\-_\.]+@[a-z0-9]+\.[a-z0-9\-_\.]+)+/i");
    $str1 = preg_replace($patterns,'********',$str);
    if ($str != $str1)
    {
        return false;
    }else
    {
        return true;
    }
    exit;

}



/**
 * 解密
 */
function decrypt($data,$aes_key)
{
    $data = base64_decode($data);
    $data = openssl_decrypt($data, 'AES-128-ECB', $aes_key, OPENSSL_RAW_DATA);
    return $data;
}
/**
 * 加密
 */
function encrypt($data,$aes_key) 
{
    $data = openssl_encrypt($data, 'AES-128-ECB', $aes_key, OPENSSL_RAW_DATA);
    // dump($data);exit;
    $data = base64_encode($data);
    return $data;

}

/**
 * 用户推荐分算法
 */
function userscore($uid,$type) 
{
    //添加一条记录
    $num = config("score.$type");
    $data = array();
    $data['uid'] = $uid;
    $data['type'] = $type;
    $data['num']  = $num;
    $data['addtime'] = time();
    $res = db::name('user_score_record')->insert($data);
    if (!$res) 
    {
        return false;
    }
    $map = array();
    $map['uid'] =  $uid;

    if ($num <= 0) 
    {
        $res = db::name('children')->where($map)->setDec('today_score',$num);
        $res = db::name('children')->where($map)->setDec('score',$num);
    }else
    {
        $res = db::name('children')->where($map)->setInc('today_score',$num);
        $res = db::name('children')->where($map)->setInc('score',$num);
    }
    if ($res) 
    {
        return true;
    }else
    {
        return false;
    }

}

function base64url_encode($data) 
{
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) 
{
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}  

function pkcs5_pad($text, $blocksize) 
{
    $pad = $blocksize - (strlen($text) % $blocksize);
    return$text . str_repeat(chr($pad), $pad);
}


/**
 * 发送模板短信
 * @param to 短信接收彿手机号码集合,用英文逗号分开
 * @param datas 内容数据
 * @param 模板Id
 */
function sendTemplateSMS($to,$code,$min,$tempid)
{
    $tempId = $tempid;
    $appId = '8a216da879f058330179f88af3cf026c';
    $serverIP = 'sandboxapp.cloopen.com';
    $serverPort = '8883';
    $softVersion = '2013-12-26';
    $accountSid = '8aaf0708697b6beb0169c8317c02378c';
    $BodyType = 'json';
    $accountToken = '76e1d7f2580e46c892144f659c194ea0';
    $datas = array();
    $datas[0] = $code;
    $datas[1] = $min; 
    //主帐号鉴权信息验证，对必选参数进行判空。
    $data['to'] = $to;
    $data['templateId'] = $tempId;
    $data['appId'] = $appId;
    $data['datas'] = $datas;
    $body = json_encode($data);
    $sig =  strtoupper(md5($accountSid . $accountToken . date("YmdHis")));
    $url="https://$serverIP:$serverPort/$softVersion/Accounts/$accountSid/SMS/TemplateSMS?sig=$sig";
    $authen = base64_encode($accountSid . ":" . date("YmdHis"));
    $header = array("Accept:application/$BodyType","Content-Type:application/$BodyType;charset=utf-8","Authorization:$authen");
    $result = curl_post($url,$body,$header);
    return $result;
}

/**
 * 发起HTTPS请求
 */
function curl_post($url,$data,$header,$post=1)
{
    //初始化curl
    $ch = curl_init();
    //参数设置
    $res= curl_setopt ($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt ($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, $post);
    if($post)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        $result = curl_exec ($ch);
        //连接失败
        if($result == FALSE){
            $result = "{\"statusCode\":\"172001\",\"statusMsg\":\"网络错误\"}";
        }
        curl_close($ch);
        return $result;
}

/**
 * 下载远程文件到本地
 * @param string $url 远程图片地址
 * @return string
 */
function localWeixinAvatar($url,$path,$uid,$size)
{
    return \service\FileService::downloadAvatar($uid,$url,$path,$size)['url'];
}
/**
 * 解析emoji表情
 */
function emojiDecode($name)
{
    return ToolsService::emojiDecode($name);
}

/**
 * Emoji原形转换为String
 */
function emojiEncode($name)
{
    return ToolsService::emojiEncode($name);
}
/**
 * 根据年龄获取属相
 * @author wzs
*/
function getShuXiang($year)
{
    $data = array('鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪');
    $index = ($year-1900)%12;
    return $data[$index];

}
/**
 * 加锁
 * @param string $key
 * @param int $time 时间/秒
 * @return bool
 */
function lock($key,$time = 5)
{
    $redis = new Redis();
    $isLock = $redis->setnx($key,time()+$time);
    $lockTime = $redis->get($key);
    if(!$isLock){
        if(time() > $lockTime){//如果已过期从新生成
            $redis->del($key);
            $isLock = $redis->setnx($key,time()+$time);
        }
    }
    return $isLock ? true : false;
}

/**
     * @Notes: 红娘牵线 会员支付最优价格信息
     * @Interface getDisPrice
     * @param $uid
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    function getDisPrice($uid){
            $activity_price = 3999;
            $price1 = 0;
            $price2 = 0;
            $distcount = 0;
            $id1 = '';
            $id2 = '';
            $distcount_id = '';
            $time1 = '';
            $time2 = '';
            $expire_time = '';
            $field = 'id,end_time,discount_price';
            $cInfo1 = DB::name('qx_discount_config')->field($field)->where(['is_show'=>1,'type'=>2,'uid'=>$uid])->find(); //个人是否有优惠
            if(!empty($cInfo1) && $cInfo1['end_time'] > date('Y-m-d H:i:s')){
                $id1 = $cInfo1['id'];
                $price1 = $cInfo1['discount_price']/100;
                $time1 = $cInfo1['end_time'];
            }
            $cInfo2 = DB::name('qx_discount_config')->field($field)->where(['is_show'=>1,'type'=>1])->find(); //全部是否有优惠
            if(!empty($cInfo2) && $cInfo2['end_time'] > date('Y-m-d H:i:s')){
                $id2 = $cInfo2['id'];
                $price2 = $cInfo2['discount_price']/100;
                $time2 = $cInfo2['end_time'];
            }
            if(!empty($price1) || !empty($price2)){
                $distcount = $price2;
                $expire_time = $time2;
                $distcount_id = $id2;
                if($price1 > $price2){
                    $distcount_id = $id1;
                    $distcount = $price1;
                    $expire_time = $time1;
                }
            }
            $price = $activity_price - $distcount ;
            $data = [
//                'distcount_id'=>$distcount_id,
//                'activity_price'=>$price,
                'distcount_id'=>0,
                'activity_price'=>0.1,
                'distcount_price'=>$distcount,
                'expire_time'=>$expire_time
            ];
            return $data;
    }