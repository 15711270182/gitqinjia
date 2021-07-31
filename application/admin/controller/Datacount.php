<?php
/**
 * User: tao
 * Date: 2021/7/27 14:42
 */


namespace app\admin\controller;


use think\Controller;
use think\Db;

class Datacount extends Controller
{
    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
        }
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
        return Db::table('tel_collection')->where('create_at', 'between',[$start, $end])->order('create_at desc')->select();
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
        $list = $this->getNewUserList($start, $end);
        foreach ($list as $key => $value){
            $list[$key]['sex'] = $value['sex'] == 1 ? '男':'女';
            $list[$key]['nickname'] = emojiDecode($value['nickname']);
            $list[$key]['add_time'] = date('Y-m-d H:i:m', $value['add_time']);
        }
        $this->assign('list', $list);
        return $this->fetch();
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
        $list = $this->getChildrenList($start, $end);
        foreach ($list as $key => $value){
            $list[$key]['sex'] = $value['sex'] == 1 ? '男':'女';
            $list[$key]['create_at'] = date('Y-m-d H:i:m', $value['create_at']);
        }
        $this->assign('list', $list);
        return $this->fetch();
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

        $list = $this->getTelList($start, $end);
        foreach ($list as $key => $value){
            // 用户信息
            $user_condition = array();
            $user_condition['id'] = $value['uid'];
            $user_info = $this->getUserInfo($user_condition);
            // 被查看者信息
            $looked_condition = array();
            $looked_condition['id'] = $value['bid'];
            $looked_info = $this->getUserInfo($looked_condition);
            $list[$key]['name'] = emojiDecode($user_info['nickname']);
            $list[$key]['look_name'] = emojiDecode($looked_info['nickname']);
            $list[$key]['create_at'] = date('Y-m-d H:i:m', $value['create_at']);
        }
        $this->assign('list', $list);
        return $this->fetch();
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
}