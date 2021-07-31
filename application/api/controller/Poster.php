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

namespace app\api\controller;

use app\api\model\Poster as PosterModel;
use think\Controller;
use think\Db;
use think\Queue;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager; //实例化上传类
use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Poster extends Controller
{   


    private static $appid;
    private static $secret;
    private static $grant_type;
    private static $url;
    private static $mch_id;
    private static $key;
    private $no_avatar;
    private static $token;
    private static $aes_key;

    public function __construct(){
        $this::$appid = 'wx5edf4369a4e29312';
        $this::$secret = '6131cf9faa54795b6439130668fe4f15';
        $this::$grant_type ='authorization_code';
        $this::$url = 'https://api.weixin.qq.com/sns/jscode2session';
        $this::$mch_id = '1610267514';
        $this::$key = 'CBDF911D317C03D8BA81EEFCF79F7AD3';
        
        $this::$token = 'weixin';
        $this->no_avatar = "http://small.ying-ji.com/understand/noheader.png";
        $this::$aes_key = '5b9c2ed3e19c40e5';
    }

    /**
     * 生成海报
     * @author wzs
    */
    public function index($uid)
    {
        // $uid= 202;
        $map = arraY();
        $map['id'] = $uid;
        $userinfo = db::name('userinfo')->where($map)->find();
        $header_url = $userinfo['headimgurl'];
        $path = './upload/perfect/headimg/'.date('m').'/'.date('d').'/'.date('H').'/';
        if ($uid <= 200) 
        {
            $header_path = $this->downloadimg($header_url,$path.rand(111,999).'.png');
        }else
        {
            $header_path = localWeixinAvatar($header_url,'./upload/perfect/headimg/',$uid,132);
        }
        if(!is_dir($path)){
            mkdir($path,0700,true);
        }
        $header_path = $this->ssimg1($path,$header_path,80,80);
        $back_pic_path = './share/sharebg.png';
        $back_shi = './share/shareshi1.png';
        $posterModel = new PosterModel();
        $header =[];
        $header['path'] = $header_path;
        $header['size'] = 80;
        $header['locate'] = [30,80];
        $header['xPos'] = 'left';
        $qrcode['path'] = $back_shi;
        $qrcode['size'] = 38;
        $qrcode['locate'] = [90,125];
        $qrcode['xPos'] = 'left';
        $qrcode['yPos'] = 'top';
        $images[0] = $header;
        $images[1] = $qrcode;
//        $back_pic_path = $posterModel->composePic($back_pic_path,$header_path,[80,80],[30,65],$file_path,1);
//        $back_pic_path = $posterModel->composePic($back_pic_path,$back_shi,[38,38],[90,115],$file_path,1);
        $map = array();
        $map['uid'] = $uid;
        $children = db::name('children')->where($map)->find();
        $sex = $children['sex']==2?'女':'男';
        $year = $children['year'].'年';
        $residence = mb_substr($children['residence'], 0,3 );
        $height = $children['height'].'CM';
        switch ($children['education']) {
            case '1':
                $education = '中专';
                break;
            case '2':
                $education = '高中';
                break;
            case '3':
                $education = '大专';
                break;
            case '4':
                $education = '本科';
                break;
            case '5':
                $education = '研究生';
                break;
            case '6':
                $education = '博士';
                break;
            default:
                $education = '本科';
                break;
        }
        $len = mb_strlen($children['work']);
        if ($len<=3) 
        {
            $work = $children['work'];

        }else
        {
            $work = mb_substr($children['work'], 0,3 ).'...';
        }
        if (mb_strlen($userinfo['realname']) == 0) {
            $name_location = '50,190';
            $name = '家长';
        }else{
            $name_location = '30,190';
            $name = mb_substr($userinfo['realname'], 0,1 ).'家长';
        }
        $text_array[0]['location'] ='160,70';
        $text_array[0]['text'] =$sex;
        $text_array[0]['font_size'] = 30;
        $text_array[0]['font_color'] = '#000';
        $text_array[1]['text'] =  $year;
        $text_array[1]['location'] = '280,70';
        $text_array[1]['font_size'] = 30;
        $text_array[1]['font_color'] = '#000';
        $text_array[2]['location'] ='160,140';
        $text_array[2]['text'] =$residence;
        $text_array[2]['font_size'] = 30;
        $text_array[2]['font_color'] = '#000';
        $text_array[3]['location'] ='280,140';
        $text_array[3]['text'] =$height;
        $text_array[3]['font_size'] = 30;
        $text_array[3]['font_color'] = '#000';
        $text_array[4]['location'] ='160,210';
        $text_array[4]['text'] =$education;
        $text_array[4]['font_size'] = 30;
        $text_array[4]['font_color'] = '#000';

        $text_array[5]['location'] ='280,210';
        $text_array[5]['text'] =$work;
        $text_array[5]['font_size'] = 30;
        $text_array[5]['font_color'] = '#000';

        $text_array[6]['location'] =$name_location;
        $text_array[6]['text'] =$name;
        $text_array[6]['font_size'] = 30;
        $text_array[6]['font_color'] = '#fff';
        $local_path = $posterModel->creates($uid,$back_pic_path,$images,$text_array);

        //上传到七牛云
        $sourceurl_data = $this->qiniuupload($local_path);
        $s_arr = json_decode($sourceurl_data,true);
        $sourceurl_qiniu = $s_arr['img'];
        return $sourceurl_qiniu;
    }

    /**
     * 生成海报
     * @author wzs
    */
    public function downloadimg($url, $path)
    {
        // $state = @file_get_contents($url,0,null,0,1);

        $state = @file_get_contents($url,0,null,0,1);//获取网络资源的字符内容
        // dump($state);exit;
        if($state){
            $filename = $path;//文件名称生成
            // dump($filename);exit;
            readfile($url);//输出图片文件
            $img = ob_get_contents();//得到浏览器输出
            ob_end_clean();//清除输出并关闭
            $size = strlen($img);//得到图片大小
            // dump($filename);exit;
            $fp2 = @fopen($filename, "a");
            @fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名
            @fclose($fp2);
            ob_start();//打开输出
            // dump($filename);exit;

            return $filename;
        }
        else{
            return 0;
        }
      
    }

    function getImage($url,$save_dir='',$filename='',$type=0)
    {
        if (trim($url) == '') {
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if (trim($save_dir) == '') {
            $save_dir = './';
        }
        if (trim($filename) == '') {//保存文件名
            $ext = strrchr($url, '.');
            if ($ext != '.gif' && $ext != '.jpg') {
                return array('file_name' => '', 'save_path' => '', 'error' => 3);
            }
            $filename = time() . $ext;
        }
        if (0 !== strrpos($save_dir, '/')) {
            $save_dir .= '/';
        }
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法
        if ($type) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        //文件大小
        $fp2 = @fopen($save_dir . $filename, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);
        return array('file_name' => $filename, 'save_path' => $save_dir . $filename, 'error' => 0);
    }


    public function qiniuupload($img) 
    {
        $file = $img;
        $ext = 'png';
        // 上传到七牛后保存的文件名
        $key = md5(rand(1,10000)).date('YmdHis').rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        // 构建鉴权对象
        $accessKey = config('qiniu.accessKey');
        $secretKey =config('qiniu.secretKey');
        $auth=new Auth($accessKey,$secretKey);
        // 要上传的空间
        $bucket =config('qiniu.bucket');
        //域名
        $domain=config('qiniu.domain');
        $token = $auth->uploadToken($bucket);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        $res = $uploadMgr->putFile($token, $key, $file);
        if ($res[0]['key']) 
        {
            $url = 'https://'.config('qiniu.domain').'/'.$res[0]['key']; 
            $data = array();
            $data['code'] = 200;
            $data['img'] = $url;
            return json_encode($data);exit;
        }else
        {
            $data = array();
            $data['code'] = -200;
            $data['msg'] = "数据生成失败！";
            return json_encode($data);exit;
        }
        
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
