<?php
/**
 * 问题-模型
 *
 * @authors shengwx (shengwxde@sina.com)
 * @date    2018-03-14
 */
namespace app\api_new\model;

use think\image\Exception as ImageException;
use think\Model;
use Endroid\QrCode\QrCode;
use tpmanc\imagick\Imagick;

class Poster extends Model
{
    private $image;

    /**
     * @param string $imagePath Path to image
     * @return Imagick
     */
    public static function open($imagePath)
    {
        $model = new self();
        $model->image = new \Imagick($imagePath);
        $geo = $model->image->getImageGeometry();
        $model->height = $geo['height'];
        $model->width = $geo['width'];
        return $model;
    }


    /**
     * Save result
     * @param string $path Save path
     * @return void
     */
    public function saveTo($path)
    {
        $this->image->writeImage($path);
        $this->image->destroy();
    }

    /**
     * Add watermark to image
     * @param string $watermarkPath Path to watermark image
     * @param string $xPos Horizontal position - 'left', 'right' or 'center'
     * @param string $yPos Vertical position - 'top', 'bottom' or 'center'
     * @param bool|int|string $xSize Horizontal watermark size: 100, '50%', 'auto' etc.
     * @param bool|int|string $ySize Vertical watermark size: 100, '50%', 'auto' etc.
     * @param bool $xOffset
     * @param bool $yOffset
     * @return Imagick
     * @throws Exception
     */
    public function watermark(
        $watermarkPath,
        $xPos,
        $yPos,
        $xSize = false,
        $ySize = false,
        $xOffset = false,
        $yOffset = false
    ) {
        if ($watermarkPath !== null) {
            $watermark = new \Imagick($watermarkPath);

            // resize watermark
            $newSizeX = false;
            $newSizeY = false;
            if ($xSize !== false) {
                if (is_numeric($xSize)) {
                    $newSizeX = $xSize;
                } elseif (is_string($xSize) && substr($xSize, -1) === '%') {
                    $float = str_replace('%', '', $xSize) / 100;
                    $newSizeX = $this->width * ((float) $float);
                }
            }
            if ($ySize !== false) {
                if (is_numeric($ySize)) {
                    $newSizeY = $ySize;
                } elseif (is_string($ySize) && substr($ySize, -1) === '%') {
                    $float = str_replace('%', '', $ySize) / 100;
                    $newSizeY = $this->height * ((float) $float);
                }
            }
            if ($newSizeX !== false && $newSizeY !== false) {
                $watermark->adaptiveResizeImage($newSizeX, $newSizeY);
            } elseif ($newSizeX !== false && $newSizeY === false) {
                $watermark->adaptiveResizeImage($newSizeX, 0);
            } elseif ($newSizeX === false && $newSizeY !== false) {
                $watermark->adaptiveResizeImage(0, $newSizeY);
            }

            $startX = false;
            $startY = false;
            $watermarkSize = $watermark->getImageGeometry();
            if ($yPos === 'top') {
                $startY = 0;
                if ($yOffset !== false) {
                    $startY += $yOffset;
                }
            } elseif ($yPos === 'bottom') {
                $startY = $this->height - $watermarkSize['height'];
                if ($yOffset !== false) {
                    $startY -= $yOffset;
                }
            } elseif ($yPos === 'center') {
                $startY = ($this->height / 2) - ($watermarkSize['height'] / 2);
            } else {
                throw new \Exception('Param $yPos should be "top", "bottom" or "center" insteed "'.$yPos.'"');
            }

            if ($xPos === 'left') {
                $startX = 0;
                if ($xOffset !== false) {
                    $startX += $xOffset;
                }
            } elseif ($xPos === 'right') {
                $startX = $this->width - $watermarkSize['width'];
                if ($xOffset !== false) {
                    $startX -= $xOffset;
                }
            } elseif ($xPos === 'center') {
                $startX = ($this->width / 2) - ($watermarkSize['width'] / 2);
            } else {
                throw new \Exception('Param $xPos should be "left", "right" or "center" insteed "'.$xPos.'"');
            }

            $this->image->compositeImage($watermark, \Imagick::COMPOSITE_OVER, $startX, $startY);
        }
        return $this;
    }
    /**
     * Resize image
     * @param integer $width
     * @param integer $height
     * @return Imagick
     * @throws Exception
     */
    public function resize($width, $height)
    {
        if ($height === false && $width === false) {
            throw new \Exception('$width and $height can not be false simultaneously');
        }
        if ($width !== false && $height !== false) {
            if ($this->width >= $this->height) {
                $this->image->adaptiveResizeImage($width, 0);
            } else {
                $this->image->adaptiveResizeImage(0, $height);
            }
        } else {
            if ($width === false) {
                $this->image->adaptiveResizeImage(0, $height);
            } elseif ($height === false) {
                $this->image->adaptiveResizeImage($width, 0);
            }
        }
        return $this;
    }
    /**
     * 图像添加文字
     * @param  string  $text   添加的文字
     * @param  string  $font   字体路径
     * @param  integer $size   字号
     * @param  string  $color  文字颜色
     * @param  int     $locate 文字写入位置
     * @return $this
     * @throws ImageException
     */
    public function text($fonts=[]) {
        foreach ($fonts as $vo){
            $font = '../heiti.TTF';
            $color = $vo['font_color'];
            $size = $vo['font_size'];
            $locate = explode(',', $vo['location']);
            $text = iconv("UTF-8", "UTF-8", $vo['text']);
            $draw = new \ImagickDraw();
            $draw->setFont($font);
            $draw->setTextAlignment(1);
            $draw->setFontSize($size);
            $draw->setFontWeight(900);
            $draw->setFillColor(new \ImagickPixel($color));
            $draw->annotation($locate[0],$locate[1],$text);

            $this->image->drawImage($draw);
        }
        return $this;

    }

