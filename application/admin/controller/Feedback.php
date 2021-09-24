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

/**
 * 意见反馈管理
 * Class Feedback
 * @package app\admin\controller
 */
class Feedback extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'feedback';

    /**
     * 反馈列表
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '反馈列表';
        $this->_query($this->table)
            ->equal('uid,type')
            ->dateBetween('create_time')
            ->order('id desc')
            ->page();
    }
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $userinfo = DB::name('userinfo')->where(['id'=>$vo['uid']])->find();
            $vo['headimgurl'] = $userinfo['headimgurl'];
            $vo['nickname'] = emoji_decode($userinfo['nickname']);
            $children = DB::name('children')->where(['uid'=>$vo['uid']])->field('sex,year,province,residence')->find();
            $vo['sex'] = '女';
            if ($children['sex'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$children['year'];
            $vo['address'] = $children['province']. '-' .$children['residence'];
        }
    }
}
