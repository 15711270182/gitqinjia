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
 * 系统用户管理
 * Class User
 * @package app\admin\controller
 */
class Order extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'order';

    /**
     * 系统用户管理
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
        $this->title = '订单管理';
        $this->_query($this->table)
            ->equal('uid,status')
            ->timeBetween('pay_time')
            ->order('id desc')
            ->page();
    }
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['addtime'] = date('Y-m-d H:i:s',$vo['pay_time']);
            //判断有没有完善孩子资料
            $userinfo = DB::name('userinfo')->where(['id'=>$vo['uid']])->find();
            $vo['headimgurl'] = $userinfo['headimgurl'];
            $vo['nickname'] = emoji_decode($userinfo['nickname']);
            //购买物品
            $title = DB::name('product')->where(['id'=>$vo['goods_id']])->value('title');
            $vo['goods'] = $title;
        }
    }
}
