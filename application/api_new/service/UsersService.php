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

namespace app\api_new\service;

use service\DataService;
use service\ToolsService;
use app\api_new\model\User as UserModel;
use think\Db;

class UsersService
{    
   /**
     * 认证有效时间
     * @var integer
     */
    private $expire = 604800;
    private $prefix = '';

    public function initialize()
    {
        $this->prefix = config('wechat.miniapp.appid').'_';
    }

   
    /**
     * 增加或更新粉丝信息
     * @param array $user
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function set(array $user, $table,$appid)
    {
        foreach (['country', 'province', 'city', 'nickname', 'remark'] as $field) {
            isset($user[$field]) && $user[$field] = ToolsService::emojiEncode($user[$field]);
        }
        if(array_key_exists('openId', $user)){
            $openid = $user['openId'];
            unset($user['openId']);
            $user['openid'] =$openid;
        }
        if(array_key_exists('avatarUrl', $user)){
            $avatarUrl = $user['avatarUrl'];
            unset($user['avatarUrl']);
            $user['headimgurl'] = $avatarUrl;
        }
        if(array_key_exists('nickName', $user)){
            $nickname = $user['nickName'];
            unset($user['nickName']);
            $user['nickname'] = $nickname;
        }
        if(array_key_exists('gender', $user)){
            $sex = $user['gender'];
            unset($user['gender']);
            $user['sex'] = $sex;
        }
        $user['add_time'] = time();
        unset($user['privilege'], $user['groupid']);
        unset($user['watermark']);
        unset($user['session3rd']);

        $map = array();
        $map['unionid'] = $user['unionid'];
        $res = db('userinfo')->where($map)->find();
        if ($res) 
        {
            //每次重新授权更新头像昵称
            $map = array();
            $map['id'] = $res['id'];
            $data = array();
            $data['nickname'] = $user['nickname'];
            $data['headimgurl'] = $user['headimgurl'];
            db::table('userinfo')->where($map)->update($data);

            //判断是否是当前appid
            if ($res['appid'] != $user['appid']) 
            {
                //不是当前appid则在u2o里面加一条记录
                $map = array();
                $map['appid'] = $user['appid'];
                $map['unionid'] = $user['unionid'];
                $is_have = db('unionid2openid')->where($map)->find();
                if (!$is_have) 
                {
                    $data = array();
                    $data['userid'] = $res['id'];
                    $data['openid'] = $user['openid'];
                    $data['appid'] = $user['appid'];
                    $data['unionid'] = $user['unionid'];
                    $data['addtime'] = time();
                    $u2o  = db('unionid2openid')->insert($data);
                }
            }
            
        }else
        {
            $res = db('userinfo')->insertGetID($user);
        }
        return true;
    }

    /**
     * 获取用户信息
     * @param string $openid
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function get($openid,$table,$appid)
    {
        $map = ['openid' => $openid,'appid' => $appid];
        $user = Db::name($table)->where($map)->find();
        if(empty($user)){
            $map = array();
            $map['openid'] = $openid;
            $map['appid'] = $appid;
            $is_have = db('unionid2openid')->where($map)->find();
            // custom_log('uuuu',json_encode($is_have));
            if (empty($is_have)) 
            {
                return false;
            }
            $map = array();
            $map['id'] = $is_have['userid'];
            $map['unionid'] = $is_have['unionid'];
            $user = db('userinfo')->where($map)->find();
            
        }
        foreach (['country', 'province', 'city', 'nickname', 'remark'] as $k) {
            isset($user[$k]) && $user[$k] = ToolsService::emojiDecode($user[$k]);
        }
        // custom_log('uuuu',json_encode($user));
        $user['openid'] = $openid;
        return $user;
    }
    /**
     * Notes:检测session
     * User:
     * Date: 2021/6/28 16:51
     */
     public static function check($session3rd,$debug_uid,$field=''){
         if($debug_uid){
             $data =  Db::table('userinfo')->field('unionid,openid as xcx_openid,id as uid')->where(['id'=>$debug_uid])->find();
         }else {
             $data = cache(config('wechat.miniapp.appid') . '_SESSION__'. $session3rd);
         }
          return isset($data[$field]) ? $data[$field] : $data;
    }

    public static function education($value){
        $education = '';
        $educationArr = ['0' => '', '1' => '中专及以下', '2' => '高中', '3' => '大专', '4' => '本科','5'=>'研究生','6'=>'博士'];
        if($value){
            $education = $educationArr[$value];
        }
        return $education;
    }
    public static function income($value){
        $income = '';
        $incomeArr = ['0' => '暂未填写', '1' => '5000以下', '2' => '5000-8000', '3' => '8000-12000', '4' => '12000-20000','5'=>'20000-30000','6'=>'30000以上'];
        if($value){
            $income = $incomeArr[$value];
        }
        return $income;
    }
    public static function income_new($value){
        $income = '';
        $incomeArr = ['0' => '暂未填写', '1' => '5千以下', '2' => '5千-8千', '3' => '8千-1万2', '4' => '1万2-2万','5'=>'2万-3万','6'=>'3万以上'];
        if($value){
            $income = $incomeArr[$value];
        }
        return $income;
    }
    public static function house($value){
        $house = '';
        $houseArr = ['0' => '暂未填写', '1' => '已购房', '2' => '父母同住', '3' => '租房'];
        if($value){
            $house = $houseArr[$value];
        }
        return $house;
    }
    public static function cart($value){
        $cart = '';
        $cartArr = ['0' => '暂未填写', '1' => '已购车', '2' => '近期购车', '3' => '无车'];
        if($value){
            $cart = $cartArr[$value];
        }
        return $cart;
    }
    public static function expect_education($value){
        $expect_education = '';
        $expect_educationArr = ['0' => '暂未填写', '1' => '不限学历', '2' => '中专以上', '3' => '高中以上','4'=>'大专以上','5'=>'本科以上','6'=>'研究生以上'];
        if($value){
            $expect_education = $expect_educationArr[$value];
        }
        return $expect_education;
    }
    public static function parents($value){
        $parents = '';
        $parentsArr = ['0' => '暂未填写', '1' => '父母健在', '2' => '单亲家庭', '3' => '父亲健在','4'=>'母亲健在'];
        if($value){
            $parents = $parentsArr[$value];
        }
        return $parents;
    }
    public static function bro($value){
        $bro = '';
        $broArr = ['0' => '暂未填写', '1' => '独生子女', '2' => '老大', '3' => '老二','4'=>'老三'];
        if($value){
            $bro = $broArr[$value];
        }
        return $bro;
    }

    /**
     * @Notes:判断用户是否是vip
     * @Interface isVip
     * @param $value
     * @author: zy
     * @Time: 2021/07/26 10:23
     */
    public static function isVip($userinfo){
        $is_vip = 0;//是否是会员
        if($userinfo['is_vip']== 1 && $userinfo['endtime']>= time()){ //判断用户是否是会员
            $is_vip = 1;  //会员每天推荐15个
        }
        return $is_vip;
    }

}