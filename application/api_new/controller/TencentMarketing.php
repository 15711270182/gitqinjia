<?php
/**
 * -*- coding: utf-8 -*-
 * @name: TencentMarketing
 * @author: zhangtao
 * @date: 2021/12/10 上午11:34
 * @description：腾讯广告投放数据
 * @update: 2021/12/10 上午11:34
 */


namespace app\api_new\controller;


class TencentMarketing
{
    private  $account_id = 21297261;                                # 推广帐号 id，有操作权限的帐号 id，包括代理商和广告主帐号 id
    private  $user_action_set_id = 1200281657;                      # 用户行为源 id，完美亲家
    private  $base_url = 'https://api.e.qq.com/v1.3/';              # 接口地址
    private  $wechat_app_id = 'wx70d65d2170dbacd7';                 # 完美亲家小程序appid
    private  $access_token = '6d7e2e1a269bd74ee50210c01b4f5fde';    # 授权令牌，腾讯广告开发者中完美亲家应用中的 access_token，设置永久
    private  $page_url_verify = 'http://www.qq.com/';

    /**
     * @param string $click_id          用户标识
     * @param string $page_url          落地页路径
     * @param string $wechat_open_id    微信用户openid
     * @return bool|string
     * @throws \Exception
     */
    public function user_actions_add( $click_id,  $page_url,  $wechat_open_id)
    {
        $interface = 'user_actions/add';
        $url = $this->base_url . $interface;

        $common_parameters = array (
            'access_token' => $this->access_token,
            'timestamp' => time(),
            'nonce' => md5(uniqid('', true))
        );

        $parameters = array (
            'account_id' => $this->account_id,
            'user_action_set_id' => $this->user_action_set_id,
            'actions' =>
                array (
                    0 =>
                        array (
                            'action_time' => time(),
                            'user_id' =>
                                array (
                                    'wechat_openid' => $wechat_open_id,
                                    'wechat_app_id' => $this->wechat_app_id
                                ),
                            'action_type' => 'REGISTER',
                            // 'channel' => 'CHANNEL_NATURAL',
                            'trace' =>
                                array(
                                    'click_id' => $click_id
                                ),
                            'url' => $this->page_url_verify . $page_url
                        ),
                ),
        );

        $parameters = json_encode($parameters);
        // var_dump($parameters);die;
        $request_url = $url . '?' . http_build_query($common_parameters);
        // return sendHttpRequest($request_url,'',$parameters);
        return $this->execute_curl($request_url, $parameters);
    }

    /**
     * 发起 curl 请求
     * @param string $request_url 请求路径
     * @param string $parameters 请求参数
     * @return bool|string
     * @throws \Exception
     */
    public function execute_curl(string $request_url, string $parameters)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type:application/json"
        ));

        $response = curl_exec($curl);
        if (curl_error($curl)) {
            $error_msg = curl_error($curl);
            $error_no = curl_errno($curl);
            curl_close($curl);
            throw new \Exception($error_msg, $error_no);
        }
        curl_close($curl);
        return $response;
    }

}