    public function getwenzinfo($nickName)
    {

        $nickNamelen = mb_strlen($nickName, 'utf-8');
        $wzallkd = 0;
        $wzallkdlist = array();
        $wzallkdlist[] = 0;
        $perzifulist = array();
        for ($i = 0; $i < $nickNamelen; $i++) {
            $perzifu = mb_substr($nickName, $i, 1, 'utf-8');
            $perzifulist[] = $perzifu;
            if (preg_match("/^[A-Za-z0-9]+$/", $perzifu) == false) {
// echo '是中文';
                $wzkd = 26;
                $wzallkd += 26;
            } else {
// echo '不是中文';
                $wzkd = 17;
                $wzallkd += 15;
            }
            $wzallkdlist[] = $wzkd;
        }
        /*因为字符串宽度是 字符本身宽度+字符之间的间隔-最后的字符串间隔*/
        $wzallkd=$wzallkd-2;
        array_pop($wzallkdlist);

        $wenziinfo['wzallkd'] = $wzallkd;
        $wenziinfo['wzallkdlist'] = $wzallkdlist;
        $wenziinfo['perzifulist'] = $perzifulist;

        return $wenziinfo;
    }


    //裁剪圆行
    function circle($imgpath = './tx.jpg',$size) {
        $img =$this->open($imgpath);
        $img->resize($size,$size)->saveTo($imgpath);
        $ext     = pathinfo($imgpath);
        $src_img = null;
        switch ($ext['extension']) {
            case 'jpg':
                $src_img = imagecreatefromjpeg($imgpath);
                break;
            case 'png':
                $src_img = imagecreatefrompng($imgpath);
                break;
        }
        $wh  = getimagesize($imgpath);
        $w   = $wh[0];
        $h   = $wh[1];
        $w   = min($w, $h);
        $h   = $w;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r   = $w / 2; //圆半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        imagepng($img,$imgpath);
        imagedestroy($img);
        return $imgpath;
    }
    //头像
    public function creates($uid,$back_pic_path,$images,$texts){
        $img = $this->open($back_pic_path);
        $path = './upload/perfect/share/'.date('m').'/'.date('d').'/'.date('H').'/';
        if(!file_exists($path)){
            mkdir($path,755, true);
        }
        $imgpath =  $path.md5($uid.'_'.time()).'.png';

        if(!empty($texts)){
            $img->text($texts);
        }
        if(!empty($images)){
            foreach ($images as $image){
                $qc =   $this->circle($image['path'],$image['size']);
                $xPos =  isset($image['xPos'])? $image['xPos']:'left';
                $yPos =  isset($image['yPos'])? $image['yPos']:'top';
                $img->watermark($qc,$xPos,  $yPos ,false,false,$image['locate'][0],$image['locate'][1]);
            }
        }
        $img->saveTo($imgpath);
        return $imgpath;
    }
    //合并
    public function qrcode($back_pic_path,$images){
        $img = $this->open($back_pic_path);
        $img->watermark($images,'center',  'center' );
        $img->saveTo($back_pic_path);
        return $back_pic_path;
    }



}