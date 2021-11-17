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
use app\api\service\UsersService;
use app\api\service\InterfaceService;
use app\api\service\Send as SendService;
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
    public $table2 = 'qx_discount_config';
    public $table3 = 'qx_search_record';
    public $table4 = 'qx_browse_record';

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
            ->where(['is_del'=>0])
            ->dateBetween('create_time')
            ->order('id desc')
            ->page();
    }
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $headimgurl = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('headimgurl');
            $nickname = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('nickname');
            $pair_last_num = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('pair_last_num');
            $vo['headimgurl'] = $headimgurl;
            $vo['nickname'] = emoji_decode($nickname);
            $vo['pair_last_num'] = $pair_last_num;
            $Children = Db::name('Children')->where(['uid'=>$vo['uid']])->find();
            $vo['qj_year'] = $Children['year']; 
            $vo['qj_sex'] = '女';
            if ($Children['sex'] == 1){
                $vo['qj_sex'] = '男';
            }
            $vo['qj_age'] = (int)date('Y') - (int)$Children['year'];
            $vo['address'] = $Children['province']. '-' .$Children['residence'];

            $vo['remark_sub'] = $this->subtext($vo['remark'],15);

            $sendInfo = DB::name('send_message_record')->where(['qx_id'=>$vo['id'],'uid'=>$vo['uid'],'bid'=>$vo['bj_uid'],'type'=>3])->find();
            $vo['send_status'] = 0; //未发送
            if($sendInfo){
                $vo['send_status'] = 1; //已发送
            }
            $vo['send_time'] = isset($sendInfo['create_time'])?$sendInfo['create_time']:'';
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
     * 发送短信
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function sendmsg(){
        $id = input('id');
        $this->id = $id;
        $this->fetch();
    }
    /**
     * 保存发送短信
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function sendSave(){
        $id = input('id');
        $content = input('content');
        if(empty($content)){
            $this->error('内容必填');
        }
        if(mb_strlen($content) > 20){
            $this->error('内容不可超过20个字');
        }
        $qxInfo = DB::name('qx_apply_user')->where(['id'=>$id])->find();
        if($qxInfo['sex'] == '女'){
            $content = $content.'小姐姐';
        }else{
            $content = $content.'小哥哥';
        }
        // 发送短信
        if($qxInfo['phone']){
            $b_phone = $qxInfo['phone'];
        }else{
            $service = InterfaceService::instance();
            $service->setAuth('123456','123456'); // 设置接口认证账号
            $queryDetailData = $service->doRequest('apinew/v1/query/detail',['uid'=>$qxInfo['bj_uid']]); // 发起接口请求
            if(empty($queryDetailData)){
                $this->error('接口返回错误');
            }
            $b_phone = $queryDetailData['phone'];
            DB::name('qx_apply_user')->where(['id'=>$id])->update(['phone'=>$b_phone]);
        }
        $project_id = '5Kf1g';//模板ID
        $vars = json_encode([
            'content' => $content
        ]);
        $send = new SendService();
        $msgJson = $send->sendMsg($b_phone,$project_id,$vars,1);
        custom_log('后台短信接收返回json',print_r($msgJson,true));
        $msgJson = json_decode($msgJson,true);
        if($msgJson['status'] == 'success'){
            $send_content = "有位{$content}对你非常感兴趣,稍后我们牵线老师会跟您联系,请注意接听电话或者微信消息!";
            $service = InterfaceService::instance();
            $service->setAuth('123456','123456'); // 设置接口认证账号
            $json = [
                'target_uid'=>$qxInfo['bj_uid'],
                'content'=>$send_content
            ];
            $queryData = $service->doRequest('apinew/v1/query/sendmsg',$json); // 发起接口请求
            if(empty($queryData)){
                $this->error('接口返回错误');
            }
            //添加发送记录
            $arrMsg['qx_id'] = $id;
            $arrMsg['uid'] = $qxInfo['uid'];
            $arrMsg['bid'] = $qxInfo['bj_uid'];
            $arrMsg['remark'] = '牵线用户'.$qxInfo['uid'].'发送短信给'.$qxInfo['bj_uid'];
            $arrMsg['type'] = 3;
            $arrMsg['create_time'] = date('Y-m-d H:i:s');
            DB::name('send_message_record')->insertGetId($arrMsg);

            $this->success('发送成功!');
        }
        $this->error('发送失败');
    }
    /**
     * 保存发送短信 - 测试
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function test(){
       
        $content = '身高168的小姐姐';
        // 发送短信
        $b_phone = '15711270182';
        $project_id = '5Kf1g';//模板ID
        $vars = json_encode([
            'content' => $content
        ]);
        $send = new SendService();
        $msgJson = $send->sendMsg($b_phone,$project_id,$vars,1);
        custom_log('后台短信接收返回json',print_r($msgJson,true));
        $msgJson = json_decode($msgJson,true);
        var_dump($msgJson);die;



        $project_id = '4lSb84';//模板ID
        $vars = json_encode([
            'realname' => '张',
            'url' => 'v1kj.cn/info'
        ]);
        $send = new SendService();
        $msgJson = $send->sendMsg('15711270182',$project_id,$vars);
//                custom_log('短信接收返回json',print_r($msgJson,true));
        $msgJson = json_decode($msgJson,true);
        var_dump($msgJson);die;

        if($msgJson['status'] == 'success'){
            $send_content = "有位{$content}对你非常感兴趣,稍后我们牵线老师会跟您联系,请注意接听电话或者微信消息!";
            $service = InterfaceService::instance();
            $service->setAuth('123456','123456'); // 设置接口认证账号
            $json = [
                'target_uid'=>250,
                'content'=>$send_content
            ];
            $queryData = $service->doRequest('apinew/v1/query/sendmsg',$json); // 发起接口请求
            if(empty($queryData)){
                $this->error('接口返回错误');
            }
            // //添加发送记录
            // $arrMsg['qx_id'] = $id;
            // $arrMsg['uid'] = $qxInfo['uid'];
            // $arrMsg['bid'] = $qxInfo['bj_uid'];
            // $arrMsg['remark'] = '牵线用户'.$qxInfo['uid'].'发送短信给'.$qxInfo['bj_uid'];
            // $arrMsg['type'] = 3;
            // $arrMsg['create_time'] = date('Y-m-d H:i:s');
            // DB::name('send_message_record')->insertGetId($arrMsg);

           echo '成功';die;
        }
        echo '失败';die;
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
     * 撤销牵线申请
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function revokeApply()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        $this->applyCsrfToken();
        $this->_save($this->table, ['is_del' => '1','del_time'=>date('Y-m-d H:i:s')]);
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
        $id = $this->request->post('id');
        $uid = DB::name('qx_apply_user')->where(['id'=>$id])->value('uid');
        $userinfo = DB::name('userinfo')->where(['id'=>$uid])->find();
        if($userinfo['pair_last_num'] <= 0){
            $this->error('牵线次数已用完！');
        }
        DB::name('userinfo')->where(['id'=>$uid])->setDec('pair_last_num',1);
        $pair_last_num = DB::name('userinfo')->where(['id'=>$uid])->value('pair_last_num');
        if($pair_last_num == 0){
            //会员自动取消
            $uSave['is_pair_vip'] = 0;
            $uSave['pair_vip_time'] = '0000-00-00 00:00:00';
            DB::name('userinfo')->where(['id'=>$uid])->update($uSave);
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

    /**
     * 活动配置列表
     * @auth true
     */
    public function index_config()
    {

        $this->title = '限时活动列表';
        $query = $this->_query($this->table2);
        $query->dateBetween('create_time')->equal('uid,type,is_show')->order('create_time desc')->page();
    }
     /**
     * 添加配置信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add_config()
    {
        $uid = input('uid');
        $this->type = '';
        if($uid){
            $this->uid = $uid;
            $this->type = 2;
        }
        $this->_form($this->table2, 'form');
    }
    /**
     * 编辑配置信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->type = '';
        $this->uid = '';
        $this->_form($this->table2, 'form');
    }
    /**
     * 数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
            $start_time = input('start_time');
            $end_time = input('end_time');
            if(empty($start_time) || empty($end_time)){
                $this->error('活动时间不能为空');
            }
            if($start_time > $end_time){
                 $this->error('结束时间不能小于开始时间');
            }
            $discount_price = input('discount_price');
            $data['discount_price'] = $discount_price*100;
        } else {
            if(isset($data['discount_price'])){
                 $data['discount_price'] = $data['discount_price']/100;
            }
        }
    }
    /**
     * 关闭活动
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function close()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        $this->applyCsrfToken();
        $this->_save($this->table2, ['is_show' => '0']);
    }

    /**
     * 开启活动
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function open()
    {
        $this->applyCsrfToken();
        $this->_save($this->table2, ['is_show' => '1']);
    }
    /**
     * 用户搜索条件列表
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index_find()
    {
        $this->title = '用户搜索条件列表';
        $uidArr = $this->UserConfig();
        $this->_query($this->table3)
            ->equal('uid,sex')
            ->dateBetween('create_time')
            ->whereNotIn('uid',$uidArr)
            ->order('id desc')
            ->page();
    }
    protected function _index_find_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $headimgurl = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('headimgurl');
            $nickname = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('nickname');
            $vo['headimgurl'] = $headimgurl;
            $vo['nickname'] = emoji_decode($nickname);
            $vo['new_sex'] = $vo['sex'];
            if ($vo['sex'] == 1){
                $vo['sex'] = '男';
            }else{
                 $vo['sex'] = '女';
            }
            if($vo['minage'] == '999'){
                $vo['minage'] = '不限';
            }
            if($vo['maxage'] == '999'){
                $vo['maxage'] = '不限';
            }
            if($vo['minheight'] == '999'){
                $vo['minheight'] = '不限';
            }
            if($vo['maxheight'] == '999'){
                $vo['maxheight'] = '不限';
            }
            $Children = Db::name('Children')->where(['uid'=>$vo['uid']])->find();
            $vo['age'] = (int)date('Y') - (int)$Children['year'];
            $vo['address'] = $Children['province']. '-' .$Children['residence'];
            $vo['phone'] = $Children['phone'];
        }
    }
    //筛选列表
    public function find_list(){
        $page = input('page') ?: '1';
        $sex = input('new_sex');
        $minage = input('minage');
        $maxage = input('maxage');
        $minheight = input('minheight');
        $maxheight = input('maxheight');
        $education = input('education');
        $salary = input('salary');
        if(empty($sex) || empty($minage) || empty($maxage) || empty($minheight) || empty($maxheight) || empty($education) || empty($salary)){
            $this->error('参数错误');
        }
        $this->new_sex = $sex;
        $this->minage = $minage;
        $this->maxage = $maxage;
        $this->minheight = $minheight;
        $this->maxheight = $maxheight;
        $this->education = $education;
        $this->salary = $salary;
        if($minage == '不限'){
            $minage = '999';
        }
        if($maxage == '不限'){
            $maxage = '999';
        }
        if($minheight == '不限'){
            $minheight = '999';
        }
        if($maxheight == '不限'){
            $maxheight = '999';
        }
        $new_sex = 1;
        if($sex == 1){
            $new_sex = 2;
        }
        $service = InterfaceService::instance();
        $service->setAuth('123456','123456'); // 设置接口认证账号
        $json = [
            'sex'=>$new_sex,
            'minage'=>$minage,
            'maxage'=>$maxage,
            'minheight'=>$minheight,
            'maxheight'=>$maxheight,
            'education'=>$education,
            'salary'=>$salary
        ];
        $queryData = $service->doRequest('apinew/v1/query/lists?page='.$page,$json); // 发起接口请求
        if(empty($queryData)){
             $this->error('暂无数据');
        }
        $data = $queryData['data'];
        foreach($data as $k=>$v){
            $data[$k]['title'] = $v['sex'].'·'.$v['year'].'('.$v['animals'].')'.'·'.$v['education'];
            $data[$k]['sex'] = 2;
            if($v['sex'] == '男'){
                $data[$k]['sex'] = 1;
            }
            unset($data[$k]['year']);
            unset($data[$k]['animals']);
            unset($data[$k]['education']);
        }
        $last_page = $queryData['last_page'];
        $pageData = [];
        for($i=1;$i<=$last_page;$i++){
            $pageData[] = $i;
        }
        $this->totalCount = $queryData['total'];//总条数
        $this->current_page = $queryData['current_page'];//当前页数
        $this->totalPage = $queryData['last_page']; //总页数
        $this->pageData = $pageData;
        $this->list = $data;
        $this->fetch();
    }

    /**
     * @Notes:筛选列表 详情
     * @Interface find_detail
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function find_detail(){
        $uid = input('uid');
        if(empty($uid)){
            $this->error('uid参数错误');
        }
        $this->uid = $uid;
        $service = InterfaceService::instance();
        $service->setAuth('123456','123456'); // 设置接口认证账号
        $queryData = $service->doRequest('apinew/v1/query/detail',['uid'=>$uid]); // 发起接口请求
        if(empty($queryData)){
            $this->error('接口返回错误');
        }
        $queryData['photoList'] = explode(',',$queryData['photo']);
        $this->vo = $queryData;
        $this->fetch();
    }
    /**
     * 页面浏览时长列表
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index_browse()
    {
        $uidArr = $this->UserConfig();
        $where['type'] = [1,2,3];
        $this->title = '页面浏览时长列表';
        $this->_query($this->table4)
            ->equal('type,uid')
            ->where($where)
            ->whereNotIn('uid',$uidArr)
            ->dateBetween('create_time')
            ->order('browse_duration desc')
            ->page();
    }
    protected function _index_browse_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $headimgurl = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('headimgurl');
            $nickname = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('nickname');
            $vo['headimgurl'] = $headimgurl;
            $vo['nickname'] = emoji_decode($nickname);

            $Children = Db::name('Children')->where(['uid'=>$vo['uid']])->find();
            if ($Children['sex'] == 1){
                $vo['sex'] = '男';
            }else{
                 $vo['sex'] = '女';
            }
            $vo['age'] = (int)date('Y') - (int)$Children['year'];
            $vo['address'] = $Children['province']. '-' .$Children['residence'];
            $vo['phone'] = $Children['phone'];
        }
    }

    /**
     * @Notes:搜索列表导出功能
     * @Interface browse_export
     * @author: zy
     * @Time: 2021/09/27
     */
    public function browse_export(){
        $whereTime = '';
        $uidArr = $this->UserConfig();
        $create_time = urldecode(input('create_time'));
        $uid = input('uid');
        $type = input('type');
        if($create_time){
            $time = explode(' - ',$create_time);
            $start_time = date('Y-m-d 00:00:00',strtotime($time[0]));
            $end_time = date('Y-m-d 23:59:59',strtotime($time[1]));
            $whereTime = "create_time between '{$start_time}' and '{$end_time}' ";
        }
        if($uid){$where['uid'] = $uid;}
        $xlsname = "牵线服务浏览数据";  //文件名
        if($type){
            if($type == 1){
                $type_name = '服务详情';
            }elseif($type == 2){
                $type_name = '筛选结果';
            }else{
                $type_name = '嘉宾资料';
            }
            $xlsname = "牵线服务浏览数据-".$type_name;  //文件名
            $where['type'] = $type;
        }else{
            $where['type'] = [1,2,3];
        }
        $data = DB::name('qx_browse_record')
            ->where($where)
            ->whereNotIn('uid',$uidArr)
            ->where($whereTime)
            ->order('browse_duration desc')
            ->select();
        if(empty($data)){
            $this->error('暂无导出数据');
        }
        $newData = [];
        $uidColumn = array_column($data,'uid');
        $nickname = DB::name('userinfo')->where(['id'=>$uidColumn])->column('id,nickname');
        $sex = DB::name('Children')->where(['uid'=>$uidColumn])->column('uid,sex');
        $year = DB::name('Children')->where(['uid'=>$uidColumn])->column('uid,year');
        $education = DB::name('Children')->where(['uid'=>$uidColumn])->column('uid,education');
        $phone = DB::name('Children')->where(['uid'=>$uidColumn])->column('uid,phone');
        foreach($data as $k=>$v){
            $newData[$k]['create_time'] = $v['create_time'];
            $newData[$k]['uid'] = $v['uid'];
            $newData[$k]['phone'] = isset($phone[$v['uid']])?$phone[$v['uid']]:'';
            $newData[$k]['nickname'] = isset($nickname[$v['uid']])?emoji_decode($nickname[$v['uid']]):'匿名';
            $newData[$k]['sex'] = isset($sex[$v['uid']])?($sex[$v['uid']] == 1?'男':'女'):'未知';
            $newData[$k]['age'] = isset($year[$v['uid']])?(int)date('Y') - (int)$year[$v['uid']]:'未知';
            $newData[$k]['education'] = isset($education[$v['uid']])?UsersService::education($education[$v['uid']]):'';

            $newData[$k]['browse_duration'] = $v['browse_duration'];
            if($v['type'] == 1){
                $newData[$k]['type'] = '服务详情';
            }elseif($v['type'] == 2){
                $newData[$k]['type'] = '筛选结果';
            }else{
                $newData[$k]['type'] = '嘉宾资料';
            }
        }
        $title = ['浏览时间','用户uid','手机号','昵称','性别','年龄','学历','浏览时长','类型']; //标题
//        $xlsname = "牵线服务浏览数据";  //文件名
        writeExcel($title,$newData , $xlsname);
    }
    /**
     * 点击立即咨询列表
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index_click()
    {
        $uidArr = $this->UserConfig();
        $where['type'] = [4,5];
        $this->title = '点击立即咨询列表';
        $this->_query($this->table4)
            ->equal('type,uid')
            ->where($where)
            ->whereNotIn('uid',$uidArr)
            ->dateBetween('create_time')
            ->order('id desc')
            ->page();
    }
    protected function _index_click_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $headimgurl = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('headimgurl');
            $nickname = DB::name('userinfo')->where(['id'=>$vo['uid']])->value('nickname');
            $vo['headimgurl'] = $headimgurl;
            $vo['nickname'] = emoji_decode($nickname);

            $Children = Db::name('Children')->where(['uid'=>$vo['uid']])->find();
            if ($Children['sex'] == 1){
                $vo['sex'] = '男';
            }else{
                 $vo['sex'] = '女';
            }
            $vo['age'] = (int)date('Y') - (int)$Children['year'];
            $vo['address'] = $Children['province']. '-' .$Children['residence'];
            $vo['phone'] = $Children['phone'];
        }
    }

    //白名单用户  不显示
    public function UserConfig(){
        $user = ['479','218','677','346','1001','1234','514','1881','354'];
        return $user;
    }
}
