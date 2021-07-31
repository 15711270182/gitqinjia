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
use library\service\AdminService;
use library\service\MenuService;
use library\tools\Data;
use think\Console;
use think\Db;
use think\exception\HttpResponseException;

/**
 * 系统公共操作
 * Class Index
 * @package app\admin\controller
 */
class Index extends Controller
{

    /**
     * 显示后台首页
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '系统管理后台';
        $auth = AdminService::instance()->apply(true);
        $this->menus = MenuService::instance()->getTree();
        if (empty($this->menus) && !$auth->isLogin()) {
            $this->redirect('@admin/login');
        } else {
            $this->fetch();
        }
    }

    /**
     * 后台环境信息
     */
    public function main()
    {
        $this->think_ver = \think\App::VERSION;
        $this->mysql_ver = Db::query('select version() as ver')[0]['ver'];
//        $this->fetch();
        //新增授权用户
        $where_m[] = ['openid','<>',0];
        $where_m[] = ['openid','<>',1];
        $this->registTotal = Db::name('miniapp_userinfo')->where($where_m)->count();
        $this->registToday = Db::name('miniapp_userinfo')->where($where_m)->whereTime('create_at', 'today')->count();
        $this->registBefore = Db::name('miniapp_userinfo')->where($where_m)->whereTime('create_at', 'yesterday')->count();
        //完善资料用户
        $where_u[] = ['info_status','=',2];
        $where_u[] = ['is_ban','=',1];
        $where_u[] = ['is_test','=',0];
        $this->infoTotal = Db::name('inlove_user')->where($where_u)->count();
        $this->infoToday = Db::name('inlove_user')->where($where_u)->whereTime('create_time', 'today')->count();
        $this->infoBefore = Db::name('inlove_user')->where($where_u)->whereTime('create_time', 'yesterday')->count();
        //完善资料用户 + 双认证
        $where_u[] = ['education_status','=',2];
        $this->authTotal = Db::name('inlove_user')->where($where_u)->count();
        $this->authToday = Db::name('inlove_user')->where($where_u)->whereTime('create_time', 'today')->count();
        $this->authBefore = Db::name('inlove_user')->where($where_u)->whereTime('create_time', 'yesterday')->count();
        //订单数
        $this->orderTotal = Db::name('inlove_orders')->where(['status'=>1])->count();
        $this->orderHyToday = Db::name('inlove_orders')->where(['status'=>1,'type'=>1])->whereTime('create_time', 'today')->count();
        $this->orderGzToday = Db::name('inlove_orders')->where(['status'=>1,'type'=>2])->whereTime('create_time', 'today')->count();
        $this->orderMoneyHyToday = Db::name('inlove_orders')->where(['status'=>1,'type'=>1])->whereTime('create_time', 'today')->sum('total_money');
        $this->orderMoneyGzToday = Db::name('inlove_orders')->where(['status'=>1,'type'=>2])->whereTime('create_time', 'today')->sum('total_money');
        //学历待审核数
        $this->authAudit = Db::name('inlove_auth_record')->where(['status'=>0,'type'=>5])->count();

        //推广数据统计
        $target_uid = 234;
        $date = date('Y-m-d');
        $this->totalJson = ['xs' => [], 'ys' => []];
        if($date == date('Y-m-d')){
            $h = date('H')+1;
        }else{
            $h = 24;
        }
        for ($i = 0; $i <= $h; $i++) {
            $this->totalJson['xs'][] = date( $i . '点');
            $item = ['_1' => 0,'_2'=>0,'_3'=>0];
            $start_time = date('Y-m-d '.$i.':00:00',strtotime($date));
            $end_time = date('Y-m-d '.$i.':59:59',strtotime($date));
            $where = "target_uid = {$target_uid} and create_time between '{$start_time}' and '{$end_time}'";
            $list = Db::name('inlove_record_statistics')->field('count(*) count,type')->where($where)->group('type')->select();

            foreach ($list as $vo) $item["_{$vo['type']}"] = $vo['count'];
            $this->totalJson['ys']['_1'][] = $item['_1']; //1用户访问访问数
            $this->totalJson['ys']['_2'][] = $item['_2']; //2喜欢点击数
            $this->totalJson['ys']['_3'][] = $item['_3'];//3点击无感数
        }
        $this->enjoyNum =  Db::name('inlove_record_statistics')->where(['target_uid'=>$target_uid,'type'=>2])->count();
        $this->averseNum =  Db::name('inlove_record_statistics')->where(['target_uid'=>$target_uid,'type'=>3])->count();
        $this->visitNum =  Db::name('inlove_record_statistics')->where(['target_uid'=>$target_uid,'type'=>1])->count();
        $this->date = $date;
        $this->fetch();
    }

    /**
     * 修改密码
     * @login true
     * @param integer $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pass($id)
    {
        $this->applyCsrfToken();
        if (intval($id) !== intval(session('user.id'))) {
            $this->error('只能修改当前用户的密码！');
        }
        if (!AdminService::instance()->isLogin()) {
            $this->error('需要登录才能操作哦！');
        }
        if ($this->request->isGet()) {
            $this->verify = true;
            $this->_form('SystemUser', 'admin@user/pass', 'id', [], ['id' => $id]);
        } else {
            $data = $this->_input([
                'password'    => $this->request->post('password'),
                'repassword'  => $this->request->post('repassword'),
                'oldpassword' => $this->request->post('oldpassword'),
            ], [
                'oldpassword' => 'require',
                'password'    => 'require|min:4',
                'repassword'  => 'require|confirm:password',
            ], [
                'oldpassword.require' => '旧密码不能为空！',
                'password.require'    => '登录密码不能为空！',
                'password.min'        => '登录密码长度不能少于4位有效字符！',
                'repassword.require'  => '重复密码不能为空！',
                'repassword.confirm'  => '重复密码与登录密码不匹配，请重新输入！',
            ]);
            $user = Db::name('SystemUser')->where(['id' => $id])->find();
            if (md5($data['oldpassword']) !== $user['password']) {
                $this->error('旧密码验证失败，请重新输入！');
            }
            if (Data::save('SystemUser', ['id' => $user['id'], 'password' => md5($data['password'])])) {
                $this->success('密码修改成功，下次请使用新密码登录！', '');
            } else {
                $this->error('密码修改失败，请稍候再试！');
            }
        }
    }

    /**
     * 修改用户资料
     * @login true
     * @param integer $id 会员ID
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function info($id = 0)
    {
        if (!AdminService::instance()->isLogin()) {
            $this->error('需要登录才能操作哦！');
        }
        $this->applyCsrfToken();
        if (intval($id) === intval(session('user.id'))) {
            $this->_form('SystemUser', 'admin@user/form', 'id', [], ['id' => $id]);
        } else {
            $this->error('只能修改登录用户的资料！');
        }
    }

    /**
     * 清理运行缓存
     * @auth true
     */
    public function clearRuntime()
    {
        try {
            Console::call('clear');
            Console::call('xclean:session');
            $this->success('清理运行缓存成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            $this->error("清理运行缓存失败，{$e->getMessage()}");
        }
    }

    /**
     * 压缩发布系统
     * @auth true
     */
    public function buildOptimize()
    {
        try {
            Console::call('optimize:route');
            Console::call('optimize:schema');
            Console::call('optimize:autoload');
            Console::call('optimize:config');
            $this->success('压缩发布成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            $this->error("压缩发布失败，{$e->getMessage()}");
        }
    }

}
