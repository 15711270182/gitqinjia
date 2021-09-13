<?php
/**
 * User: tao
 * Date: 2021/7/27 14:42
 */


namespace app\admin\controller;


use library\Controller;
use think\Db;

class Datacount extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'statistical_report';
    public $table2 = 'tel_collection';
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
        // 获取今日数据
        $today = [];
        $start = strtotime(date('Ymd').'000000');
        $end = strtotime(date('Ymd').'235959');
        $today['user_count'] = count($this->getNewUserList($start, $end));
        $today['children_count'] = count($this->getChildrenList($start, $end));
        $today['tel_count'] = count($this->getTelList($start, $end));
        $today['info_count'] = count($this->getInfoList($start, $end)); // 完善资料数

        // 根据日期范围,统计数据
        $date = input('date');
        $search_list = [];
        $date_list = array();
        if (strlen($date)){
            $start_date = explode(' - ', $date)[0];
            $end_date = explode(' - ', $date)[1];
            $date_list = $this->getDateFromRange($start_date, $end_date);
        }
        foreach ($date_list as $key => $value){
            $start = strtotime(date($value['start']).'000000');
            $end = strtotime(date($value['start']).'235959');
            $date = $value['start'];
            $search_list[$key]['date'] = $date;
            $search_list[$key]['date_name'] = substr($date,0,4).'-'.substr($date,4,2).'-'.substr($date,6,2);
            $search_list[$key]['user_count'] = count($this->getNewUserList($start, $end));
            $search_list[$key]['children_count'] = count($this->getChildrenList($start, $end));
            $search_list[$key]['tel_count'] = count($this->getTelList($start, $end));
            $search_list[$key]['tj_count'] = count($this->getTjList($start, $end));
            $search_list[$key]['info_count'] = count($this->getInfoList($start, $end));

        }
        cache('statistical_data', $search_list);
        $this->assign('today', $today);
        $this->assign('list', $search_list);
        return $this->fetch();
    }

    /**
     * 根据时间范围,返回时间列表
     * @param string $start_date    '2021-07-27'
     * @param string $end_date      '2021-07-28'
     * @return array
     */
    function getDateFromRange($start_date, $end_date){
        $start_time_stamp = strtotime($start_date);
        $end_time_stamp = strtotime($end_date);
        $days = ($end_time_stamp-$start_time_stamp)/86400+1;
        $date_list = array();
        for($i=0; $i<$days; $i++){
            $date = $start_time_stamp+(86400*$i);
            if ($date <= strtotime(date('Ymd'))){
                $date_list[$i]['start'] = date('Ymd', $date);
                $date_list[$i]['end'] = date('Ymd', $date);
            }
        }
        return $date_list;
    }

    /**
     * 根据时间范围获取用户数
     * @param string $start 时间戳
     * @param string $end 时间戳
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNewUserList($start, $end)
    {
        return Db::table('userinfo')->where('add_time','between',[$start, $end])->order('add_time desc')->select();
    }

    /**
     * 根据时间范围获取填写资料数
     * @param $start
     * @param $end
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildrenList($start, $end)
    {
        return Db::table('children')->where('create_at','between',[$start, $end])->order('create_at desc')->select();
    }
    /**
     * 根据时间范围获取完善资料数
     * @param $start
     * @param $end
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildrenPerList($start, $end)
    {
        return Db::table('children')->where('create_at','between',[$start, $end])->order('create_at desc')->select();
    }

    /**
     * 根据时间范围获取查看号码数
     * @param $start
     * @param $end
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTelList($start, $end)
    {
        return DB::name('tel_collection')->where('create_at', 'between',[$start, $end])->where(['status'=>1,'type'=>1])->order('create_at desc')->select();

//        return DB::name("tel_collection")->alias('t')->join('tel_collection telB','t.uid = telB.bid and t.bid = telB.uid')
//            ->where('t.create_at', 'between',[$start, $end])->where(['t.status'=>1,'telB.status'=>1])->order('t.create_at desc')->select();
//        var_dump(DB::name("tel_collection")->getLastSql());die;
    }

    /**
     * 根据时间范围获取完善资料数
     * @param $start
     * @param $end
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfoList($start, $end)
    {
        $start = date('Y-m-d H:i:s',$start);
        $end = date('Y-m-d H:i:s',$end);
        return Db::table('children')->where('info_check_time', 'between',[$start, $end])->order('info_check_time desc')->select();
    }
    /**
     * 根据时间范围获取拉取推荐人数
     * @param $start
     * @param $end
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTjList($start, $end)
    {
        $start = date('Ymd',$start);
        $end = date('Ymd',$end);
        return Db::table('recommend_record')->where('date', 'between',[$start, $end])->group('uid')->order('date desc')->select();
    }
    /**
     * 根据时间范围获取被推荐人数
     * @param $start
     * @param $end
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBTjList($date,$field)
    {
        return $this->_query('recommend_record')->field($field)->where(['date'=>$date])->group('recommendid')->page();
    }
    /**
     * 新增用户列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function newUser()
    {
        $date = input('date');
        if ($date){
            $start = strtotime($date .'000000');
            $end = strtotime($date.'235959');
        }else{
            $start = strtotime(date('Ymd').'000000');
            $end = strtotime(date('Ymd').'235959');
        }
        $this->_query('userinfo')->where('add_time','between',[$start, $end])->order('add_time desc')->page();
    }
    protected function _newUser_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['sex'] = $vo['sex'] == 1 ? '男':'女';
            $vo['nickname'] = emojiDecode($vo['nickname']);
            $vo['add_time'] = date('Y-m-d H:i:m', $vo['add_time']);
        }
    }
    /**
     * 填写资料列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function newChildren()
    {
        $date = input('date');
        if ($date){
            $start = strtotime($date .'000000');
            $end = strtotime($date.'235959');
        }else{
            $start = strtotime(date('Ymd').'000000');
            $end = strtotime(date('Ymd').'235959');
        }
        $this->_query('children')->where('create_at','between',[$start, $end])->order('create_at desc')->page();
    }
    protected function _newChildren_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['create_at'] = date('Y-m-d H:i:m', $vo['create_at']);
        }
    }
    /**
     * 完善资料列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function infoChildren()
    {
        $date = input('date');
        if ($date){
            $start = strtotime($date .'000000');
            $end = strtotime($date.'235959');
        }else{
            $start = strtotime(date('Ymd').'000000');
            $end = strtotime(date('Ymd').'235959');
        }
        $start = date('Y-m-d H:i:s',$start);
        $end = date('Y-m-d H:i:s',$end);
        $this->_query('children')->where('info_check_time', 'between',[$start, $end])->order('info_check_time desc')->page();
    }

    /**
     * 查看号码列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lookTel()
    {
        $date = input('date');
        if ($date){
            $start = strtotime($date .'000000');
            $end = strtotime($date.'235959');
        }else{
            $start = strtotime(date('Ymd').'000000');
            $end = strtotime(date('Ymd').'235959');
        }
        $this->_query($this->table2)->where('create_at', 'between',[$start, $end])->where(['status'=>1,'type'=>1])->order('create_at desc')->page();
    }
    protected function _lookTel_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            // 用户信息
            $user_condition = array();
            $user_condition['id'] = $vo['uid'];
            $user_info = $this->getUserInfo($user_condition);
            // 被查看者信息
            $looked_condition = array();
            $looked_condition['id'] = $vo['bid'];
            $looked_info = $this->getUserInfo($looked_condition);
            $vo['name'] = emojiDecode($user_info['nickname']);
            $vo['look_name'] = emojiDecode($looked_info['nickname']);
            $vo['create_at'] = date('Y-m-d H:i:m', $vo['create_at']);

            //被查看者是否查看
            $vo['status'] = 0; //对方未查看
            $status = DB::name('tel_collection')->where(['uid'=>$vo['bid'],'bid'=>$vo['uid'],'type'=>2,'is_read'=>1])->count();
            if(!empty($status)){
                 $vo['status'] = 1;
            }
        }
    }

    /**
     * 查看推荐列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lookTj()
    {
        $date = input('date');
        $date = date('Ymd',strtotime($date));
        $field = 'date,recommendid,count(*) as tj_count';
        $this->_query('recommend_record')->field($field)->where(['date'=>$date])->group('recommendid')->order('tj_count desc')->page();
    }
    protected function _lookTj_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $looked_condition = array();
            $looked_condition['id'] = $vo['recommendid'];
            $looked_info = $this->getUserInfo($looked_condition);
            $vo['nickname'] = emojiDecode($looked_info['nickname']);
            $vo['headimgurl'] = $looked_info['headimgurl'];
            $cinfo = DB::name('children')->where(['uid'=>$vo['recommendid']])->field('sex,year,province,residence,weight_score')->find();
            $vo['sex'] = $cinfo['sex']==1?'男':'女';
            $vo['age'] = (int)date('Y') - (int)$cinfo['year'];
            $vo['address'] = $cinfo['province']. '-' .$cinfo['residence'];
            $vo['weight_score'] = $cinfo['weight_score'];
            $vo['create_at'] = $vo['date'];
        }
    }

     /**
     * 查看浏览（被）列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lookBrowse()
    {
        $date = input('date');
        $type = input('type');
        $this->type = $type;
        $start_time = date('Y-m-d 00:00:00',strtotime($date));
        $end_time = date('Y-m-d 23:59:59',strtotime($date));
        if($type == 1){ //浏览
            $field = 'uid as id,count(*) as browse_num';
            $group = 'uid';
        }else{ //被浏览
            $field = 'bid as id,count(*) as browse_num';
            $group = 'bid';
        }
        $this->_query('view_info_record')->field($field)->where('create_time', 'between',[$start_time, $end_time])->group($group)->order('browse_num desc')->page();
    }
    protected function _lookBrowse_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $uinfo = Db::table('userinfo')->where(['id'=>$vo['id']])->find();
            $cinfo = DB::name('children')->where(['uid'=>$vo['id']])->field('sex,year,province,residence,weight_score')->find();
            $vo['nickname'] = emojiDecode($uinfo['nickname']);
            $vo['headimgurl'] = $uinfo['headimgurl'];
            $vo['sex'] = $cinfo['sex']==1?'男':'女';
            $vo['age'] = (int)date('Y') - (int)$cinfo['year'];
            $vo['address'] = $cinfo['province']. '-' .$cinfo['residence'];
        }
    }

    /**
     * @Notes:短信发送列表
     * @Interface sendList
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function sendList(){
        $this->title = '发送短信列表';
        $this->_query('send_message_record')->dateBetween('create_time')->equal('bid,type,is_read')->order('create_time desc')->page();
    }
    protected function _sendList_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $uinfo = $this->getUserInfo(['id'=>$vo['bid']]);
            $binfo = $this->getUserInfo(['id'=>$vo['uid']]);
            $cinfo = DB::name('children')->where(['uid'=>$vo['bid']])->field('sex,year,province,residence,weight_score')->find();
            $cbinfo = DB::name('children')->where(['uid'=>$vo['uid']])->field('sex,year,province,residence,weight_score')->find();
            $vo['nickname'] = emojiDecode($uinfo['nickname']);
            $vo['headimgurl'] = $uinfo['headimgurl'];
            $vo['sex'] = $cinfo['sex']==1?'男':'女';
            $vo['age'] = (int)date('Y') - (int)$cinfo['year'];
            $vo['address'] = $cinfo['province']. '-' .$cinfo['residence'];

            $vo['b_nickname'] = emojiDecode($binfo['nickname']);
            $vo['b_headimgurl'] = $binfo['headimgurl'];
            $vo['b_sex'] = $cbinfo['sex']==1?'男':'女';
            $vo['b_age'] = (int)date('Y') - (int)$cbinfo['year'];
            $vo['b_address'] = $cbinfo['province']. '-' .$cbinfo['residence'];
        }
    }

    /**
     * @param array $condition 查询条件
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo($condition)
    {
        if (empty($condition)) return array();
        return Db::table('userinfo')->where($condition)->find();
    }

    public function ReportForms()
    {
        $type = input('type');
        if ($type == 0){
            $statistical_data = cache('statistical_data');
            if (empty($statistical_data)) return;
            $statistical = $this->createStatisticalFormat($statistical_data);
        }elseif($type == 1){// 近四周报表
            $weeks = $this->getWeeks();
            foreach ($weeks as $key => $value){
                $start = $value['start'].'000000';
                $end = $value['end'].'235959';
                $weeks[$key]['date'] = substr($value['start'], 4).'-'.substr($value['end'],4);
                $weeks[$key]['start'] = strtotime(date($start));
                $weeks[$key]['end'] = strtotime(date($end));
            }
            $search_list = array();
            foreach ($weeks as $key => $value){
                $search_list[$key]['date'] = $value['date'];
                $search_list[$key]['user_count'] = count($this->getNewUserList($value['start'], $value['end']));
                $search_list[$key]['children_count'] = count($this->getChildrenList($value['start'], $value['end']));
                $search_list[$key]['tel_count'] = count($this->getTelList($value['start'], $value['end']));
            }
            $statistical = $this->createStatisticalFormat($search_list);
        }else{
            $months = $this->getMonths();
            foreach ($months as $key => $value){
                $start = str_replace('-','',$value['date']).'01000000';
                $end = str_replace('-','',$value['date']).'31235959';
                $months[$key]['start'] = strtotime(date($start));
                $months[$key]['end'] = strtotime(date($end));
            }
            $search_list = array();
            foreach ($months as $key => $value){
                $search_list[$key]['date'] = $value['date'];
                $search_list[$key]['user_count'] = count($this->getNewUserList($value['start'], $value['end']));
                $search_list[$key]['children_count'] = count($this->getChildrenList($value['start'], $value['end']));
                $search_list[$key]['tel_count'] = count($this->getTelList($value['start'], $value['end']));
            }
            $statistical = $this->createStatisticalFormat($search_list);
        }
        $this->assign('statistical', $statistical);
        return $this->fetch();
    }

    /**
     * 获取近4周
     * @return array
     */
    public function getWeeks()
    {
        $weeks = array();
        for ($i=4; $i > 0; $i--){
            $weeks[$i]['start'] = date('Ymd', strtotime('-'.$i.' week Monday'));
            $weeks[$i]['end'] = date('Ymd', strtotime('-'.($i-1) .' week Sunday'));
        }
        return $weeks;
    }

    /**
     * 获取近6个月
     * @return array
     */
    public function getMonths()
    {
        $months = array();
        for ($i = 5; $i >= 0; $i--){
            $months[$i]['date'] = date("Y-m",mktime(0, 0,0,date("m")- $i,1,date("Y")));
        }
        return $months;
    }


    /**生成报表格式
     * @param $statistical_data
     * @return array
     */
    public function createStatisticalFormat($statistical_data)
    {
        if (empty($statistical_data)) return array();
        $statistical = array();
        $statistical['xs'] = array();
        $statistical['ys'] = array();
        $statistical['ys']['user'] = array();
        $statistical['ys']['children'] = array();
        $statistical['ys']['tel'] = array();
        foreach ($statistical_data as $key => $value){
            array_push($statistical['xs'], $value['date']);
            array_push($statistical['ys']['user'], $value['user_count']);
            array_push($statistical['ys']['children'], $value['children_count']);
            array_push($statistical['ys']['tel'], $value['tel_count']);
        }
        return $statistical;
    }
}