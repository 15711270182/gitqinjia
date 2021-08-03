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
class Member extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'userinfo';
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
        $this->title = '用户管理';
        $this->bid = input('bid');
        $bid = input('bid');
        $where=[];
        if(!empty($bid)){
            $uid = DB::name('relation')->where(['bid'=>$bid])->column('uid');
            $where['u.id'] = $uid;
        }
        $this->_query($this->table)
                ->alias('u')
                ->field('u.*,c.phone,c.sex')
                ->join('children c', 'u.id = c.uid')
                ->equal('u.id#id,u.nickname#nickname,u.is_vip#is_vip,c.phone#phone,c.sex#sex,u.status#status')
                ->timeBetween('c.create_at#create_at')
                ->where("u.headimgurl <> ''")
                ->where($where)
                ->order('u.id desc')->page();
//        var_dump(DB::name('userinfo')->getLastSql());die;
    }
    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['addtime'] = date('Y-m-d H:i:s',$vo['add_time']);
            $vo['nickname'] = emojiDecode($vo['nickname']);
            $vo['is_false']  = '否';
            if ($vo['id'] <= 200){// 判断是否是测试用户
                $vo['is_false']  = '是';
            }
            //判断有没有完善孩子资料
            $map = [];
            $map['uid'] = $vo['id'];
            $children = DB::name('children')->where($map)->find();
            $vo['phone'] = $children['phone'];
            $vo['sex'] = '女';
            if ($children['sex'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$children['year'];
            $vo['address'] = $children['province']. '-' .$children['residence'];
            $vo['team_status'] =  0;
            $vo['is_children']  = '否';
            if ($children){
                $vo['team_status'] =  $children['team_status'];
                $vo['is_children']  = '是';
            }
            $relation_info = DB::name('relation')->where('uid', $vo['id'])->find();
            $vo['relation_id'] = $relation_info['bid'];
            $relation_user_info = DB::name($this->table)->where('id', $relation_info['bid'])->find();
            $vo['relation_name'] = emojiDecode($relation_user_info['nickname']);
        }
    }

    public function telCountInfo()
    {
        $id = input('id');
        $info_list = Db::table('tel_count')->where('uid', $id)->order('create_at desc')->select();
        foreach ($info_list as $key => $value){
            $info_list[$key]['type'] = $value['type'] == 1 ? '增加' : '消耗';
            $info_list[$key]['create_at'] = date('Y-m-d H:i:s', $value['create_at']);
        }
        $this->assign('title', '号码查看记录');
        $this->assign('list', $info_list);
        return $this->fetch();
    }

    /**
     * @Notes:查看子女详情
     * @Interface look
     * @author: zy
     * @Time: 2021/07/20 11:33
     */
    public function look()
    {
        $id = input("id", '', 'htmlspecialchars_decode');
        $map = ['uid' => $id];
        $Children = Db::name('Children')->where($map)->find();
        if ($Children['sex'] == 1){
            $Children['sex_name'] = '男';
        }else{
            $Children['sex_name'] = '女';
        }
        if ($Children['min_height'] == 0) $Children['min_height'] = '未填写';
        if ($Children['min_height'] == 999) $Children['min_height'] = '不限';
        if ($Children['max_height'] == 0) $Children['max_height'] = '未填写';
        if ($Children['max_height'] == 999) $Children['max_height'] = '不限';
        if ($Children['min_age'] == 0) $Children['min_age'] = '未填写';
        if ($Children['min_age'] == 999) $Children['min_age'] = '不限';
        if ($Children['max_age'] == 0) $Children['max_age'] = '未填写';
        if ($Children['max_age'] == 999) $Children['max_age'] = '不限';

        $sex_list = array(
            1 => '男',
            2 => '女'
        );
        // 学历列表学历
        $education_list = array(
            '0' => '不限学历',
            '1' => '中专及以下',
            '2' => '高中',
            '3' => '大专以上',
            '4' => '本科以上',
            '5' => '研究生以上',
            '6' => '博士'
        );
        // 月收入列表
        $income_list = array(
            '0'=>'暂不填写',
            '1' => '5000 以下',
            '2' => '5000 ~ 8000',
            '3' => '8000 ~ 12000',
            '4' => '12000 ~ 20000',
            '5' => '20000 ~ 30000',
            '6' => '30000 以上'
        );
        $house_list = array(
           '0' => '暂未填写',
            '1' => '已购房',
            '2' => '父母同住',
            '3' => '租房'
        );
        $car_list = array(
            '0' => '暂未填写',
            '1' => '已购车',
            '2' => '近期购车',
            '3' => '无车'
        );
        $this->sex_list = $sex_list;
        $this->children = $Children;
        $this->car_list = $car_list;
        $this->house_list = $house_list;
        $this->income_list = $income_list;
        $this->education_list = $education_list;
        $this->fetch();
    }
    //保存子女信息
    public function saveChildrenInfo()
    {
        $params = $this->request->post();
        if (empty($params)) $this->error('缺少必要参数!');
        $id = isset($params['id']) ? $params['id'] : 0;
        if ($id == 0) $this->error('缺少必要参数!');
        $up_data = array();
        foreach($params as $key => $val){
            if ($key === 'min_height' && $val == '不限') $val = 999;
            if ($key === 'max_height' && $val == '不限') $val = 999;
            if ($key === 'min_age' && $val === '不限') $val = 999;
            if ($key === 'max_age' && $val === '不限') $val = 999;
            if($key != 'id') $up_data[$key]= $val;
        }
        $res = DB::name('children')->where(['id'=>$id])->update($up_data);
        if ($res){
            $this->success('保存成功!');
        }else{
            $this->error('保存失败!');
        }
    }
    /**
     * 人工审核电话页面
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function checkphone()
    {
        $id = input('param.id');
        $children = DB::name('children')->where(['uid'=>$id])->find();
        $this->assign('info',$children);
        return $this->fetch();
    }

    /**
     * 人工审核电话页面
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function passphone()
    {
        $id = input('param.id');
        if (!$id){
            $this->error('参数错误！');
        }
        $res = DB::name('children')->where(['id'=>$id])->update(['team_status'=>2]);
        if ($res){
            $this->success('已通过','');
        }else{
            $this->error('重试');
        }
    }


    /**
     * 添加系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->applyCsrfToken();
        $this->_form($this->table, 'form');
    }

    /**
     * 编辑系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->applyCsrfToken();
        $this->_form($this->table, 'form');
    }
    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
            // 用户权限处理
            $data['authorize'] = (isset($data['authorize']) && is_array($data['authorize'])) ? join(',', $data['authorize']) : '';
            // 用户账号重复检查
            if (isset($data['id'])) unset($data['username']);
            elseif (Db::name($this->table)->where(['username' => $data['username'], 'is_deleted' => '0'])->count() > 0) {
                $this->error("账号{$data['username']}已经存在，请使用其它账号！");
            }
        } else {
            $data['authorize'] = explode(',', isset($data['authorize']) ? $data['authorize'] : '');
            $this->authorizes = Db::name('SystemAuth')->where(['status' => '1'])->order('sort desc,id desc')->select();
        }
    }

    /**
     * 禁用系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        $this->applyCsrfToken();
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->applyCsrfToken();
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止删除！');
        }
        $this->applyCsrfToken();
        $this->_delete($this->table);
    }

    /**
     * 注销用户
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function newglory()
    {
        $this->applyCsrfToken();
        $user_id = $this->request->post('id');
        $user_status = $this->request->post('status');
        $user_info = Db::table($this->table)->where('id', $user_id)->find();
        if($user_status){
            $up_data['status'] = 0; //注销用户时,设置为 0; 该字段为后添加字段,只有 status = 0 时为注销;
        }else{
            $up_data['status'] = 1; //激活用户时,设置为 1;
        }
        Db::startTrans();
        try {
            Db::table($this->table)->where('id', $user_id)->update($up_data);
            Db::table('children')->where('uid', $user_id)->update($up_data);
            Db::table('wechat_fans')->where('unionid', $user_info['unionid'])->update($up_data);
            Db::commit();
            $this->success('修改成功!','');
        } catch (\ErrorException $e){
            $this->error('重试');
        }
    }

}
