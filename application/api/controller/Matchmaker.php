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

namespace app\api\controller;
use app\api\model\Children as ChildrenModel;
use app\api\service\InterfaceService;
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
     * @Notes:页数数据
     * @Interface getNum
     * @return string
     * @author: zy
     * @Time: 2021/09/07
     */
    public function getNum()
    {
        $list = [
            'activity_price'=>'3999',
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
        $data['img_url'] = 'https://pics.njzec.com/wxewm.png';
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
        if(empty($ask_age)){
            return $this->errorReturn(self::errcode_fail,'ask_age参数不能为空');
        }
        if(empty($ask_height)){
            return $this->errorReturn(self::errcode_fail,'ask_height参数不能为空');
        }
        if(empty($education)){ //1专科以上 2本科以上 3研究生以上 4博士
            return $this->errorReturn(self::errcode_fail,'education参数不能为空');
        }
        if(empty($salary)){ //1 10万以下 2 10万-20万 3 20-30万  4 30-50万 5 100以上
            return $this->errorReturn(self::errcode_fail,'salary参数不能为空');
        }
        $sex = ChildrenModel::getchildrenField(['uid'=>$uid],'sex');
        $age = explode('到',$ask_age);
        $height = explode('到',$ask_height);
        $service = InterfaceService::instance();
        $service->setAuth($this->appid,$this->appkey); // 设置接口认证账号
        $json = [
            'sex'=>$sex,
            'minage'=>$age[0],
            'maxage'=>$age[1],
            'minheight'=>$height[0],
            'maxheight'=>$height[1],
            'education'=>$education,
            'salary'=>$salary
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
        $list = [
            'is_vip_qx'=>1,//牵线是否是会员  0否  1是
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
        $qxInfo = DB::name('qx_apply_user')->where(['uid'=>$uid,'bj_uid'=>$bj_uid])->find();
        $queryData['apply_status'] = '';
        if($qxInfo){
            $queryData['apply_status'] = $qxInfo['apply_status'];
        }
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
        $list = DB::name('qx_apply_user')
            ->where(['uid'=>$uid])
            ->order('create_time desc')
            ->page($page,$pageSize)
            ->select();
        if(empty($list)){
            return $this->successReturn([],'暂无数据',self::errcode_ok);
        }
        $totalCount = DB::name('qx_apply_user')->where(['uid'=>$uid])->count();
        $totalPage = ceil($totalCount/$pageSize);
        $newData = [];
        foreach($list as $k=>$v){
            $newData[$k]['uid'] = $v['bj_uid'];
            $newData[$k]['create_time'] = date('Y年m月d日',strtotime($v['create_time']));
            $newData[$k]['cover'] = $v['cover'];
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
}
