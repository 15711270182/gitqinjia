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
use app\api\model\Relation;
use app\api\model\TelCollection;
use app\api\service\UsersService;
use app\api\service\ScoreService;
use app\api\model\User as UserModel;
use app\api\model\Children as ChildrenModel;
use app\api\model\Collection as CollectionModel;
use app\api\model\Relation as RelationModel;
use app\api\model\Order as OrderModel;
use app\api\model\Team as TeamModel;
use think\Db;
use think\Queue;

use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class User extends Base
{

    /**
     * @Notes:用户添加子女资料
     * @Interface addChildren
     * @return string
     * @throws \think\Exception
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function addChildren()
    {
        $uid = $this->uid;
        $lockInfo = lock('add_'.$uid);
        if($lockInfo == false){
            return $this->errorReturn(self::errcode_fail,'正在提交中,请勿频繁操作');
        }
        $source = input("source", '', 'htmlspecialchars_decode');//来源 推荐人id
        $params = input("post.", '', 'htmlspecialchars_decode');
        unset($params['session3rd']);//去除不要的信息,存进数据库
        unset($params['source']);
        $is_have = ChildrenModel::childrenFind(['uid'=>$uid]);
        if($is_have){
            return $this->errorReturn(self::errcode_fail,'孩子资料已完善');
        }
        //添加审核员id team_id
        $count = TeamModel::teamCount('');
        $params['team_id'] = rand(1,$count);
        $params['uid'] = $uid;
        $params['create_at'] = time();
        ChildrenModel::childrenAdd($params);
        //如果来源不为空且没有被其他人推荐过 给推荐者增加次数 并添加来源relation 添加 查看手机号次数流水记录 tel_count
        $count = RelationModel::relationFind(['uid'=>$uid]);
        if(!empty($source)){
            $userinfo = UserModel::userFind(['id'=>$uid]);
            $telcount = [
                'uid' => $source,
                'type' => 1,
                'count' => 1,
                'remarks' => '推荐'.$userinfo['nickname'].'注册增加一次次数',
                'create_at' => time()
            ];
            if($count == 0){ //没被推荐过
                UserModel::getuserInt(['id'=>$source],'count');
                //添加来源 关系表 relation
                $relation = [
                    'uid' => $uid,
                    'bid' => $source,
                    'create_at' => time()
                ];
                RelationModel::relationAdd($relation);
                TelCollection::tcountAdd($telcount);
                ScoreService::instance()->weightScoreInc($source,21,$uid);//邀请者增加权重分
            }else{
                if($count['type'] == 1){ //静默未填写资料的状态
                    RelationModel::relationEdit(['uid'=>$uid,'bid'=>$source],['type'=>0,'update_time'=>date('Y-m-d H:i:s')]);
                    UserModel::getuserInt(['id'=>$source],'count');
                    TelCollection::tcountAdd($telcount);
                    ScoreService::instance()->weightScoreInc($source,21,$uid);//邀请者增加权重分
                }

            }
        }
        return $this->successReturn($params,'成功',self::errcode_ok);
    }
    /**
     * @Notes:我的页面用户信息
     * @Interface meInformation
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function meInformation()
    {
        $uid = $this->uid;
        $field = 'uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro,balance';
        $children = ChildrenModel::childrenFind(['uid'=>$uid],$field);
        if(empty($children)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        //数据转化
        $data = $this->userchange($children);
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $data['is_vip'] = 0;
        $data['endtime'] = '';
        if($userinfo['is_vip'] == 1 && $userinfo['endtime'] >= time()){
            $data['is_vip'] = 1;
            $data['endtime'] = date('Y-m-d',$userinfo['endtime']);
        }
        $last_num = $userinfo['count']?$userinfo['count']:0;
        $map['uid'] = $uid;
        $map['status'] = 1;
        $money = OrderModel::getorderSum($map,'payment');
        $data['pay_money'] = $money/100;

        $wechat_url = 'http://mp.weixin.qq.com/s?__biz=Mzg3ODYzMjk5OA==&mid=100000006&idx=1&sn=085429b461d09aa0f663db416f363230&chksm=4f11884f786601595841cbdde10c0f56a0aaf384f08c8db9e258cba3e21bbe9af176faa7fe97#rd';

        $paytype = cache('paytypeuid-'.$uid);
        if(!$paytype){
            $paytype = $userinfo['paytype'];
            cache('paytypeuid-'.$userinfo['paytype'],$paytype,3*24*3600);
        }
        $list = [
            'operate_uid'=>$uid, //新增 操作者uid
            'paytype'=>$paytype, //用户支付类型 1月卡 2次卡
            'balance'=>!empty($children['balance'])?$children['balance']/100:'',
            'last_num'=>$last_num,
            'wechat_url'=>$wechat_url,
            'data'=>$data
        ];
        return $this->successReturn($list,'成功',self::errcode_ok);
    }
    /**
     * @Notes:我的页面 - 编辑资料页面
     * @Interface childrenInfo
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function childrenInfo()
    {
        $uid = $this->uid;
        $children = ChildrenModel::childrenFind(['uid'=>$uid]);
        if(empty($children)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        $userinfo = UserModel::userFind(['id'=>$uid]);
        $children['realname'] = $userinfo['realname'];
        $children['headimgurl'] = $userinfo['headimgurl'];
        $children['sex_test'] = $children['sex']==1?'男':'女';
        $children['phone'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);
        $children['education_test'] = UsersService::education($children['education']);
        $children['income_test'] = UsersService::income($children['income']);
        $children['house_test'] = UsersService::house($children['house']);
        $children['house_test'] = UsersService::house($children['house']);
        $children['cart_test'] = UsersService::cart($children['cart']);
        $children['expect_education_test'] = UsersService::expect_education($children['expect_education']);
        $children['expect_education_test'] = UsersService::expect_education($children['expect_education']);
        $children['parents_test'] = UsersService::parents($children['parents']);
        $children['bro_test'] = UsersService::bro($children['bro']);
        $children['min_age_test'] = $children['min_age']==999?'不限':$children['min_age'];
        $children['max_age_test'] = $children['max_age']==999?'不限':$children['max_age'];
        $children['min_height_test'] = $children['min_height']==999?'不限':$children['min_height'];
        $children['max_height_test'] = $children['max_height']==999?'不限':$children['max_height'];
        $children['is_wechat'] = 0;
        $is_wechat = UserModel::wxFind(['unionid'=>$userinfo['unionid']]);
        if($is_wechat){
            $children['is_wechat'] = 1;
        }
        return $this->successReturn($children,'成功',self::errcode_ok);
    }
    /**
     * @Notes:我的页面-修改用户资料
     * @Interface childrenEdit
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function childrenEdit()
    {
        $uid = $this->uid;
        $field = input("field", '', 'htmlspecialchars_decode');//要修改的字段 field
        $value = input("values", '', 'htmlspecialchars_decode');//对应的值
        if(empty($field)){
            return $this->errorReturn(self::errcode_fail,'field参数不能为空');
        }
        if(empty($value)){
            return $this->errorReturn(self::errcode_fail,'values参数不能为空');
        }
        //0过不了 empty 的判断
        if($value == '/'){
            $value = 0;
        }
        $cInfo = ChildrenModel::childrenFind(['uid'=>$uid]);
        //第一次完善加分  1 school 学校 2 hometown 家乡 3 native_place 户籍 5 work 职业 6 house 房子 7 cart 车子
//        switch($field){
//            case 'school':if(empty($cInfo[$field])){$type = 1;} break;
//            case 'hometown':if(empty($cInfo[$field])){$type = 2;} break;
//            case 'native_place':if(empty($cInfo[$field])){$type = 3;} break;
//            case 'work':if(empty($cInfo[$field])){$type = 5;} break;
//            case 'house':if($cInfo[$field] == 0){$type = 6;} break;
//            case 'cart':if($cInfo[$field] == 0){$type = 7;} break;
//        }
//        if (!empty($type)) {
//            userscore($uid,$type);
//        }
        //修改的数据
        $update['update_time'] = date('Y-m-d H:i:s');
        if($field == 'ask_age' || $field == 'ask_height'){
             if($field == 'ask_age'){
                $age = explode('到',$value);
                $update['min_age'] = $age[0];
                $update['max_age'] = $age[1];
            }
            if($field == 'ask_height'){
                $height = explode('到',$value);
                $update['min_height'] = $height[0];
                $update['max_height'] = $height[1];
            }
        }else{
            $update[$field] = $value;
        }
        if($field == 'realname'){
            //修改用户真实姓名
            $userinfo = UserModel::userFind(['id'=>$uid]);
            if (empty($userinfo)){
                return $this->errorReturn(self::errcode_fail,'暂无数据');
            }
            $res = UserModel::userEdit(['id'=>$uid],$update);
            if($res){
                return $this->successReturn('','修改成功',self::errcode_ok);
            }else{
                return $this->errorReturn(self::errcode_fail,'请勿重复修改');
            }
        }
        //修改子女资料表
        if (empty($cInfo)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        ScoreService::instance()->editScoreInc($uid,$field,$value);
        $res = ChildrenModel::childrenEdit(['uid'=>$uid],$update);
        if($res){
            return $this->successReturn('','修改成功',self::errcode_ok);
        }
        return $this->errorReturn(self::errcode_fail,'请勿重复修改');
    }
    /**
     * @Notes:编辑资料-填写其他信息
     * @Interface editRemarks
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function editRemarks()
    {
        $uid = $this->uid;
        $remark = input("remarks", '', 'htmlspecialchars_decode');
        if(!$remark){
            return $this->errorReturn(self::errcode_fail,'数据不能为空');
        }
        $data['remarks'] = $remark;
        $data['update_time'] = date('Y-m-d H:i:s');
        $res = ChildrenModel::childrenEdit(['uid'=>$uid],$data);
        if(!$res){
            return $this->errorReturn(self::errcode_fail,'更新失败');
        }
        ScoreService::instance()->weightScoreInc($uid, 1);//相亲说明增加权重分
        return $this->successReturn('','更新成功',self::errcode_ok);
    }
    /**
     * @Notes:type 消息  1 我收藏的  2 收藏我的 3联系人
     * @Interface msgList
     * @author: zy
     * @Time: 2021/07/20
     */
    public function msgList(){
        $uid = $this->uid;
        $type = input('type') ? : 1;
        $where = "is_del = 1 and is_show = 0";
        if($type == 2){
            $where .= " and bid = '{$uid}'";
        }else{
            $where .= " and uid = '{$uid}'";
        }
        $table = 'Collection';
        if($type == 3){
           $table = 'TelCollection';
        }
        $list = Db::name($table)->where($where)->order('create_at desc')->select();
        if(empty($list)){
            return $this->errorReturn(self::errcode_fail);
        }
        $field = 'id,uid,sex,year,height,residence,native_place,hometown,education,work,income,remarks,house,cart,school,parents,bro';
        foreach ($list as $key => $value) {
            //被收藏用户子女资料
            $where_c['uid'] = $value['bid'];
            $where_s['is_del'] = 1;
            $where_s['uid'] = $uid;
            $where_s['bid'] = $value['bid'];
            if($type == 2){ //收藏我的
                $where_c['uid'] = $value['uid'];
                $where_s['bid'] = $value['uid'];
            }
            $ChildInfo = ChildrenModel::childrenFind($where_c,$field);
            $list[$key] = $this->userchange($ChildInfo);
            $list[$key]['create_time'] = date("m月d日",$value['create_at']);
            if(time() - $value['create_at'] < 172800){
                $todaystart = strtotime(date('Y-m-d'.'00:00:00',time()));
                if($value['create_at'] < $todaystart){
                    $list[$key]['create_time'] = '昨天';
                }else{
                    $list[$key]['create_time'] = '今天';
                }
            }
            $is_collection = CollectionModel::collectionCount($where_s);//我收藏的 /收藏我的
            if(empty($is_collection)){
                $list[$key]['is_collection'] = 1;
            }else{
                $list[$key]['is_collection'] = 2;
            }
            $list[$key]['id'] = $value['id'];
        }
        return $this->successReturn($list,'成功',self::errcode_ok);
    }

    /**
     * @Notes:id 要删除的id
     * @Interface msgDel
     * @author: zy
     * @Time: 2021/07/20
     */
    public function msgDel(){
        $uid = $this->uid;
        $id = input('id') ? : 0;
        $type = input('type') ? : 0;
        if(empty($id)){
            return $this->errorReturn(self::errcode_fail, 'id不能为空');
        }
        if(empty($type)){
            return $this->errorReturn(self::errcode_fail, 'type不能为空');
        }
        $table = 'Collection';
        if($type == 3){
           $table = 'TelCollection';
        }
        $find = Db::name($table)->where(['id'=>$id])->find();
        if(empty($find)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        if($find['is_show'] == 1){
            return $this->errorReturn(self::errcode_fail,'该数据已经被删除');
        }
        $res = Db::name($table)->where(['id'=>$id])->update(['is_show'=>1]);
        if(!$res){
            return $this->errorReturn(self::errcode_fail,'删除失败');
        }
        return $this->successReturn('','删除成功',self::errcode_ok);

    }

    /**
     * @Notes:收藏与取消收藏
     * @Interface collection
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function collection()
    {
        $uid = $this->uid;
        $bid = input('bid') ? : 0;//要收藏或取消收藏id的 用户id
        $type = input('type') ? : 0;//1收藏 2取消收藏
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail, 'bid参数不能为空');
        }
        if(empty($type)){
            return $this->errorReturn(self::errcode_fail, 'type参数不能为空');
        }
        $b_user_info = UserModel::userFind(['id'=>$bid]);
        if ($b_user_info['status'] == 0) {
            return $this->errorReturn(self::errcode_fail, '该用户已经注销!');
        }
        $where['uid'] = $uid;
        $where['bid'] = $bid;
        $collection = CollectionModel::collectionFind($where);
        if(empty($collection)){
            if($type == 1){
                //收藏成功发送模板消息
                $touser = UserModel::userFind(['id'=>$bid]);
                $map['unionid'] = $touser['unionid'];
                $map['subscribe'] = 1;
                $mini_user = UserModel::wxFind($map);
                if($mini_user && $mini_user['status'] == 1){ //用户关注 非注销 发送模板消息
                    $senduser = UserModel::userFind(['id'=>$uid]);
                    $children = ChildrenModel::childrenFind(['uid'=>$uid]);
                    $openid = $mini_user['openid'];
                    $time = date('Y-m-d H:i');
                    $tip = '有位家长收藏了您孩子的相亲卡，试试联系ta吧';
                    $name =  $senduser['realname'].'家长';
                    $phone = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $children['phone']);
                    $remark = '帮孩子找对象，首选完美亲家';
                    $temp_id = 'aGiyIGwKmygDgnNWl9XGyIFNjSAOvau8Tr5RNjLlkkM';
                    $data = array();
                    $data['first'] = array('value'=>$tip,'color'=>'#FF0000');
                    $data['keyword1'] = array('value'=>$name,'color'=>'#0000ff');
                    $data['keyword2'] = array('value'=>$phone,'color'=>'#0000ff');
                    $data['keyword3'] = array('value'=>$time,'color'=>'#0000ff');
                    $data['remark'] = array('value'=>$remark,'color'=>'#0000ff');
                    $param = [
                        'touser'=>$openid,
                        'template_id'=>$temp_id,
                        'page'=>'pages/message/message',
                        'data'=>$data,
                        'miniprogram' => [
                            'pagepath'=>'pages/message/message',
                            'appid'=>'wx70d65d2170dbacd7',
                        ],
                    ];
                    $this->shiwuSendMsg($param);
                }
                $params = [
                    'uid' => $uid,
                    'bid' => $bid,
                    'create_at' => time()
                ];
                CollectionModel::collectionAdd($params);
                ScoreService::instance()->weightScoreInc($uid,30,$bid);
            }
            return $this->successReturn('','成功',self::errcode_ok);
        }
        $update['is_del'] = 2; //取消收藏
        if($type == 1){ //收藏
            $update['is_del'] = 1;
        }
        $res = CollectionModel::collectionEdit(['id'=>$collection['id']],$update);
        if(!$res){
            return $this->errorReturn(self::errcode_fail,'操作失败');
        }
        if($type == 1){ //收藏
            ScoreService::instance()->weightScoreInc($uid,30,$bid);
        }else{
            ScoreService::instance()->weightScoreInc($uid,31,$bid);
        }
        return $this->successReturn('','成功',self::errcode_ok);
    }
    /**
     * @Notes:用户撤销注销
     * @Interface cancellation
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function cancellation()
    {
        $uid = $this->uid;
        $user_info = UserModel::userFind(['id'=>$uid]);
        $up_date['status'] = 1;
        Db::startTrans();
        try{
            UserModel::userEdit(['id'=>$user_info['id']],$up_date);
            ChildrenModel::childrenEdit(['uid'=>$user_info['id']],$up_date);
            UserModel::wxEdit(['unionid'=>$user_info['unionid']],$up_date);
            Db::commit();
            return $this->successReturn('','撤销注销成功',self::errcode_ok);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->errorReturn(self::errcode_fail,'撤销注销失败');
        }
    }
    /**
     * @Notes:用户举报
     * @Interface report
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function report()
    {
        $uid = $this->uid;
        $type = input("type", '', 'htmlspecialchars_decode'); //举报类型
        if(empty($type)){
            return $this->errorReturn(self::errcode_fail,'type参数不能为空');
        }
        $bid = input("bid", '', 'htmlspecialchars_decode'); // 被举报的用户id
        if(empty($bid)){
            return $this->errorReturn(self::errcode_fail,'bid参数不能为空');
        }
        $params = [
            'uid' => $uid,
            'type' =>$type,
            'bid' =>$bid,
            'create_at' =>time()
        ];
        $res = Db::name('Report')->insert($params);
        if(!$res){
            return $this->errorReturn(self::errcode_fail,'失败');
        }
        return $this->successReturn('','成功',self::errcode_ok);
    }
    /**
     * @Notes:我的邀请列表
     * @Interface shareList
     * @return string
     * @author: zy
     * @Time: 2021/07/22
     */
    public function shareList()
    {
        $uid = $this->uid;
        $where_c['r.bid'] = $uid;
        $list = Db::table('children')->alias('c')
            ->where($where_c)
            ->leftjoin('relation r','c.uid=r.uid')
            ->field('r.uid,r.create_at')
            ->order('r.create_at desc')
            ->select();
        if(empty($list)){
            return $this->errorReturn(self::errcode_fail,'暂无分享');
        }
        foreach ($list as $key => $value) {
            $pare = UserModel::userFind(['id'=>$value['uid']]);
            $list[$key]['nickname'] = $pare['nickname'];
            $list[$key]['headimgurl'] = $pare['headimgurl'];
            $list[$key]['sex'] = $pare['sex'];
            $list[$key]['create_at'] = date('Y-m-d H:i',$list[$key]['create_at']);
        }
        return $this->successReturn($list,'成功',self::errcode_ok);
    }
    
    /**
     * @Notes:(邀请人)代理列表明细
     * @Interface shareDetails
     * @author: zy
     * @Time: 2021/07/28
     */
    public function shareDetails(){
        $type = input('type') ? : 1; //1 资金明细  2填写资料  3邀请用户
        $uid = $this->uid;
        $map['bid'] = $uid;
        $map['is_del'] = 1;
        $list = Relation::relationSelect($map,'uid,create_at','create_at desc');
        if(empty($list)){
            return $this->errorReturn(self::errcode_fail,'暂无数据');
        }
        if($type == 1){ //资金明细
            $field = 'uid,pay_time,total_money,awards_money,order_type,status';
            $list = DB::name("invite_awards")->where(['bid'=>$uid])->field($field)->order('id desc')->select();
            if(empty($list)){
                return $this->errorReturn(self::errcode_fail,'暂无数据');
            }
            $newData = [];
            $uid = array_column($list,'uid');
            $where_u['id'] =$uid;
            $info = UserModel::getuserColumn($where_u,'id,headimgurl,nickname,realname');
            foreach($list as $k=>$v){
                
                if(isset($info[$v['uid']]['realname'])){
                    $realname = $info[$v['uid']]['realname'];
                    if(empty($realname)){
                        $realname = '匿名'; 
                        if(isset($info[$v['uid']]['nickname'])){
                            $realname = $info[$v['uid']]['nickname'];
                        }
                    }else{
                        $realname = $realname.'家长';
                    }
                }else{
                    $realname = '匿名'; 
                    if(isset($info[$v['uid']]['nickname'])){
                        $realname = $info[$v['uid']]['nickname'];
                    }
                }
                $type = $v['order_type'] == 1?'月卡':'次卡';
                $newData[$k]['uid'] = $v['uid'];
                $newData[$k]['realname'] = $realname;
                $newData[$k]['headimgurl'] = isset($info[$v['uid']]['headimgurl'])?$info[$v['uid']]['headimgurl']:'https://pics.njzec.com/default.png';
                $newData[$k]['awards_money'] = $v['awards_money']/100;
                $newData[$k]['status'] = $v['status'];
                if($v['status'] == 2){
                    $newData[$k]['remark'] = '用户提现';
                }else{
                    $newData[$k]['remark'] = '支付'.$type.($v['total_money']/100).'元';
                }
                $newData[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['pay_time']));

            }
            return $this->successReturn(['count'=>count($newData),'list'=>$newData],'成功',self::errcode_ok);
        }
        $bid = array_column($list,'uid');
        $where_u['id'] = $bid;
        $field = 'id as uid,realname,headimgurl,nickname,add_time';
        $list = UserModel::userSelect($where_u,$field,'add_time desc');
        if($type == 2){ //填写资料
            $where_c['c.uid'] = $bid;
            $list = Db::table('userinfo')
                ->alias('a')
                ->where($where_c)
                ->leftjoin('children c','a.id=c.uid')
                ->field('a.realname,a.headimgurl,a.nickname,c.create_at as add_time ,c.uid')
                ->order('c.create_at desc')
                ->select();
        }
        foreach ($list as $key => $value) {
            if(!empty($value['realname'])){
                $realname = $value['realname'].'家长';
            }else{
                $realname = '匿名';
                if(!empty($value['nickname'])){
                    $realname = $value['nickname'];
                }
            }
            $list[$key]['realname'] = $realname;
            $list[$key]['headimgurl'] = !empty($value['headimgurl'])?$value['headimgurl']:'https://pics.njzec.com/default.png';
            $list[$key]['awards_money'] = '';
            $list[$key]['status'] = '';
            $list[$key]['remark'] = '';
            $list[$key]['add_time'] = date('Y-m-d H:i',$list[$key]['add_time']);
        }
        $count = count($list);
        return $this->successReturn(['count'=>$count,'list'=>$list],'成功',self::errcode_ok);
    }
    /**
     * 用户数据转化成前端需要的样式
     * @author zy
    */
    private function userchange($value)
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
        //查询用户父母的名称
        $pare = UserModel::userFind(['id'=>$value['uid']]);
        $user['realname'] = $pare['realname']?$pare['realname'].'家长':'家长';
        $user['headimgurl'] = $pare['headimgurl'];
        $user['user_sex'] = $pare['sex'];
        $user['user_status'] = $pare['status'];
        $user['sex'] = $value['sex'];

        return $user;
    }

    public function pushSubUser(){
//        //订阅号模板内容
//        $dy_openid   = 'of8n75YM-J-IL08PQt-wVIdNSnO0';
//        $dy_data['thing1'] = array('value' => "我们为您推荐了12位相亲对象");
//        $dy_data['thing2'] = array('value' => "点击小程序进行查看");
//        $dy_temp_id = "h7hV5I03Ve_flhZm9n7lH4TWzqZvjDsIxkqV5MpE6gM";
//        $param = [
//            'touser'=>$dy_openid,
//            'template_id'=>$dy_temp_id,
//            'page'=>'pages/home/home',
//            'data'=>$dy_data
//        ];
//        $res = $this->shiwuSendMsg($param,2);
//        var_dump($res);die;
        $list = UserModel::userSelectPage(['is_subscribe'=>1],'id,openid');
        if(empty($list)){
            echo '无可推送数据';die;
        }
        $num = 12;
        $dy_count = 0;
        foreach($list as $k=>$v){
            $find = ChildrenModel::childrenFind(['uid'=>$v['id']]);
            if(empty($find)){
                //订阅号模板内容
                $dy_openid   = $v['openid'];
                $dy_data['thing1'] = array('value' => "我们为您推荐了12位相亲对象");
                $dy_data['thing2'] = array('value' => "点击小程序进行查看");
                $dy_temp_id = "h7hV5I03Ve_flhZm9n7lH4TWzqZvjDsIxkqV5MpE6gM";
                $param = [
                    'touser'=>$dy_openid,
                    'template_id'=>$dy_temp_id,
                    'page'=>'pages/home/home',
                    'data'=>$dy_data
                ];
                $res = $this->shiwuSendMsg($param,2);
                if($res == true){
                    $add['uid'] = $v['id'];
                    $add['openid'] = $v['openid'];
                    $add['type'] = 1;
                    $add['create_time'] = date('Y-m-d H:i:s');
                    Db::name('send_record')->insertGetId($add);
                    $dy_count++;
                }
            }

        }
        echo $dy_count;die;
    }


    public function test(){
        $field = "uid,height,residence,education,income,bro,parents,native_place,hometown,school,house,cart,remarks,expect_education,max_age,max_height";
        $list =  ChildrenModel::childrenSelect(['status'=>1],$field);
        foreach($list as $k=>$v){
            if($v['height']){
                ScoreService::instance()->weightScoreInc($v['uid'],32);//身高
            }
            if($v['residence']){
                ScoreService::instance()->weightScoreInc($v['uid'],2);//身高
            }
            if($v['education']){
                ScoreService::instance()->weightScoreInc($v['uid'],3);
            }
            if($v['income']){
                ScoreService::instance()->weightScoreInc($v['uid'],4);
            }
            if($v['bro']){
                ScoreService::instance()->weightScoreInc($v['uid'],5);
            }
            if($v['parents']){
                ScoreService::instance()->weightScoreInc($v['uid'],6);
            }
            if($v['native_place']){
                ScoreService::instance()->weightScoreInc($v['uid'],7);
            }
            if($v['hometown']){
                ScoreService::instance()->weightScoreInc($v['uid'],8);
            }
            if($v['school']){
                ScoreService::instance()->weightScoreInc($v['uid'],9);
            }
            if($v['house']){
                if($v['house'] == 1){
                    ScoreService::instance()->weightScoreInc($v['uid'],12);
                }
                if($v['house'] == 2){
                    ScoreService::instance()->weightScoreInc($v['uid'],13);
                }
                if($v['house'] == 3){
                    ScoreService::instance()->weightScoreInc($v['uid'],14);
                }
            }
            if($v['cart']){
                if($v['cart'] == 1){
                    ScoreService::instance()->weightScoreInc($v['uid'],15);
                }
                if($v['cart'] == 2){
                    ScoreService::instance()->weightScoreInc($v['uid'],16);
                }
                if($v['cart'] == 3){
                    ScoreService::instance()->weightScoreInc($v['uid'],17);
                }
            }
            if($v['remarks']){
                ScoreService::instance()->weightScoreInc($v['uid'],1);
            }
            if($v['expect_education']){
                ScoreService::instance()->weightScoreInc($v['uid'],18);
            }
            if($v['max_age']){
                ScoreService::instance()->weightScoreInc($v['uid'],19);
            }
            if($v['max_height']){
                ScoreService::instance()->weightScoreInc($v['uid'],20);
            }

        }
        echo '11';die;
    }

    public function test_bb(){
        //邀请填写资料加分
        $info1 = Db::name('relation')->alias('r')
            ->leftJoin('children p','r.uid= p.uid')
            ->field("r.uid,r.bid")
            ->where(['r.type'=>0])
            ->order('r.create_at desc')
            ->select();
        foreach($info1 as $k=>$v){
            ScoreService::instance()->weightScoreInc($v['bid'],21,$v['uid']);
        }
//        关注公众号
        $info2 = Db::name('userinfo')->alias('r')
            ->leftJoin('wechat_fans p','r.unionid= p.unionid')
            ->field("r.id")
            ->where(['r.status'=>1,'p.subscribe'=>1])
            ->select();
        foreach($info2 as $k=>$v){
            ScoreService::instance()->weightScoreInc($v['id'],28);
        }
//        购买会员
        $info3 = Db::name('order')->alias('o')
            ->join('product p','o.goods_id= p.id')
            ->where(['o.status'=>1])->select();
        foreach($info3 as $k=>$v){
            if($v['type'] == 2){
                 switch($v['num']){
                    case 1:ScoreService::instance()->weightScoreInc($v['uid'],23);//购买1次卡增加权重分
                    break;
                    case 5:ScoreService::instance()->weightScoreInc($v['uid'],24);//购买5次卡增加权重分
                    break;
                    case 10:ScoreService::instance()->weightScoreInc($v['uid'],25);//购买10次卡增加权重分
                    break;
                    default:break;
                 }
            }else{
                ScoreService::instance()->weightScoreInc($v['uid'],22);//购买月卡增加权重分
            }

        }
//        查看手机号
        $info4 = Db::name('tel_collection')->where(['is_del'=>1])->select();
        foreach($info4 as $k=>$v){
            ScoreService::instance()->weightScoreInc($v['bid'],26,$v['uid']);//被查看
            ScoreService::instance()->weightScoreInc($v['uid'],27,$v['bid']);//查看
        }
        //收藏取消收藏
        $info5 = Db::name('collection')->where(['is_show'=>0])->select();
        foreach($info5 as $k=>$v){
            if($v['is_del'] == 1){
                ScoreService::instance()->weightScoreInc($v['uid'],30,$v['bid']);//收藏
            }
            if($v['is_del'] == 2){
                ScoreService::instance()->weightScoreInc($v['uid'],31,$v['bid']);//取消收藏
            }
        }
    }
}
