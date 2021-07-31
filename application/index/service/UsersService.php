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

namespace app\index\service;

use service\DataService;
use service\ToolsService;
use think\Db;

/**
 * 默契考验用户数据同步
 * 小程序用户数据服务
 * Class FansService
 * @package app\wechat
 */
class UsersService
{    
    
   
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
    
    

   

}