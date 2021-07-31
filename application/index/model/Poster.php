<?php
/**
 * 问题-模型
 * 
 * @authors shengwx (shengwxde@sina.com)
 * @date    2018-03-14
 */
namespace app\index\model;

use think\Model;
use Endroid\QrCode\QrCode;

class Poster extends Model
{
    /**
     * 
     * @param unknown $back_pic_path  背景图路径 
     * @param unknown $qrcode_path    小图路径
     * @param unknown $qrcode_size    小图大小
     * @param unknown $qrcode_location  小图位置
     * @param unknown $file_path  合成之后的文件存放位置 
     * @param number $is_circle  是否要切成圆形 
     * @param unknown $text
     * 2019年1月5日下午4:47:36 
     * liuxin 285018762@qq.com
     */
    public function composePic($back_pic_path,$qrcode_path,$qrcode_size,$qrcode_location,$file_path,$is_circle=0,$text=null) {
        //海报背景图地址
        $qrcode_max_width   =  $qrcode_size[0];
        $qrcode_min_height  =  $qrcode_size[1];
        $qrcode_x  =  $qrcode_location[0];
        $qrcode_y  =  $qrcode_location[1];;
        //读取背景图片
        $back_data = $this->readImage($back_pic_path);
        if(empty($back_data)){
            return false;
        }
        //宽度   高度  资源
        $back_width  = $back_data['width'];
        $back_height = $back_data['height'];
        $back_resource = $back_data['resource'];
        //读取二维码
        $qrcode_data = $this->readImage($qrcode_path);
        if(empty($qrcode_data)){
            return false;
        }
        //宽度   高度  资源
        $qrcode_width  = $qrcode_data['width'];
        $qrcode_type  = $qrcode_data['type'];
        $qrcode_height = $qrcode_data['height'];
        $qrcode_resource = $qrcode_data['resource'];
        $path  = $file_path.date('m').'/'.date('d').'/'.date('H').'/';
        if(!is_dir($path)){
            mkdir($path,0700,true);
        }
//         //读取二维码图片
//         $qrcode_data = $this->readImage($qrcode_path);
        if(empty($qrcode_data)){
            return false;
        }
        
        if($is_circle){
            //需要剪切为圆形
            $qrcode_resource = $this->circleImg($qrcode_resource, [$qrcode_width,$qrcode_height]);
            //创建一个新的画布（缩放后的），从左上角开始填充透明背景
            $target_im = imagecreatetruecolor($qrcode_max_width,$qrcode_height);     
            imagesavealpha($target_im, true);
            $trans_colour = imagecolorallocatealpha($target_im, 0, 0, 0, 127);
            imagefill($target_im, 0, 0, $trans_colour);
            imagecopyresampled($target_im,$qrcode_resource, 0, 0,0, 0, $qrcode_max_width, $qrcode_height, $qrcode_width, $qrcode_height);
            $file_head_name = 'circle_'.time().'.jpg';
            $comp_path =$path.$file_head_name;
            imagepng($target_im,$comp_path);
            imagedestroy($target_im);
            //重新读取图片
            $qrcode_data = $this->readImage($comp_path);
            if(empty($qrcode_data)){
                return false;
            }
            //获取二维码宽度   高度  资源
            $qrcode_width  = $qrcode_data['width'];
            $qrcode_type  = $qrcode_data['type'];
            $qrcode_height = $qrcode_data['height'];
            $qrcode_resource = $qrcode_data['resource'];
        }
        if(!empty($text)){
            if(is_string($text)){
                //添加用户昵称
                $nickname = iconv("UTF-8", "UTF-8", $text);
                //
                imagefttext($back_resource, 18, 0, 250, 280, imagecolorallocate($back_resource, 0, 0, 0), './msyh.ttc', $nickname);
            }
            if(is_array($text)){
                foreach ($text as $key=>$val){
                    $text = iconv("UTF-8", "UTF-8", $val['text']);
                    $location =  explode(',', $val['location']);
                    $font_size = $val['font_zie'];
                    $font_color = explode(',',$val['font_color']);
                    $font  = './msyh.ttc';
                    if(array_key_exists('font', $val)){
                        $font  = $val['font'];
                    }
                    imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
                }
            }
            
           
        }
      
        
        //合成海报
        $newImg = $this->create_pic_watermark_new($back_resource,[$back_width,$back_height],$qrcode_resource,$qrcode_size,$qrcode_location);
        //最终合成图片文件
        $file_name = time().randomFromDev(16).'.png';
        $file_name_url =  $path.$file_name;
        imagejpeg($newImg,$file_name_url);
        return $file_name_url;
    }
    
	
	 public function composePic2($back_pic_path,$file_path,$text=null) {
        //海报背景图地址
        //读取背景图片
        $back_data = $this->readImage($back_pic_path);
        if(empty($back_data)){
            return false;
        }
        //宽度   高度  资源
        $back_width  = $back_data['width'];
        $back_height = $back_data['height'];
        $back_resource = $back_data['resource'];
        $path  = $file_path.date('m').'/'.date('d').'/'.date('H').'/';
        if(!is_dir($path)){
            mkdir($path,0700,true);
        }
        if(!empty($text)){
            if(is_array($text)){
                foreach ($text as $key=>$val){
                    $text = iconv("UTF-8", "UTF-8", $val['text']);
                    $font_size = $val['font_zie'];
                    $location =  explode(',', $val['location']);
                    $font_color = explode(',',$val['font_color']);
                    $fontpath = realpath('./msyh.ttc');
                    $font  = $fontpath;
                    if(array_key_exists('font', $val)){
                        $font  = $val['font'];
                    }
                    $fontBox = imagettfbbox($font_size, 0, $font, $text);//获取文字所需的尺寸大小
                    //4.写入文字 (图片资源，字体大小，旋转角度，坐标x，坐标y，颜色，字体文件，内容)
                    imagettftext($back_resource, $font_size, 0,$location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
                }
            }
        }
        //创建真彩色背景画布
        $createdImg=imagecreatetruecolor($back_width, $back_height);
        //拷贝背景到真彩色画布
        imagecopy($createdImg, $back_resource, 0, 0, 0, 0, $back_width, $back_height);
        //最终合成图片文件
        $file_name = time().randomFromDev(16).'.png';
        $file_name_url =  $path.$file_name;
        imagejpeg($createdImg,$file_name_url);
        return $file_name_url;
    }

    public function composePic3($back_pic_path,$file_path,$text=null,$tem) {
        //海报背景图地址
        //读取背景图片
        $back_data = $this->readImage($back_pic_path);
        if(empty($back_data)){
            return false;
        }
        //宽度   高度  资源
        $back_width  = $back_data['width'];
        $back_height = $back_data['height'];
        $back_resource = $back_data['resource'];
        $path  = $file_path.date('m').'/'.date('d').'/'.date('H').'/';
        if(!is_dir($path)){
            mkdir($path,0700,true);
        }
        if(!empty($text)){
            if(is_array($text)){
                foreach ($text as $key=>$val){
                    $text = iconv("UTF-8", "UTF-8", $val['text']);
                    $font_size = $val['font_zie'];
                    $location =  explode(',', $val['location']);
                    $font_color = explode(',',$val['font_color']);
                    $fontpath = realpath('./msyh.ttc');
                    $font  = $fontpath;
                    if(array_key_exists('font', $val)){
                        $font  = $val['font'];
                    }
                    $fontBox = imagettfbbox($font_size, 0, $font, $text);//获取文字所需的尺寸大小
                    //4.写入文字 (图片资源，字体大小，旋转角度，坐标x，坐标y，颜色，字体文件，内容)
                    imagettftext($back_resource, $font_size, 0, ceil(($back_width - $fontBox[2]) / 2),$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
                }
            }
        }
        //创建真彩色背景画布
        $createdImg=imagecreatetruecolor($back_width, $back_height);
        //拷贝背景到真彩色画布
        imagecopy($createdImg, $back_resource, 0, 0, 0, 0, $back_width, $back_height);
        //最终合成图片文件
        $file_name = $tem.'.png';
        $file_name_url =  $path.$file_name;
        imagejpeg($createdImg,$file_name_url);
        return $file_name_url;
    }
	
	/**
     * 读取图片
     * @param unknown $path
     * 2018年11月5日下午12:50:02
     * liuxin 285018762@qq.com
     */
    public function readImage($path) {
        list($dwidth,$dheight,$dtype)=getimagesize($path);
        $types=array(1 => "GIF",2 => "JPEG",3 => "PNG",
                4 => "SWF",5 => "PSD",6 => "BMP",
                7 => "TIFF",8 => "TIFF",9 => "JPC",
                10 => "JP2",11 => "JPX",12 => "JB2",
                13 => "SWC",14 => "IFF",15 => "WBMP",16 => "XBM");
        $dtype=strtolower($types[$dtype]);//原图类型
    
        $created="imagecreatefrom".$dtype;
        $back_pic_image = $created($path);
        if(empty($back_pic_image)){
            return false;
        }
        $data['type']   = $dtype;
        $data['width']  = $dwidth;
        $data['height'] = $dheight;
        $data['resource'] = $back_pic_image;
        return $data;
    }
    
    /**
     * 图片合成
     * @param unknown $bigImg
     * @param unknown $bigImgSize
     * @param unknown $littleImg
     * @param unknown $littleSize
     * @param unknown $position
     * 2018年11月5日下午12:49:32
     * liuxin 285018762@qq.com
     */
    public function create_pic_watermark_new($bigImg,$bigImgSize,$littleImg,$littleSize,$position){
        //创建真彩色背景画布
        $createdImg=imagecreatetruecolor($bigImgSize[0], $bigImgSize[1]);
        //拷贝背景到真彩色画布
        imagecopy($createdImg, $bigImg, 0, 0, 0, 0, $bigImgSize[0], $bigImgSize[1]);
        imagecopy($createdImg,$littleImg,$position[0],$position[1],0,0, $littleSize[0], $littleSize[1]);
        return $createdImg;
    }
    
    
    /**
     * 图片等比例缩放
     * @param unknown $im
     * @param unknown $maxwidth
     * @param unknown $maxheight
     * @param unknown $name
     * @param unknown $filetype
     * 2018年7月6日下午3:45:09
     * liuxin 285018762@qq.com
     */
    public function resizeImage($im,$maxwidth,$maxheight,$name,$filetype)
    {
    
        $pic_width = imagesx($im);
        $pic_height = imagesy($im);
         
        if(($maxwidth && $pic_width > $maxwidth) || ($maxheight && $pic_height > $maxheight))
        {
            if($maxwidth && $pic_width>=$maxwidth)
            {
                $widthratio = $maxwidth/$pic_width;
                $resizewidth_tag = true;
            }
    
            if($maxheight && $pic_height>=$maxheight)
            {
                $heightratio = $maxheight/$pic_height;
                $resizeheight_tag = true;
            }
    
            $ratio = $widthratio;
            if($resizewidth_tag && !$resizeheight_tag)
    
                if($resizeheight_tag && !$resizewidth_tag)
                    $ratio = $heightratio;
    
                    $newwidth = $pic_width * $ratio;
                    $newheight = $pic_height * $ratio;
                    //$newheight = $maxheight;
                    if(function_exists("imagecopyresampled"))
                    {
                        $newim = imagecreatetruecolor($newwidth,$newheight);
                        imagecopyresampled($newim,$im,0,0,0,0,$newwidth,$newheight,$pic_width,$pic_height);
                    }
                    else
                    {
                        $newim = imagecreate($newwidth,$newheight);
                        imagecopyresized($newim,$im,0,0,0,0,$newwidth,$newheight,$pic_width,$pic_height);
                    }
                    $name = $name.'.'.$filetype;
                    imagejpeg($newim,$name);
                    imagedestroy($newim);
        }
        else
        {
            $name = $name.'.'.$filetype;
            imagejpeg($im,$name);
        }
    }
    
    /**
     * 生成二维码图片 
     * @param string $url  二维码url指向地址 
     * @param int $qrcodeSize   二维码图片大小，宽带高度为同一数值  
     * @param unknown $userid   用户id
     * @param unknown $path   生成二维码图片位置 
     * 2018年12月27日上午11:28:59 
     * liuxin 285018762@qq.com
     */
    public function gernateQrcode($url,$qrcodeSize, $userid,$path){
        $qrCode = new QrCode();
        $qrCode->setText($url)->setSize($qrcodeSize)->setPadding(5)->setImageType(QrCode::IMAGE_TYPE_PNG);
        $file_path = $path.date('m/d/H/').time().$userid.'_'.createRandStr(4,1).'.png'; 
        if(!is_dir($path.date('m/d/H/'))){
            createDirs($path.date('m/d/H/'));
        }
        $qrCode->save($file_path);
        return $file_path;
    }
    
    
    /**
     * [ 编辑图片为圆形]  剪切头像为圆形
     * @param  [string] $imgpath [头像保存之后的图片名]
     */
    public function circleImg($src_img,$imagesize) {
//         $ext     = pathinfo($imgpath);
//         $src_img = null;
//         switch ($ext['extension']) {
//             case 'jpg':
//                 $src_img = @imagecreatefromjpeg($imgpath);
//                 break;
//             case 'png':
//                 $src_img = @imagecreatefromjpeg($imgpath);
//                 break;
//         }
        //兼容图片下载错误的情况
        if(empty($src_img)){
            return false;
        }
//         $wh  = getimagesize($imgpath);
        $w   = $imagesize[0];
        $h   = $imagesize[1];
        $w   = min($w, $h);
        $h   = $w;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r   = $w / 2; //圆半径
        $y_x = $r; //圆心X坐标
        $y_y = $r; //圆心Y坐标
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        return $img;
    }


    public function yuyin($file_path,$file_name,$length)
    {

        $url = 'http://raasr.xfyun.cn/api/prepare';
        $appid = '5d1c1e45';
        $appkey = '73cf3206da6f02577575cfcd35b22e13';
        $ts = time();
        $aa = $appid.$ts;
        $basestring = MD5($aa);
        $utf8Str = mb_convert_encoding($basestring, "UTF-8");
        $hmac_sha1_str = hash_hmac("sha1", $utf8Str, $appkey);
        $res = hex2bin($hmac_sha1_str);
        $signa = base64_encode($res);
        $data = array(
            'app_id' => $appid,
            'signa' => $signa,
            'ts' => $ts,
            'file_len' => $length,
            'file_name' => $file_name,
            'slice_num' => 1,
        );


        $xparams = base64_encode(json_encode($data));
        $checksum = md5($appkey . $ts . $xparams );

        $header = array(
            'X-CurTime:' . $ts ,
            'X-Param:' . $xparams,
            'X-Appid:' . $appid ,
            'X-CheckSum:' . $checksum,
            'X-Real-Ip:127.0.0.1',
            'Content-Type:application/x-www-form-urlencoded; charset=utf-8',
            
        );
    
        //第一步 预处理
        $response = $this->tocurl($url, $header, $data);
        if ($response['body']) 
        {
            //处理结果
            
            $arr = (array)json_decode($response['body']);
            $taskid = $arr['data'];
            $file = array();
            $file['filename'] = $file_name;
            $file['content'] = new \CURLFile(realpath($file_path));
            if ($taskid) 
            {
                //上传
                $data = array(
                    'app_id' => $appid,
                    'signa' => $signa,
                    'ts' => $ts,
                    'task_id' => $taskid,
                    'slice_id' => 'aaaaaaaaaa',
                    'content' =>  $file['content'],
                );
                


                $xparams = base64_encode(json_encode($data));
                $checksum = md5($appkey . $ts . $xparams );

                $header = array(
                    'X-CurTime:' . $ts ,
                    'X-Param:' . $xparams,
                    'X-Appid:' . $appid ,
                    'X-CheckSum:' . $checksum,
                    'X-Real-Ip:127.0.0.1',
                    'Content-Type: multipart/form-data;',
                    
                );
                //第二步 上传
                $url = 'http://raasr.xfyun.cn/api/upload';
                $response = $this->tocurl1($url, $header, $data);
                //第三部 整合

                $url = 'http://raasr.xfyun.cn/api//merge';
                
                $data = array(
                    'app_id' => $appid,
                    'signa' => $signa,
                    'ts' => $ts,
                    'task_id' => $taskid,
                );


                $xparams = base64_encode(json_encode($data));
                $checksum = md5($appkey . $ts . $xparams );

                $header = array(
                    'X-CurTime:' . $ts ,
                    'X-Param:' . $xparams,
                    'X-Appid:' . $appid ,
                    'X-CheckSum:' . $checksum,
                    'X-Real-Ip:127.0.0.1',
                    'Content-Type:application/x-www-form-urlencoded; charset=utf-8',
                    
                );
            
                $response = $this->tocurl($url, $header, $data);
                //第四步 查询进度

                $url = 'http://raasr.xfyun.cn/api/getProgress';
                
                $data = array(
                    'app_id' => $appid,
                    'signa' => $signa,
                    'ts' => $ts,
                    'task_id' => $taskid,
                );


                $xparams = base64_encode(json_encode($data));
                $checksum = md5($appkey . $ts . $xparams );

                $header = array(
                    'X-CurTime:' . $ts ,
                    'X-Param:' . $xparams,
                    'X-Appid:' . $appid ,
                    'X-CheckSum:' . $checksum,
                    'X-Real-Ip:127.0.0.1',
                    'Content-Type:application/x-www-form-urlencoded; charset=utf-8',
                    
                );
            
                $response1 = $this->tocurl($url, $header, $data);

                //第wu步 查询进度

                $url = 'http://raasr.xfyun.cn/api/getResult';
                
                $data = array(
                    'app_id' => $appid,
                    'signa' => $signa,
                    'ts' => $ts,
                    'task_id' => $taskid,
                );


                $xparams = base64_encode(json_encode($data));
                $checksum = md5($appkey . $ts . $xparams );

                $header = array(
                    'X-CurTime:' . $ts ,
                    'X-Param:' . $xparams,
                    'X-Appid:' . $appid ,
                    'X-CheckSum:' . $checksum,
                    'X-Real-Ip:127.0.0.1',
                    'Content-Type:application/x-www-form-urlencoded; charset=utf-8',
                    
                );
                sleep(3);
            
                $response1 = $this->tocurl($url, $header, $data);
                return $response1;


            }
        }

    }



    public function StrToBin($str){
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach($arr as &$v){
            $temp = unpack('H*', $v);
            $v = base_convert($temp[1], 16, 2);
            unset($temp);
        }
      
        return join(' ',$arr);
    }

    /**
     * 发送数据
     * @param String $url     请求的地址
     * @param Array  $header  自定义的header数据
     * @param Array  $content POST的数据
     * @return String
     */
    public function tocurl1($url, $header, $content)
    {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        $response = curl_exec($ch);
        $error=curl_error($ch);
        if($error){
            die($error);
        }
        $header = curl_getinfo($ch);
        curl_close($ch);
        $data = array('header' => $header,'body' => $response);
        return $data;
    }

    /**
     * 发送数据
     * @param String $url     请求的地址
     * @param Array  $header  自定义的header数据
     * @param Array  $content POST的数据
     * @return String
     */
    public function tocurl($url, $header, $content)
    {
        $ch = curl_init();
        if(substr($url,0,5)=='https'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
        $response = curl_exec($ch);
        $error=curl_error($ch);
        //var_dump($error);
        if($error){
            die($error);
        }
        $header = curl_getinfo($ch);

        curl_close($ch);
        $data = array('header' => $header,'body' => $response);
        return $data;
    }
    
    
    
    
    
    
    
    
   
    
    
    
    
    
   
    
    
    
    
}