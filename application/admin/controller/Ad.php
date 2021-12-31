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
 * 广告用户管理
 * Class Ad
 * @package app\admin\controller
 */
class Ad extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'ad_user';

    /**
     * 用户列表
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
        $this->title = '系统用户管理';
        $field = "a.create_time,a.uid,a.status,a.aid,a.click_id,c.residence,c.phone";
        $query = $this->_query($this->table)
                ->alias('a')
                ->join('children c', 'a.uid = c.uid')
                ->field($field)
                ->equal('a.status#status,a.uid#uid,c.phone#phone')  
                ->dateBetween('a.create_time#create_time')
                ->order('a.id desc')->page();
        // var_dump(DB::name('ad_user')->getLastsql());
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
           $vo['nickname'] = Db::name('userinfo')->where(['id'=>$vo['uid']])->value('nickname');
           $vo['headimgurl'] = Db::name('userinfo')->where(['id'=>$vo['uid']])->value('headimgurl');
        }
    }
}
