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
use app\api_new\model\User as UserModel;
use app\api_new\model\Relation as RelationModel;
use app\api_new\service\Token as TokenService;

use think\Db;
use think\Queue;

use think\facade\Cache;
/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Token extends Base
{
    /**
     * 微信授权
     * @author wzs
     */
    public function wxLogin()
    {
//        $code = input("code", '', 'htmlspecialchars_decode');
        $rawData = input("rawData", '', 'htmlspecialchars_decode');
        $signature = input("signature", '', 'htmlspecialchars_decode');
        $encryptedData = input("encryptedData", '', 'htmlspecialchars_decode');
        $iv = input("iv", '', 'htmlspecialchars_decode');

        $WechatService = new TokenService();
        $result = $WechatService->session();
        $session_key = $result['session_key'];
        $openid = $result['openid'];
        $unionid = isset($result['unionid']) ? $result['unionid'] : '';
        //检测静默授权
        if (empty($rawData) || empty($signature) || empty($encryptedData) || empty($iv)) {
            $result['member']  = TokenService::get($result['openid'],config('wechat.miniapp.appid'));
            if(!$result['member']){
                $userInfo = UserModel::userFind(['openid' => $openid],'id as uid,openid,unionid');
                if(!$userInfo){
                    $data = [];
                    $data['paytype'] = 2; //默认次数
                    $data['openid'] = $openid;
                    $data['unionid'] = $unionid;
                    $data['appid'] = config('wechat.miniapp.appid');
                    $data['add_time'] = time();
                    $resId = UserModel::userAdd($data);
                    $source = input('source');//来源 推荐人id
                    custom_log('静默邀请人id',print_r($source,true));
                    if(!empty($source)){
                        $count = RelationModel::relationFind(['uid'=>$resId]);
                        if($count == 0){
                            //添加来源 关系表 relation
                            $relation = [
                                'uid' => $resId,
                                'bid' => $source,
                                'type'=>1,
                                'create_at' => time()
                            ];
                            RelationModel::relationAdd($relation);
                        }
                    }
                    $userInfo = UserModel::userFind(['id'=>$resId],'id as uid,openid,unionid');
                }
                $c_data = $userInfo;
                $c_data['session_key'] = $session_key;
                $session3rd = randomFromDev(16);
                cache(config('wechat.miniapp.appid') . '_SESSION__' . $session3rd, $c_data);
                return $this->errorReturn(self::errcode_fail, $session3rd);
            }

            $c_data = $result['member'];
            $c_data['session_key'] = $session_key;
            $session3rd = randomFromDev(16);
            cache(config('wechat.miniapp.appid') . '_SESSION__' . $session3rd, $c_data);
            return $this->successReturn($session3rd, '成功', self::errcode_ok);
        }
        $user_data = json_decode($rawData, true);
        //弹框授权
        $data = [];
        $data['nickname'] = emojiEncode($user_data['nickName']);
        $data['headimgurl'] = $user_data['avatarUrl'];
        $data['sex'] = $user_data['gender'];
        $data['country'] = $user_data['country'];
        $data['province'] = $user_data['province'];
        $data['city'] = $user_data['city'];
        $data['unionid'] = $unionid;
        $is_have = UserModel::userFind(['openid' => $openid]);
        if ($is_have) {
            UserModel::userEdit(['id' => $is_have['id']],$data);
            $data = [];
            $data['uid'] = $is_have['id'];
            $data['openid'] = $openid;
            $data['unionid'] = $unionid;
            $data['session_key'] = $session_key;
            $session3rd = randomFromDev(16);
            cache(config('wechat.miniapp.appid') . '_SESSION__' . $session3rd, $data);

            return $this->successReturn($session3rd, '成功', self::errcode_ok);
        }
        $data['paytype'] = 2; //默认次数
        $data['openid'] = $openid;
        $data['appid'] = config('wechat.miniapp.appid');
        $data['add_time'] = time();
        $userid = UserModel::userAdd($data);
        $data = [];
        $data['uid'] = $userid;
        $data['openid'] = $openid;
        $data['unionid'] = $unionid;
        $data['session_key'] = $session_key;
        $session3rd = randomFromDev(16);
        cache(config('wechat.miniapp.appid') . '_SESSION__' . $session3rd, $data);

        return $this->successReturn($session3rd, '成功', self::errcode_ok);
    }
    /**
     *
     * 验证用户是否登陆
     */
    public function checkLoginStatus()
    {
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        $nickname = input("nickname", '', 'htmlspecialchars_decode');
        $headimgurl = input("headimgurl", '', 'htmlspecialchars_decode');
        $info = cache(config('wechat.miniapp.appid') . '_SESSION__' . $session3rd);
        if (empty($info)) {
            return $this->errorReturn(self::errcode_login_fail, '用户登陆失效');
        }
        if (!array_key_exists('openid', $info) || empty($info)) {
            return $this->errorReturn(self::errcode_login_fail, '用户登陆失效,需重新登陆');
        }
        if(isset($info['uid'])){
            $update = [];
            $update['nickname'] = $nickname;
            $update['headimgurl'] = $headimgurl;
            UserModel::userEdit(['id'=>$info['uid']],$update);
        }
        return $this->successReturn('', '登陆有效', self::errcode_ok);
    }

    /**
     * @Notes:获取手机号
     * @Interface getPhone
     * @author: zy
     * @Time: 2021/08/09   14:50
     */
    public function getPhone()
    {
        $encryptedData = input("encryptedData", '', 'htmlspecialchars_decode');
        $iv = input("iv", '', 'htmlspecialchars_decode');
        $WechatService = new TokenService();

        $result = $WechatService->session();
        $sessionKey = $result['session_key'];

        $result = \We::WeMiniCrypt(config('wechat.miniapp'))->decode($iv, $sessionKey, $encryptedData);
        custom_log('授权获取手机号',print_r($result,true));
        //手机号已存在 返回已授权
        return json_encode(['code' => 200, 'data' => $result]);
    }
}