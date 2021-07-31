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

namespace app\api\service;


use Qiniu\Storage\BucketManager;
use think\Controller;
use Qiniu\Auth;
//require 'vendor/qiniu/php-sdk/autoload.php';  //引入自动加载类
use Qiniu\Storage\UploadManager; //实例化上传类

/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Upload extends Controller
{


    public function index($img) {
        $file = $img;

        $ext = 'png';
        // 上传到七牛后保存的文件名
        $key = md5(111).date('YmdHis').rand(0, 9999) . '.' . $ext;

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

    public function index7($img,$ext="png")
    {
        $file = $img;

//		var_dump($file);die;

        $ext = $ext;
        // 上传到七牛后保存的文件名
        $key = md5(111).date('YmdHis').rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        // 构建鉴权对象
        $accessKey = config('qiniu.accessKey');
        $secretKey =config('qiniu.secretKey');
        $auth=new Auth($accessKey,$secretKey);
//        var_dump($auth);die;
        // 要上传的空间
        $bucket =config('qiniu.bucket');
        //域名
        $domain=config('qiniu.domain');

        $token = $auth->uploadToken($bucket);

        $bucketM = new BucketManager($auth);

        $items = $bucketM->fetch($file,$bucket,$key);

//        custom_log('qiniu_return',json_encode($items));
//        custom_log('qiniu_return=>img',$img);

        $url = 'https://'.config('qiniu.domain').'/'.$items[0]['key'];

        return $url;
    }
    public function index8($img,$ext="png")
    {
        $file = $img;

        $fileurl = $file;
        $file_name=basename($fileurl);
        $houzhui = substr(strrchr($file_name, '.'), 1);
        $result = basename($file_name,".".$houzhui);


//		var_dump($file);die;

        $ext = $ext;
        // 上传到七牛后保存的文件名
        $key = 'imgexpert/' . date('Ymd').'/'.$result.rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        // 构建鉴权对象
        $accessKey = config('qiniu.accessKey');
        $secretKey =config('qiniu.secretKey');
        $auth=new Auth($accessKey,$secretKey);
//        var_dump($auth);die;
        // 要上传的空间
        $bucket =config('qiniu.bucket');
        //域名
        $domain=config('qiniu.domain');

        $token = $auth->uploadToken($bucket);

        $bucketM = new BucketManager($auth);

        $items = $bucketM->fetch($file,$bucket,$key);

        $url = 'https://'.config('qiniu.domain').'/'.$items[0]['key'];

        return $url;
    }

    public function test(){
        custom_log('test','xxxxxxxxxxxxxxx');
    }


    /**
     * 下载远程图片保存到本地
     * @access public
     * @author      lxhui<772932587@qq.com>
     * @since 1.0
     * @return array
     * @params string $url 远程图片地址
     * @params string $save_dir 需要保存的地址
     * @params string $filename 保存文件名
     */
    public function download($url, $save_dir = './uploads/imgexpert/',$filename='')
    {

        $save_dir .= date('Ymd') . '/';
        if(trim($save_dir)=='')
            $save_dir='./';

        if(trim($filename)==''){//保存文件名
            $allowExt = array('.gif', '.jpg', '.jpeg', '.png', '.bmp');
            $ext=strrchr($url,'.');

//            echo json_encode($ext);die;
            if(!in_array($ext,$allowExt)){
//                echo 1;die;
                return array('file_name'=>'','save_path'=>'','error'=>3);
            }

            $filename=time().$ext;
        }
        if(0!==strrpos($save_dir,'/'))
            $save_dir.='/';

        //创建保存目录
        if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true))
            return array('file_name'=>'','save_path'=>'','error'=>5);

        $ch = curl_init();


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($url, PATHINFO_BASENAME);
//        echo $filename;die;

        $resource = fopen($save_dir . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
        unset($file,$url);
        return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
    }




}
