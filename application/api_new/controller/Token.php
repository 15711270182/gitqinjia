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
            $is_have = UserModel::userFind(['unionid' => $unionid]);
            if($is_have){ //公众号支付的用户 无openid
                if(empty($is_have['openid'])){
                    $save_jm = [];
                    $save_jm['openid'] = $openid;
                    UserModel::userEdit(['id' =>$is_have['id']],$save_jm);
                }
            }
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
                    $this->gxWechatCount($unionid);
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
        $data['openid'] = $openid;
        $data['switch_auth'] = 0;
        // $is_have = UserModel::userFind(['openid' => $openid]);
        $is_have = UserModel::userFind(['unionid' => $unionid]);
        if ($is_have) {
            $this->gxWechatCount($unionid);
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
        $this->gxWechatCount($unionid);
        return $this->successReturn($session3rd, '成功', self::errcode_ok);
    }
    /**
     *
     * 验证用户是否登陆
     */
    public function checkLoginStatus()
    {
        $session3rd = input("session3rd", '', 'htmlspecialchars_decode');
        // $nickname = input("nickname", '', 'htmlspecialchars_decode');
        // $headimgurl = input("headimgurl", '', 'htmlspecialchars_decode');
        $info = cache(config('wechat.miniapp.appid') . '_SESSION__' . $session3rd);
        if (empty($info)) {
            return $this->errorReturn(self::errcode_login_fail, '用户登陆失效');
        }
        if (!array_key_exists('openid', $info) || empty($info)) {
            return $this->errorReturn(self::errcode_login_fail, '用户登陆失效,需重新登陆');
        }
        // if(isset($info['uid'])){
        //     $update = [];
        //     $update['nickname'] = $nickname;
        //     $update['headimgurl'] = $headimgurl;
        //     UserModel::userEdit(['id'=>$info['uid']],$update);
        // }
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
    /**
     * @Notes:判断用户是否关注公众号赠送次数
     * @Interface gxWechatCount
     * @author: zy
     * @Time: 2022/01/18   10:11
     */
    public function gxWechatCount($unionid = ''){
        // $unionid = 'oKvpA6Pcrqz3MuREtaboiTTQBguw';
        if(empty($unionid)){
            custom_log('关注公众号','登录接口unionid为空');
        }   
        $is_have = UserModel::userFind(['unionid' => $unionid]);
        if($is_have){
            $where_c = "type = 1 and uid = '{$is_have['id']}' and remarks like '关注公众号%'";
            $count_have = Db::name('tel_count')->where($where_c)->find(); //是否赠送关注公众号的次数
            // var_dump(Db::name('tel_count')->getLastsql());die;
            $where_f = [];
            $where_f['unionid'] = $unionid;
            $where_f['subscribe'] = 1;
            $gx_have = Db::name('wechat_fans')->where($where_f)->find(); //是否关注公众号
            //var_dump($gx_have);die;
            if(empty($count_have) && !empty($gx_have)){
                custom_log('关注公众号','未赠送次数-登录时赠送_'.$unionid);
                //赠送联系次数
                $map = [];
                $map['id'] = $is_have['id'];
                Db::name('userinfo')->where($map)->setInc('count',1);
                //添加增加记录
                $params = [
                    'uid' => $is_have['id'],
                    'type' => 1,
                    'count' => 1,
                    'remarks' => '关注公众号增加1次',
                    'create_at' => time()
                ];
                Db::name('tel_count')->strict(false)->insertGetId($params);
            }
            custom_log('关注公众号','已赠送次数-登录时_'.$unionid);
        }
    }
}