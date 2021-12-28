<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\api_new\controller;
use app\api_new\model\Children as ChildrenModel;
use app\api_new\service\InterfaceService;
use app\api_new\service\UsersService;
use think\Db;
use think\Queue;

use think\facade\Cache;
/**
 * 红娘牵线应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Matchmaker extends Base
{
    public  $appid = 123456;
    public  $appkey = 123456;
    /**
     * @Notes:页面数据
     * @Interface getNum
     * @return string
     * @author: zy
     * @Time: 2021/09/07
     */
    public function getNum()
    {
        $uid = $this->uid;
        //获取最优价
        $priceInfo = getDisPrice($uid);
        $list = [
            'activity_price'=>$priceInfo['activity_price'],
            'distcount_price'=>$priceInfo['distcount_price'],
            'expire_time'=>$priceInfo['expire_time'],
            'original_price'=>'6999',
            'annual_salary'=>'2000',
            'num1'=>'1000',
            'num2'=>'1200',
            'num3'=>'1000',
        ];
        return $this->successReturn($list,'成功',self::errcode_ok);
    }

    /**
     * @Notes:获取客服微信二维码图片
     * @Interface getWxcode
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function getWxcode(){
        $data['activity_price'] = '3999';
        $data['img_url'] = 'https://pics.njzec.com/kefu.jpg';
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
    /**
     * @Notes:筛选 获取嘉宾信息列表
     * @Interface getUserList
     * @author: zy
     * @Time: 2021/09/07
     */
    public function getUserList(){

        // 年龄 身高 学历 年薪
        $uid = $this->uid;
        $ask_age = input("ask_age",'');
        $ask_height = input("ask_height",'');
        $education = input("education",'');
        $salary = input("salary",'');
        $page = input('page') ?: '1';
        
        $user_sn = input("user_sn",'');
        $marriage_type = input("marriage_type",'');//婚史
        $house_type = input("house_type",''); //房子情况
        $industry = input("industry",''); //职业
        $industry_type = input("industry_type",''); //单位性质
        $is_auth = input("is_auth",''); 
        if(empty($ask_age)){
            return $this->errorReturn(self::errcode_fail,'ask_age参数不能为空');
        }
        if(empty($ask_height)){
            return $this->errorReturn(self::errcode_fail,'ask_height参数不能为空');
        }
        if(empty($education)){ //1专科以上 2本科以上 3研究生以上 4博士
            return $this->errorReturn(self::errcode_fail,'education参数不能为空');
        }
        if(empty($salary)){ //1 10万以下 2 10万-20万 3 20-30万  4 30-50万 5 50-100 6 100以上
            return $this->errorReturn(self::errcode_fail,'salary参数不能为空');
        }
        $sex = ChildrenModel::getchildrenField(['uid'=>$uid],'sex');
        $new_sex = 1;
        if($sex == 1){
           $new_sex = 2;
        }
        $age = explode('到',$ask_age);
        $height = explode('到',$ask_height);
        //添加搜索日志
        if($page == 1){
            $searchLog['uid'] = $uid;
            $searchLog['sex'] = $sex;
            $searchLog['minage'] = $age[0];
            $searchLog['maxage'] = $age[1];
            $searchLog['minheight'] = $height[0];
            $searchLog['maxheight'] = $height[1];
            $searchLog['education'] = $education;
            $searchLog['salary'] = $salary;
            $searchLog['create_time'] = date('Y-m-d H:i:s');
            DB::name('qx_search_record')->insertGetId($searchLog);
        }
        //过滤已经申请的铂金用户id
        $filters = '';
        $bjArr = DB::name('qx_apply_user')->where(['uid'=>$uid])->group('bj_uid')->column('bj_uid');
        if($bjArr){
            $filters = implode(',', $bjArr);
        }
        $service = InterfaceService::instance();
        $service->setAuth($this->appid,$this->appkey); // 设置接口认证账号
        $json = [
            'filters'=>$filters,
            'sex'=>$new_sex,
            'minage'=>$age[0],
            'maxage'=>$age[1],
            'minheight'=>$height[0],
            'maxheight'=>$height[1],
            'education'=>$education,
            'uid'=>$user_sn,
            'marriage_type'=>$marriage_type,
            'house_type'=>$house_type,
            'industry_type'=>$industry_type,
            'industry'=>$industry,
            'is_auth'=>$is_auth
        ];
        $queryData = $service->doRequest('apinew/v1/query/lists?page='.$page,$json); // 发起接口请求
        if(empty($queryData)){
             return $this->errorReturn(self::errcode_fail,'暂无数据');
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
        $uinfo = DB::name('userinfo')->where(['id'=>$uid])->find();
        $is_pair_vip = 0;
        if($uinfo['is_pair_vip'] == 1 && $uinfo['pair_vip_time'] >= date('Y-m-d H:i:s')){
            $is_pair_vip = 1;
        }
        $list = [
            'is_pair_vip'=>$is_pair_vip,//是否是牵线会员  0否  1是
            'totalCount'=>$queryData['total'], //总条数
            'current_page'=>$queryData['current_page'], //当前页数
            'totalPage'=>$queryData['last_page'], //总页数
            'list'=>$data
        ];
        return $this->successReturn($list,'成功',self::errcode_ok);
    }

    /**
     * @Notes: 获取嘉宾详情信息
     * @Interface getUserDetail
     * @author: zy
     * @Time: 2021/09/07
     */
    public function getUserDetail(){
        $uid = $this->uid;
        $bj_uid = input("bj_uid");
        if(empty($bj_uid)){
            return $this->errorReturn(self::errcode_fail,'bj_uid参数不能为空');
        }
        $service = InterfaceService::instance();
        $service->setAuth($this->appid,$this->appkey); // 设置接口认证账号
        $queryData = $service->doRequest('apinew/v1/query/detail',['uid'=>$bj_uid]); // 发起接口请求
        if(empty($queryData)){
            return $this->errorReturn(self::errcode_fail,'接口返回错误');
        }
        $qxInfo = DB::name('qx_apply_user')->where(['uid'=>$uid,'bj_uid'=>$bj_uid,'is_del'=>0])->find();
        $queryData['apply_status'] = '';
        if($qxInfo){
            $queryData['apply_status'] = $qxInfo['apply_status'];
        }
        $uinfo = DB::name('userinfo')->where(['id'=>$uid])->find();
        $queryData['is_pair_vip'] = 0;
        if($uinfo['is_pair_vip'] == 1 && $uinfo['pair_vip_time'] >= date('Y-m-d H:i:s')){
            $queryData['is_pair_vip'] = 1;
        }
        $queryData['bj_uid'] = input("bj_uid");
        unset($queryData['phone']);
        return $this->successReturn($queryData,'成功',self::errcode_ok);
    }
     /**
     * @Notes:点击牵线
     * @Interface clickMatch
     * @author: zy
     * @Time: 2021/09/07
     */
    public function clickMatch(){
        $uid = $this->uid;
        $bj_uid = input("bj_uid");
        $pair_last_num = DB::name('userinfo')->where(['id'=>$uid])->value('pair_last_num');
        if($pair_last_num == 0){
            return $this->errorReturn(self::errcode_fail,'牵线次数已用完');
        }
        $where_apply = "uid = {$uid} and is_del = 0 and (apply_status = 0 or apply_status = 1)";
        $apply_count = DB::name('qx_apply_user')->where($where_apply)->count();
        if($apply_count >= 3){
            return $this->errorReturn(self::errcode_fail,'同时牵线服务人数不宜超过3个，请等待红娘牵线结果出来后，再来申请！');
        }
        //请求铂金详情数据 添加入库
        $service = InterfaceService::instance();
        $service->setAuth($this->appid,$this->appkey); // 设置接口认证账号
        $queryData = $service->doRequest('apinew/v1/query/detail',['uid'=>$bj_uid]); // 发起接口请求
        if(empty($queryData)){
            return $this->errorReturn(self::errcode_fail,'接口返回错误');
        }
        $add['uid']    = $uid;
        $add['bj_uid'] = $bj_uid;
        $add['cover']  = $queryData['cover'];
        $add['sex']    = $queryData['sex'];
        $add['year']   = $queryData['year'];
        $add['education'] = $queryData['education'];
        $add['animals']   = $queryData['animals'];
        $add['height']    = $queryData['height'];
        $add['salary']    = $queryData['salary'];
        $add['industry']  = $queryData['industry'];
        $add['current_province'] = $queryData['current_province'];
        $add['current_city']     = $queryData['current_city'];
        $add['native_province']  = $queryData['native_province'];
        $add['native_city']      = $queryData['native_city'];
        $add['phone']      = $queryData['phone'];
        $add['create_time']      = date('Y-m-d H:i:s');
        $res = DB::name('qx_apply_user')->insertGetId($add);
        if(!$res){
            return $this->errorReturn(self::errcode_fail,'申请失败');
        }
        return $this->successReturn('','申请成功',self::errcode_ok);
    }
    /**
     * @Notes:牵线记录列表
     * @Interface matchRecord
     * @author: zy
     * @Time: 2021/09/07
     */
    public function matchRecord(){
        $uid = $this->uid;
        $page = input('page') ?: '1';
        $pageSize = input('pageSize') ?: '10';
        $where = [];
        $where['uid'] = $uid;
        $where['is_del'] = 0;
        $list = DB::name('qx_apply_user')
            ->where($where)
            ->order('create_time desc')
            ->page($page,$pageSize)
            ->select();
        if(empty($list)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        $totalCount = DB::name('qx_apply_user')->where($where)->count();
        $totalPage = ceil($totalCount/$pageSize);
        $newData = [];
        foreach($list as $k=>$v){
            $newData[$k]['uid'] = $v['bj_uid'];
            $newData[$k]['create_time'] = date('Y年m月d日',strtotime($v['create_time']));
            $newData[$k]['cover'] = $v['cover'];
            $newData[$k]['sex'] = 2;
            if($v['sex'] == '男'){
                $newData[$k]['sex'] = 1;
            }
            $newData[$k]['title'] = $v['sex'].'·'.$v['year'].'('.$v['animals'].')'.'·'.$v['education'];
            $newData[$k]['height'] = $v['height'];
            $newData[$k]['current_province'] = $v['current_province'];
            $newData[$k]['current_city'] = $v['current_city'];
            $newData[$k]['apply_status'] = $v['apply_status'];
            $newData[$k]['remark'] = $v['remark'];
        }
        $data['totalCount'] = $totalCount; //总条数
        $data['current_page'] = $page; //当前页
        $data['totalPage'] = $totalPage; //总页数
        $data['list'] = $newData;
        return $this->successReturn($data,'成功',self::errcode_ok);
    }

    /**
     * @Notes: 记录页面浏览时长
     * @Interface viewCount
     * @author: zy
     * @Time: 2021/09/14
     */
    public function viewCount(){
        $uid = $this->uid;
        $type = input('type'); //type  1图表数据页  2 搜索列表页  3详情页  4立即咨询(列表)  5 立即咨询（服务详情页）
        if(empty($type)){
            return $this->errorReturn(self::errcode_fail,'type参数不能为空');
        }
        $browse_duration = input('browse_duration','');
        if($type != 4 && $type != 5){
            if(empty($browse_duration)){
                return $this->errorReturn(self::errcode_fail,'browse_duration参数不能为空');
            }
        }
        $add['uid'] = $uid;
        $add['type'] = $type;
        $add['browse_duration'] = $browse_duration;
        $add['create_time'] = date('Y-m-d H:i:s');
        DB::name('qx_browse_record')->insertGetId($add);
        return $this->successReturn('','成功',self::errcode_ok);
    }
    /**
     * @Notes:获取支付成功表单数据
     * @Interface getFormInfo
     * @author: zy
     * @Time: 2021/09/14
     */
    public function getFormInfo(){
        $uid = $this->uid;
        $data = DB::name("children_form")->where(['uid'=>$uid])->find();
        if(!empty($data)){ //表单信息
            unset($data['create_time']);
            unset($data['update_time']);
        }else{
            //子女信息
            $children = ChildrenModel::childrenFind(['uid'=>$uid]);
            if(empty($children)){
                return $this->errorReturn(self::errcode_fail,'无用户资料');
            }
            $data = [];
            $data['sex'] = $children['sex']; 
            $data['age'] = date('Y') - $children['year'];
            $data['shuxiang'] = getShuXiang($children['year']);
            $data['xingzuo'] = get_constellation($children['year']);
            $data['year'] = substr($children['year'],-2).'年';
            $data['height'] = $children['height'];
            $data['residence_province'] = $children['province']; //现居地 省份
            $data['residence_ciity'] = $children['residence']; //现居地 城市
            $data['work'] = $children['work'];
            
            // $data['education'] = UsersService::education($children['education']);
            // $data['income'] = UsersService::income($children['income']);
            // $data['house'] = UsersService::house($children['house']);
            // $data['cart'] = UsersService::cart($children['cart']);
            // $data['parents_test'] = UsersService::parents($children['parents']); //父母情况
            // $data['bro_test'] = UsersService::bro($children['bro']); //家中排行

            $data['education'] = $children['education'];
            $data['income'] = $children['income'];
            $data['house'] = $children['house'];
            $data['cart'] = $children['cart'];
            $data['parents'] = $children['parents']; //父母情况
            $data['bro'] = $children['bro']; //家中排行


            $data['remarks'] = $children['remarks']; //相亲说明

            //择偶标准
            $data['min_age'] = $children['min_age'];
            $data['max_age'] = $children['max_age'];
            $data['min_height'] = $children['min_height'];
            $data['max_height'] = $children['max_height'];
            $data['expect_education'] = $children['expect_education'];
            // $data['expect_education'] = UsersService::expect_education($children['expect_education']);
        }
        return $this->successReturn($data,'成功',self::errcode_ok);
    }

    /**
     * @Notes:提交表单数据
     * @Interface addUserInfo
     * @author: zy
     * @Time: 2021/11/22
     */
    public function addUserInfo(){
        $uid = $this->uid;
        $params = input("post.", '', 'htmlspecialchars_decode');
        $lockInfo = lock('addUserInfo_'.$uid);
        if($lockInfo == false){
            return $this->errorReturn(self::errcode_fail,'操作过于频繁,请稍后重试!');
        }
        unset($params['session3rd']);//去除不要的信息,存进数据库
        unset($params['debug_uid']);
        foreach ($params as $key => $value) {
            if(empty($value) && ($key != 'house_info' || $key != 'cart_info' || $key != 'expect_house_info')){
                return $this->errorReturn(self::errcode_fail,$key.'参数不能为空');
            }
        }
        if($params['house'] == 1){ //已购房
            if(empty($params['house_info'])){
                return $this->errorReturn(self::errcode_fail,'房子情况必填');
            }
        }
        if($params['cart'] == 1){ //已购车
            // echo '11';
            if(empty($params['cart_info'])){
                return $this->errorReturn(self::errcode_fail,'车子情况必填');
            }
        }
        if($params['expect_house'] == 1){ //已购车
            // echo '2';
            if(empty($params['expect_house_info'])){
                return $this->errorReturn(self::errcode_fail,'择偶标准房子情况必填');
            }
        }
        $info = DB::name("children_form")->where(['uid'=>$uid])->find();
        if(!empty($info)){
            $params['uid'] = $uid;
            $params['update_time'] = date('Y-m-d H:i:s');
            $res = DB::name("children_form")->where(['uid'=>$uid])->update($params);
        }else{
            $params['uid'] = $uid;
            $params['create_time'] = date('Y-m-d H:i:s');
            $res = DB::name("children_form")->insertGetId($params);
        }
        if(!$res){
            return $this->errorReturn(self::errcode_fail,'失败');
        }
        $data['title'] = '添加红娘老师微信号';
        $data['content'] = '红娘老师一对一婚恋咨询,情感导师帮分析,约会相亲全程恋爱指导红娘帮推进';
        $data['img_url'] = 'https://pics.njweiyi6.com//d1f19a87d7a3dfa0/4a8b89aad4a62f7a.jpg';
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
}
