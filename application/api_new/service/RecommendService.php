<?php
/**
 * 推荐类
 * User: tao
 * Date: 2021/7/15 16:06
 */


namespace app\api_new\service;

use app\api_new\service\UsersService;
use app\api\model\User as UserModel;
use app\api_new\model\Children as ChildrenModel;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class RecommendService
{
    private static $aes_key;
    public function __construct()
    {
        $this::$aes_key = '5b9c2ed3e19c40e5';
    }

     /**
     * 获取首页数据列表
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getRecommend($uid,$page,$pageSize)
    {
        $user_info = Db::name('children')->where('uid', $uid)->find();
        if (empty($user_info)) return [];

        $field = "id,uid,sex,year,work,education,income,house,cart,parents,bro,hometown,native_place,residence,school,remarks,auth_status,video_url,login_last_time";
        $order = "login_last_time desc,video_url desc,full_info desc";
        $condition['sex'] = 1;
        if($user_info['sex'] == 1){
            $condition['sex'] = 2;
        }
        $condition['is_del'] = 1;
        $condition['auth_status'] = 1;
        $condition['status'] = 1;
        $condition['is_ban'] = 1;

        $notInID = $this->hadRecommend($uid); //去除 已收藏已联系用户
        $totalCountAll = Db::name('children')->where($condition)->where('uid', 'notin', $notInID)->count(); //已认证总条数
        // var_dump($totalCountAll);die;
        $totalPageAll = ceil($totalCountAll / $pageSize); //已认证总页数

        $condition['residence'] = $user_info['residence'];
        $totalCount = Db::name('children')->where($condition)->where('uid', 'notin', $notInID)->count(); //区域匹配总条数
        $totalPage = ceil($totalCount / $pageSize); //区域匹配总页数

        $data = [];
        $data['totalCountAll'] = $totalCountAll;
        $data['totalPageAll'] = $totalPageAll;

        $limit = ($page - 1) * $pageSize;
        if(empty($totalCount)){ //无需匹配城市
            $list = $this->getDataList($condition,$notInID,$field,$order,$limit,$pageSize);
            $data['list'] = $list;
            return $data;
        }
        //匹配城市  3种情况  小于  等于  大于
        if($page < $totalPage){ //查询匹配的城市
            $list = $this->getDataList($condition,$notInID,$field,$order,$limit,$pageSize,1);
            $data['list'] = $list;
            return $data;
        }
        $lastCount = 0;
        if($page == $totalPage){ //查看匹配的城市是否足够
            $list = ChildrenModel::getSelect($condition,$notInID,$field,$order,$limit,$pageSize,1);
            $count = count($list);
            $lastCount = $pageSize - $count;
            if($lastCount){
                $list_nomatch = ChildrenModel::getSelect($condition,$notInID,$field,$order,0,$lastCount,0);
                $list = array_merge($list, $list_nomatch);
            }
            cache('uid_'.$uid,$lastCount);
            $re_list = [];
            if($list){
                foreach ($list as $key => $value) {
                    $re_list[$key] = $this->userchange($value);
                }
            }
            $data['list'] = $re_list;
            return $data;
        }
        if($page > $totalPage){ //查询不匹配的城市
            $lastCount = cache('uid_'.$uid);
            $page = ($page - $totalPage - 1)*$pageSize + $lastCount; 
            // var_dump($page);
            $list = $this->getDataList($condition,$notInID,$field,$order,$page,$pageSize,0);
            $data['list'] = $list;
            return $data;
        }
    }
    public function getDataList($condition,$notInID,$field,$order,$page,$pageSize,$residence = 0)
    {
        $list = ChildrenModel::getSelect($condition,$notInID,$field,$order,$page,$pageSize,$residence);
        $re_list = [];
        if($list){
            foreach ($list as $key => $value) {
                $re_list[$key] = $this->userchange($value);
            }
        }
        return $re_list;
    }
    /**
     * 获取,已收藏,已联系的 记录,将不予推荐
     * @param $uid      用户id
     * @return string   用户id字符串
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function hadRecommend($uid)
    {
        // 获取我已收藏
        $had_collection_id_list = array();
        $had_collection['uid'] = $uid;
        $had_collection['is_del'] = 1;
        $had_collection_list = Db::table('collection')->where($had_collection)->field('bid')->select();
        foreach ($had_collection_list as $key => $value){
            array_push($had_collection_id_list, $value['bid']);
        }

        // 获取已联系
        $had_tel_id_list = array();
        $had_tel['uid'] = $uid;
        $had_tel['is_del'] = 1;
        $had_tel_list = Db::table('tel_collection')->where($had_tel)->field('bid')->select();
        foreach ($had_tel_list as $key => $value){
            array_push($had_tel_id_list, $value['bid']);
        }

        $not_id_list = array_merge($had_collection_id_list, $had_tel_id_list);
        return array_unique($not_id_list);
    }

    /**
     * 用户数据转化成前端需要的样式
     * @author zy
    */
    public function userchange($value)
    {

        $education = UsersService::education($value['education']);//学历
        $income = UsersService::income($value['income']);//收入
        if($income){
            $income = '月收入'.$income;
        }
        $cart = UsersService::cart($value['cart']);//车
        $parents = UsersService::parents($value['parents']);//父母状况
        $bro = UsersService::bro($value['bro']);//子女情况
        if(isset($value['id'])){
            $user['id'] = $value['id'];
        }
        $user['uid']  = $value['uid'];
        $user['first']  = $value['sex']==1?'男':'女';
        if ($value['year']){
            $user['first'] = $user['first'].'·'.substr($value['year'],-2).'年('.getShuXiang($value['year']).')' ;
        }
        $work = $value['work'];
        if($value['work'] && $value['income']){
            $work = $value['work'].'·';
        }
        $four = '';
        if($value['hometown']){
            $four = '老家'.$value['hometown'].'·';
        }
        if($value['native_place']){
            $four = $four.$value['native_place'].'户口·';
        }
        if($value['residence']){
            $four = $four.'现居'.$value['residence'];
        }
        switch($value['house']){
            case 0:
                $five = '暂未填写';break;
            case 1:
                $five = '有房·';break;
            case 2:
                $five = '和父母住·';break;
            default:$five = '租房·';break;
        }
        $six = '';
        if ($value['bro']){
            $six = $bro;
            if(!empty($parents)){
                $six = $parents.'·'.$bro;
            }
        }
        $user['first'] = $user['first'].'·'.$education;
        $user['second']= !empty($value['school'])?$value['school']:'';
        $user['three'] = $work.$income;
        $user['four']  = $four;
        $user['five']  = $five.$cart;
        $user['six']   = $six;
        $user['remark'] = $value['remarks'];
        //根据资料完善程度  排序
        $type_five = $user['five'];
        if($user['five'] == '暂未填写'){
            $type_five = '';
        }
        $user['video_url'] = $value['video_url'];
        $user['login_last_time'] = date('Y-m-d',strtotime($value['login_last_time']));

        //查询用户父母的名称
        $pare = UserModel::userFind(['id'=>$value['uid']]);
        $user['realname'] = $pare['realname']?$pare['realname'].'家长':'家长';
        $user['headimgurl'] = $pare['headimgurl'];
        $user['user_sex'] = $pare['sex'];
        $user['user_status'] = $pare['status'];
        $user['sex'] = $value['sex'];

        return $user;
    }
}