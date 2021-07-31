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

namespace app\admin\controller;

use library\Controller;
use library\tools\Data;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager; //实例化上传类
use Qiniu\Storage\BucketManager; //实例化上传类
// use Qiniu\Storage\UploadManager; //实例化上传类
use Qiniu\Processing\ImageUrlBuilder;

/**
 * 系统用户管理
 * Class User
 * @package app\admin\controller
 */
class Video extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'video';

    /**
     * 视频管理
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '视频管理';
        $this->_query($this->table)->equal('is_online')->where(['is_del'=>1])->dateBetween('create_time')->order('id desc')->page();
    }
    /**
     * 添加视频
     * @auth true
     * @menu true
     */
    public function add()
    {
        $this->_form($this->table, 'form');
    }
    /**
     * 编辑
     * @auth true
     * @menu true
     */
    public function edit()
    {
        $this->_form($this->table, 'form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @auth true
     */
    protected function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
            $id = input('param.id');
            $share_img = input('param.share_img');
            $default_img = 'https://ziyuan.vxcoco.com/698d51a19d8a121ce581499d7b701668202107081438568293.png';
            $imageUrlBuilder = new ImageUrlBuilder();
            if(!empty($id)){
                $data['update_time'] = date('Y-m-d H:i:s');
                $info = DB::name('video')->where(['id'=>$id])->find();
                if ($share_img != $info['share_img']){
                    //合成二维码
                    $waterLink = $imageUrlBuilder->waterImg($share_img, $default_img,100,'SouthEast',185,145,0,0);
                    $data['share_img'] = $waterLink;
                }
            }else{
                //合成二维码
                $waterLink = $imageUrlBuilder->waterImg($share_img, $default_img,100,'SouthEast',185,145,0,0);
                $data['share_img'] = $waterLink;
            }
        }
    }
    /**
     * 上架
     * @auth true
     * @menu true
     */
    public function online()
    {
        $id = input('param.id');
        $res = DB::name('video')->where(['id'=>$id])->update(['is_online'=>1]);
        if ($res){
            $this->success('上架成功！','');
        }else{
            $this->error('失败！');
        }
    }

    /**
     * 下架
     * @auth true
     * @menu true
     */
    public function downline()
    {
        $id = input('param.id');
        $res = DB::name('video')->where(['id'=>$id])->update(['is_online'=>0]);
        if ($res){
            $this->success('下架成功！','');
        }else{
            $this->error('失败！');
        }
    }
}
