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
 * 推荐管理
 * Class Recommend
 * @package app\admin\controller
 */
class Recommend extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'recommend_record';

    /**
     * 推荐列表
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
        $this->title = '用户推荐列表';
        $u_sex = input('u_sex');
        $t_sex = input('t_sex');
        $this->u_sex = $u_sex;
        $this->t_sex = $t_sex;
        $query = $this->_query($this->table);
        if($u_sex){ //接收方性别
            $uInfo = DB::name("children")->where(['sex'=>$u_sex])->column('uid');
            $query->whereIn('uid',$uInfo);
        }
        if($t_sex){ //推荐方性别
            $tInfo = DB::name("children")->where(['sex'=>$t_sex])->column('uid');
            $query->whereIn('recommendid',$tInfo);
        }
        $query->equal("uid,recommendid")->timeBetween('addtime')->where("type != 2")->order('addtime desc')->page();
    }
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
//            $vo['create_at'] = date('Y-m-d',$vo['addtime']);
            $field = 'sex,year,weight_score';
            $uinfo = DB::name("children")->field($field)->where(['uid'=>$vo['uid']])->find();
            $tinfo = DB::name("children")->field($field)->where(['uid'=>$vo['recommendid']])->find();
            $u_nickname = DB::name("userinfo")->where(['id'=>$vo['uid']])->value('nickname');
            $t_nickname = DB::name("userinfo")->where(['id'=>$vo['recommendid']])->value('nickname');
            //接受方
            $vo['u_nickname'] = emoji_decode($u_nickname);
            $vo['u_sex'] = $uinfo['sex'];
            $vo['u_age'] = getage($uinfo['year']);
            $vo['u_weight_score'] = $uinfo['weight_score'];
            //推荐方
            $vo['t_nickname'] = emoji_decode($t_nickname);
            $vo['t_sex'] = $tinfo['sex'];
            $vo['t_age'] = getage($tinfo['year']);
            $vo['t_weight_score'] = $tinfo['weight_score'];

        }
    }
}
