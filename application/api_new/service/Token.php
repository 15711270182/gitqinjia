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

namespace app\api_new\service;

use app\api_new\model\Children as ChildrenModel;
use think\Db;
use think\Request;

/**
 * Class Token
 * @package app\store\controller\api_new
 */
class Token
{
    /**
     * 获取小程序配置
     * @return array
     */
    private function config()
    {
        return config('wechat.miniapp');
    }

    /**
     * Code信息换取
     */
    public function session()
    {
        $code = input('code');
        $result = \We::WeMiniCrypt($this->config())->session($code);
        custom_log('login_data', '微信登录返回' . print_r($result, true));
        custom_log('login_data', '微信登录返回code : ' . $code);
        return $result;
    }

    /**
     * 小程序数据解密
     */
    public function decode($session)
    {
        $iv = input('iv');
        $content = input('encryptedData');
        $result = \We::WeMiniCrypt($this->config())->decode($iv, $session, $content);
//        custom_log('wxLoginError',print_r($result,true));
//        self::set($result,config('wechat.miniapp.appid'));
        $member = self::get($result['openId'],config('wechat.miniapp.appid'));
        $result['member'] = $member;
        return $result;
    }
    /**
     * 获取用户信息
     * @param string $openid
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function get($openid, $appid = 'wx19ac3436ccf898ee')
    {
        $map = ['openid' => $openid];

        $user = Db::name('userinfo')->field('id as uid,openid,unionid')->where($map)->find();
        if (empty($user)) {
            return false;
        }
        $map = [];
        $map['is_del'] = 1;
        $map['uid'] = $user['uid'];
        $is_reg = ChildrenModel::childrenFind($map);
        if (!$is_reg) {
            return false;
        }
        return $user;
    }

}
