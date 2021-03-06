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
use app\api\model\TelCollection;
use app\api\model\User as UserModel;
use app\api\model\Children as ChildrenModel;
use app\api\service\UsersService as UsersService;
use think\Db;
use think\Queue;

use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Feedback extends Base
{
    /**
     * @Notes:意见反馈
     * @Interface add
     * @author: zy
     * @Time: 2021/09/23
     */
    public function add()
    {
        $uid = $this->uid;
        $bid = input('bid');  //被反馈人
        $type = input('type'); //1未联系  2已联系
        $content = input('content'); //反馈内容
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        if(empty($type)){
            return $this->errorReturn(self::errcode_fail,'type参数不能为空');
        }
        if(empty($content)){
            return $this->errorReturn(self::errcode_fail,'content参数不能为空');
        }
        $count = Db::name('feedback')->where(['uid'=>$uid])->count();
        if($count >= 2){
            return $this->errorReturn(self::errcode_fail,'反馈次数已耗尽');
        }
        try{
            $feed_data['uid'] = $uid;
            $feed_data['bid'] = $bid;
            $feed_data['type'] = $type;
            $feed_data['content'] = $content;
            Db::name('feedback')->insertGetId($feed_data);
            $tel_count['uid'] = $uid;
            $tel_count['type'] = 1;
            $tel_count['count'] = 1;
            $tel_count['remarks'] = '打电话意见反馈增加一次次数';
            $tel_count['create_at'] = time();
            TelCollection::tcountAdd($tel_count);
            UserModel::getuserInt(['id'=>$uid],'count',1);
            Db::commit();
            return $this->successReturn('','反馈成功',self::errcode_ok);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->errorReturn(self::errcode_fail,'反馈失败');
        }
    }

    /**
     * @Notes:意见反馈次数
     * @Interface count
     * @author: zy
     * @Time: 2021/09/23
     */
    public function count()
    {
        $uid = $this->uid;
        $bid = input('bid');  //被反馈人
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        $userinfo = UserModel::userFind(['id'=>$bid]);
        $data['headimgurl'] = $userinfo['headimgurl'];
        $children = ChildrenModel::childrenFind(['uid'=>$bid],'sex,year,education,residence');
        $sex = '女';
        if($children['sex'] == 1){
            $sex = '男';
        }
        $year = substr($children['year'],-2).'年';
        $shuxiang = getShuXiang($children['year']);
        $education = UsersService::education($children['education']);
        $data['content'] = $sex.'·'.$year.'('.$shuxiang.')'.'·'.$education.'·现居'.$children['residence'];
        //查看时间
        $tel = TelCollection::telFind(['uid'=>$uid,'bid'=>$bid,'type'=>1],'create_at');
        $time = date('Y-m-d:',$tel['create_at']);
        if($time == date('Y-m-d')){
            $data['tel_time'] = date('H:i',$tel['create_at']);
        }else{
            $data['tel_time'] = date('Y-m-d H:i',$tel['create_at']);
        }
        $data['count'] = Db::name('feedback')->where(['uid'=>$uid])->count();
        return $this->successReturn($data,'成功',self::errcode_ok);
    }
}