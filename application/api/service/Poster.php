<?php
/**
 * 问题-模型
 *
 * @authors shengwx (shengwxde@sina.com)
 * @date    2018-03-14
 */
namespace app\api\service;

use think\Model;
use Endroid\QrCode\QrCode;

class Poster
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
     * @param string $text_path 字体文件路径
     * 2019年1月5日下午4:47:36
     * liuxin 285018762@qq.com
     */
    public function composePic($back_pic_path,$qrcode_path,$qrcode_size,$qrcode_location,$file_path,$is_circle=0,$text=null,$text_path='./msyh.ttc') {
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
            $trans_colour = imagecolorallocatealpha($target_im, 0, 0, 0, 120);
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
                imagefttext($back_resource, 18, 0, 250, 280, imagecolorallocate($back_resource, 0, 0, 0),$text_path , $nickname);
                //custom_log('poster','$text_path=='.$text_path);
            }
            if(is_array($text)){
                foreach ($text as $key=>$val){
                    $text = iconv("UTF-8", "UTF-8", $val['text']);
                    $location =  explode(',', $val['location']);
                    $font_size = $val['font_zie'];
                    $font_color = explode(',',$val['font_color']);
                    $font  = $val['font'];
                    if(array_key_exists('font', $val)){
                        $font  = $val['font'];
                    }
                    imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    if($key==0){
//                        $text = iconv("UTF-8", "UTF-8", $val['text']);
//                        $location =  explode(',', $val['location']);
//                        $font_size = $val['font_zie'];
//                        $font_color = explode(',',$val['font_color']);
//                        $font  = $text_path;
//                        if(array_key_exists('font', $val)){
//                            $font  = $val['font'];
//                        }
//                        $fontBox = imagettfbbox($font_size, 0, $font, $text);//获取文字所需的尺寸大小
//                        imagettftext($back_resource, $font_size, 0, ceil(($back_width - $fontBox[2]) / 2), ceil(($back_height - $fontBox[1] - $fontBox[7]) / 2), imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    }else{
//                        $text = iconv("UTF-8", "UTF-8", $val['text']);
//                        $location =  explode(',', $val['location']);
//                        $font_size = $val['font_zie'];
//                        $font_color = explode(',',$val['font_color']);
//                        $font  = $text_path;
//                        if(array_key_exists('font', $val)){
//                            $font  = $val['font'];
//                        }
//                        imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    }

                }
            }


        }


        //合成海报
        $newImg = $this->create_pic_watermark_new($back_resource,[$back_width,$back_height],$qrcode_resource,$qrcode_size,$qrcode_location);
        //最终合成图片文件
        $file_name = time().randomFromDev(16).'.png';
        $file_name_url =  $path.$file_name;
        imagejpeg($newImg,$file_name_url);
        if($is_circle){
            unlink($comp_path);
        }
        return $file_name_url;
    }
    /**
     *
     * @param unknown $back_pic_path  背景图路径
     * @param unknown $qrcode_path    小图路径
     * @param unknown $qrcode_size    小图大小
     * @param unknown $qrcode_location  小图位置
     * @param unknown $file_path  合成之后的文件存放位置
     * @param number $is_circle  是否要切成圆形
     * @param unknown $text
     * @param string $text_path 字体文件路径
     * 2019年1月5日下午4:47:36
     * liuxin 285018762@qq.com
     */
    public function composePicnew($back_pic_path,$qrcode_path,$qrcode_size,$qrcode_location,$file_path,$is_circle=0,$text=null,$text_path='./msyh.ttc') {
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
            $qrcode_resource = $this->radius_img($qrcode_resource, [$qrcode_width,$qrcode_height]);
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
                imagefttext($back_resource, 18, 0, 250, 280, imagecolorallocate($back_resource, 0, 0, 0), $text_path, $nickname);
                //custom_log('poster','$text_path=='.$text_path);
            }
            if(is_array($text)){
                foreach ($text as $key=>$val){
                    $text = iconv("UTF-8", "UTF-8", $val['text']);
                    $location =  explode(',', $val['location']);
                    $font_size = $val['font_zie'];
                    $font_color = explode(',',$val['font_color']);
                    $font  = $text_path;
                    if(array_key_exists('font', $val)){
                        $font  = $val['font'];
                    }
                    imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    if($key==0){
//                        $text = iconv("UTF-8", "UTF-8", $val['text']);
//                        $location =  explode(',', $val['location']);
//                        $font_size = $val['font_zie'];
//                        $font_color = explode(',',$val['font_color']);
//                        $font  = $text_path;
//                        if(array_key_exists('font', $val)){
//                            $font  = $val['font'];
//                        }
//                        $fontBox = imagettfbbox($font_size, 0, $font, $text);//获取文字所需的尺寸大小
//                        imagettftext($back_resource, $font_size, 0, ceil(($back_width - $fontBox[2]) / 2), ceil(($back_height - $fontBox[1] - $fontBox[7]) / 2), imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    }else{
//                        $text = iconv("UTF-8", "UTF-8", $val['text']);
//                        $location =  explode(',', $val['location']);
//                        $font_size = $val['font_zie'];
//                        $font_color = explode(',',$val['font_color']);
//                        $font  = $text_path;
//                        if(array_key_exists('font', $val)){
//                            $font  = $val['font'];
//                        }
//                        imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    }

                }
            }


        }


        //合成海报
        $newImg = $this->create_pic_watermark_new($back_resource,[$back_width,$back_height],$qrcode_resource,$qrcode_size,$qrcode_location);
        //最终合成图片文件
        $file_name = time().randomFromDev(16).'.png';
        $file_name_url =  $path.$file_name;
        imagejpeg($newImg,$file_name_url);
        if($is_circle){
            unlink($comp_path);
        }
        return $file_name_url;
    }

    public function composePic1($back_pic_path,$qrcode_path,$qrcode_size,$qrcode_location,$file_path,$is_circle=0,$text=null,$text_path='./msyh.ttc') {
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
                imagefttext($back_resource, 18, 0, 250, 280, imagecolorallocate($back_resource, 0, 0, 0), $text_path, $nickname);
                //custom_log('poster','$text_path=='.$text_path);
            }
            if(is_array($text)){
                foreach ($text as $key=>$val){
                    $text = iconv("UTF-8", "UTF-8", $val['text']);
                    $location =  explode(',', $val['location']);
                    $font_size = $val['font_zie'];
                    $font_color = explode(',',$val['font_color']);
                    $font  = $text_path;
                    if(array_key_exists('font', $val)){
                        $font  = $val['font'];
                    }
                    imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    if($key==0){
//                        $text = iconv("UTF-8", "UTF-8", $val['text']);
//                        $location =  explode(',', $val['location']);
//                        $font_size = $val['font_zie'];
//                        $font_color = explode(',',$val['font_color']);
//                        $font  = $text_path;
//                        if(array_key_exists('font', $val)){
//                            $font  = $val['font'];
//                        }
//                        $fontBox = imagettfbbox($font_size, 0, $font, $text);//获取文字所需的尺寸大小
//                        imagettftext($back_resource, $font_size, 0, ceil(($back_width - $fontBox[2]) / 2), ceil(($back_height - $fontBox[1] - $fontBox[7]) / 2), imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    }else{
//                        $text = iconv("UTF-8", "UTF-8", $val['text']);
//                        $location =  explode(',', $val['location']);
//                        $font_size = $val['font_zie'];
//                        $font_color = explode(',',$val['font_color']);
//                        $font  = $text_path;
//                        if(array_key_exists('font', $val)){
//                            $font  = $val['font'];
//                        }
//                        imagefttext($back_resource, $font_size, 0, $location[0],$location[1], imagecolorallocate($back_resource, $font_color[0],$font_color[1],$font_color[2]), $font, $text);
//                    }

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

    /**
     * [ 编辑图片为圆形]  剪切图片为圆角矩形
     * @param  [string] $imgpath [头像保存之后的图片名]
     */
    function radius_img($src_img,$wh ,$radius = 10) {

        $w  = $wh[0];
        $h  = $wh[1];
        // $radius = $radius == 0 ? (min($w, $h) / 2) : $radius;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }

    /**
     * 画一个带圆角的背景图
     * @param $im  底图
     * @param $dst_x 画出的图的（0，0）位于底图的x轴位置
     * @param $dst_y 画出的图的（0，0）位于底图的y轴位置
     * @param $image_w 画的图的宽
     * @param $image_h 画的图的高
     * @param $radius 圆角的值
     */
    function imagebackgroundmycard($im, $dst_x, $dst_y, $image_w, $image_h, $radius)
    {
        $resource = imagecreatetruecolor($image_w, $image_h);
        $bgcolor = imagecolorallocate($resource, 0xef, 0xef, 0xe1);//该图的背景色

        imagefill($resource, 0, 0, $bgcolor);
        $lt_corner = $this->get_lt_rounder_corner($radius, 255, 255, 255);//圆角的背景色

        // lt(左上角)
        imagecopymerge($resource, $lt_corner, 0, 0, 0, 0, $radius, $radius, 100);
        // lb(左下角)
        $lb_corner = imagerotate($lt_corner, 90, 0);
        imagecopymerge($resource, $lb_corner, 0, $image_h - $radius, 0, 0, $radius, $radius, 100);
        // rb(右上角)
        $rb_corner = imagerotate($lt_corner, 180, 0);
        imagecopymerge($resource, $rb_corner, $image_w - $radius, $image_h - $radius, 0, 0, $radius, $radius, 100);
        // rt(右下角)
        $rt_corner = imagerotate($lt_corner, 270, 0);
        imagecopymerge($resource, $rt_corner, $image_w - $radius, 0, 0, 0, $radius, $radius, 100);

        imagecopy($im, $resource, $dst_x, $dst_y, 0, 0, $image_w, $image_h);
    }
    function get_lt_rounder_corner($radius, $color_r, $color_g, $color_b)
    {
        // 创建一个正方形的图像
        $img = imagecreatetruecolor($radius, $radius);
        // 图像的背景
        $bgcolor = imagecolorallocate($img, $color_r, $color_g, $color_b);
        $fgcolor = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $bgcolor);
        // $radius,$radius：以图像的右下角开始画弧
        // $radius*2, $radius*2：已宽度、高度画弧
        // 180, 270：指定了角度的起始和结束点
        // fgcolor：指定颜色
        imagefilledarc($img, $radius, $radius, $radius * 2, $radius * 2, 180, 270, $fgcolor, IMG_ARC_PIE);
        // 将弧角图片的颜色设置为透明
        imagecolortransparent($img, $fgcolor);
        return $img;
    }

    /**
     * @param $im  大的背景图，也是我们的画板
     * @param $lt_corner 我们画的圆角
     * @param $radius  圆角的程度
     * @param $image_h 图片的高
     * @param $image_w 图片的宽
     */
    function myradus($im, $lift, $top, $lt_corner, $radius, $image_h, $image_w)
    {
/// lt(左上角)
        imagecopymerge($im, $lt_corner, $lift, $top, 0, 0, $radius, $radius, 100);
// lb(左下角)
        $lb_corner = imagerotate($lt_corner, 90, 0);
        imagecopymerge($im, $lb_corner, $lift, $image_h - $radius + $top, 0, 0, $radius, $radius, 100);
// rb(右上角)
        $rb_corner = imagerotate($lt_corner, 180, 0);
        imagecopymerge($im, $rb_corner, $image_w + $lift - $radius, $image_h + $top - $radius, 0, 0, $radius, $radius, 100);
// rt(右下角)
        $rt_corner = imagerotate($lt_corner, 270, 0);
        imagecopymerge($im, $rt_corner, $image_w - $radius + $lift, $top, 0, 0, $radius, $radius, 100);
    }


    /**
     * 给图片加边框 by liangjian 2014-06-19
     * @param $ImgUrl	图片地址
     * @param $SavePath	新图片保存路径
     * @param $px	边框像素（2表示左右各一像素）
     * @return Ambigous <boolean, 新图片的路径>
     */
//    function ImageAddBoard($ImgUrl, $SavePath, $px = 2,$size) {
//
//
//        $qrcode_resource = $this->circleImg($qrcode_resource, [$qrcode_width,$qrcode_height]);
//        //创建一个新的画布（缩放后的），从左上角开始填充透明背景
//        $target_im = imagecreatetruecolor($qrcode_max_width,$qrcode_height);
//        imagesavealpha($target_im, true);
//        $trans_colour = imagecolorallocatealpha($target_im, 0, 0, 0, 127);
//        imagefill($target_im, 0, 0, $trans_colour);
//        imagecopyresampled($target_im,$qrcode_resource, 0, 0,0, 0, $qrcode_max_width, $qrcode_height, $qrcode_width, $qrcode_height);
//        $file_head_name = 'circle_'.time().'.jpg';
//        $comp_path =$path.$file_head_name;
//        imagepng($target_im,$comp_path);
//        imagedestroy($target_im);
//        //重新读取图片
//        $qrcode_data = $this->readImage($comp_path);
//        if(empty($qrcode_data)){
//            return false;
//        }
//
//
//        $aPathInfo = pathinfo ( $ImgUrl );
//        // 文件名
//        $sFileName = $aPathInfo ['filename'];
//        // 图片扩展名
//        $sExtension = $aPathInfo ['extension'];
//        // 获取原图大小
//        list($img_w, $img_h) = getimagesize ( $ImgUrl );
//
//        // 读取图片
//        if (strtolower ( $sExtension ) == 'png') {
//            $resource = imagecreatefrompng ( $ImgUrl );
//        } elseif (strtolower ( $sExtension ) == 'jpg' || strtolower ( $sExtension ) == 'jpeg') {
//            $resource = imagecreatefromjpeg ( $ImgUrl );
//        }
//
//        // 282*282的黑色背景图片
//        $im = @imagecreatetruecolor ( ($img_w + $px), ($img_h + $px) ) or die ( "Cannot Initialize new GD image stream" );
//
//        // 为真彩色画布创建背景，再设置为透明
//        $color = imagecolorallocate ( $im, 0, 0, 0 );
//        //imagefill ( $im, 0, 0, $color );
//        //imageColorTransparent ( $im, $color );
//
//        // 把品牌LOGO图片放到黑色背景图片上，边框是1px
//        imagecopy ( $im, $resource, $px / 2, $px / 2, 0, 0, $size, $size );
//
//        $imgNewUrl = $SavePath . $sFileName . '-n.' . $sExtension;
//        if (strtolower ( $sExtension ) == 'png') {
//            $ret = imagepng ( $im, $imgNewUrl );
//        } elseif (strtolower ( $sExtension ) == 'jpg' || strtolower ( $sExtension ) == 'jpeg') {
//            $ret = imagejpeg ( $im, $imgNewUrl );
//        }
//        imagedestroy ( $im );
//        return $ret ? $imgNewUrl : false;
//    }

    public function composePicYuan($qrcode_path,$file_path,$qrcode_size) {
        //海报背景图地址
        $qrcode_max_width   =  $qrcode_size[0];
        $qrcode_min_height  =  $qrcode_size[1];


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
        if(empty($qrcode_data)){
            return false;
        }

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

        return $comp_path;

    }

     /**
     * 
     * 处理图片
     */
    public function ssimg1($path,$img,$widths,$height)
    {
        $target_im = imagecreatetruecolor($widths,$height);     //创建一个新的画布（缩放后的），从左上角开始填充透明背景
        imagesavealpha($target_im, true);
        $trans_colour = imagecolorallocatealpha($target_im, 0, 0, 0, 127);
        imagefill($target_im, 0, 0, $trans_colour);
        
        list($width, $width, $type, $attr) = getimagesize($img);
        $types=array(1 => "GIF",2 => "JPEG",3 => "PNG",
                4 => "SWF",5 => "PSD",6 => "BMP",
                7 => "TIFF",8 => "TIFF",9 => "JPC",
                10 => "JP2",11 => "JPX",12 => "JB2",
                13 => "SWC",14 => "IFF",15 => "WBMP",16 => "XBM");
        $dtype=strtolower($types[$type]);//原图类型
        $created="imagecreatefrom".$dtype;
        $o_image  = $created($img);
        imagecopyresampled($target_im,$o_image, 0, 0,0, 0, $widths,$height, $width, $width);
        $file_head_name = 'big_192_'.time().createRandStr(7).'.jpg';
        $comp_path =$path;
        if(!is_dir($comp_path)){
            mkdir($comp_path,0755,true);
        }
        $comp_file = $comp_path.$file_head_name;
        imagejpeg($target_im,$comp_file);
        imagedestroy($target_im);
        return $comp_file;
    }

}