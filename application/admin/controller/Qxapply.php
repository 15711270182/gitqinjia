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
 * 牵线服务管理
 * Class Qxapply
 * @package app\admin\controller
 */
class Qxapply extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'qx_apply_user';

    /**
     * 牵线列表
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
        $this->title = '牵线列表';
        $this->_query($this->table)
            ->equal('uid,apply_status')
            ->dateBetween('create_time')
            ->order('id desc')
            ->page();
    }
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $headimgurl = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('headimgurl');
            $nickname = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('nickname');
            $vo['headimgurl'] = $headimgurl;
            $vo['nickname'] = emoji_decode($nickname);
            $Children = Db::name('Children')->where(['uid'=>$vo['uid']])->find();
            $vo['sex'] = '女';
            if ($Children['sex'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$Children['year'];
            $vo['address'] = $Children['province']. '-' .$Children['residence'];

            $vo['remark_sub'] = $this->subtext($vo['remark'],15);
        }
    }
    public function subtext($text, $length)
    {
        if(mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8').'...';
        } else {
            return $text;
        }

    }
     /**
     * 通过审核申请
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function pass()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        $this->applyCsrfToken();
        $this->_save($this->table, ['apply_status' => '1','apply_pass_time'=>date('Y-m-d H:i:s')]);
    }
    /**
     * 同意牵线
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function agreeApply()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        $this->applyCsrfToken();
        $this->_save($this->table, ['apply_status' => '2','update_time'=>date('Y-m-d H:i:s')]);
    }
    /**
     * 拒绝牵线页面
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function refuseApply()
    {
       $id = input('id');
       $this->assign('id',$id);
       $this->fetch();
    }
    /**
     * 拒绝牵线
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function refuseApplySave()
    {
        $remark = $this->request->post('remark');
        $id = $this->request->post('id');
        $res = DB::name('qx_apply_user')->where(['id'=>$id])->update(['remark'=>$remark,'apply_status'=>3,'update_time'=>date('Y-m-d H:i:s')]);
        if($res){
            $this->success('保存成功!');
        }
        $this->error('保存失败');
    }
}
