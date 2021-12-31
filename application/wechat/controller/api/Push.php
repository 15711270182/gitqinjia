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

namespace app\wechat\controller\api;

use app\api\controller\v1\Score;
use app\api\model\WechatFans as WechatFansModel;
use app\api\model\WechatFans;
use app\wechat\service\FansService;
use app\wechat\service\MediaService;
use app\wechat\service\WechatService;
use app\api\service\ScoreService;
use library\Controller;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\facade\Hook;

/**
 * 微信消息推送处理
 * Class Push
 * @package app\wechat\controller\api
 */
class Push extends Controller
{

    /**
     * 微信APPID
     * @var string
     */
    protected $appid;

    /**
     * 微信用户OPENID
     * @var string
     */
    protected $openid;

    /**
     * 消息是否加密码
     * @var boolean
     */
    protected $encrypt;


    /**
     * 微信OPENID
     * @var string
     */
    protected $fromOpenid;

    /**
     * 微信消息对象
     * @var array
     */
    protected $receive;

    /**
     * 微信实例对象
     * @var \WeChat\Receive
     */
    protected $wechat;

    /**
     * 强制返回JSON消息
     * @var boolean
     */
    protected $forceJson = false;

    /**
     * 强制客服消息回复
     * @var boolean
     */
    protected $forceCustom = false;

    /**
     * 获取网络出口IP
     * @return mixed
     */
    public function geoip()
    {
        return $this->request->ip();
    }

    /**
     * 消息推送处理接口
     * @return string
     */
    public function index()
    {
        // dump(1);exit;
        try {
            if ($this->request->has('receive', 'post') && WechatService::getType() === 'thr') {
                $this->forceJson = true; // 强制返回JSON到Service转发
                $this->forceCustom = false; // 强制使用客服消息模式推送
                $this->appid = $this->request->post('appid', '', null);
                $this->openid = $this->request->post('openid', '', null);
                $this->encrypt = boolval($this->request->post('encrypt', 0));
                $this->receive = $this->toLower(unserialize($this->request->post('receive', '', null)));
                if (empty($this->appid) || empty($this->openid) || empty($this->receive)) {
                    throw new \think\Exception('微信API实例缺失必要参数[appid,openid,receive]');
                }
            } else {
                $this->forceJson = false; // 暂停返回JSON消息对象
                $this->forceCustom = false; // 暂停使用客户消息模式
                $this->wechat = WechatService::WeChatReceive();
                $this->appid = WechatService::getAppid();
                $this->openid = $this->wechat->getOpenid();
                $this->encrypt = $this->wechat->isEncrypt();
                $this->receive = $this->toLower($this->wechat->getReceive());
            }
            $this->fromOpenid = $this->receive['tousername'];
            // text, event, image, location
            if (method_exists($this, ($method = $this->receive['msgtype']))) {
                if (is_string(($result = $this->$method()))) return $result;
            }
        } catch (\Exception $e) {
            Log::error("{$e->getFile()}:{$e->getLine()} [{$e->getCode()}] {$e->getMessage()}");
        }
        return 'success';
    }

    /**
     * 数组KEY全部转小写
     * @param array $data
     * @return array
     */
    private function toLower(array $data)
    {
        $data = array_change_key_case($data, CASE_LOWER);
        foreach ($data as $key => $vo) if (is_array($vo)) {
            $data[$key] = $this->toLower($vo);
        }
        return $data;
    }

    /**
     * 文件消息处理
     * @return boolean
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function text()
    {
        return $this->keys("wechat_keys#keys#{$this->receive['content']}", false, $this->forceCustom);
    }

    /**
     * 事件消息处理
     * @return boolean|string
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function event()
    {
        switch (strtolower($this->receive['event'])) {
            case 'subscribe':
                $this->updateFansinfo(true);
                if (isset($this->receive['eventkey']) && is_string($this->receive['eventkey'])) {
                    if (($key = preg_replace('/^qrscene_/i', '', $this->receive['eventkey']))) {
                        return $this->keys("wechat_keys#keys#{$key}", false, true);
                    }
                }
                return $this->keys('wechat_keys#keys#subscribe', true, $this->forceCustom);
            case 'unsubscribe':
                return $this->updateFansinfo(false);
            case 'click':
                return $this->keys("wechat_keys#keys#{$this->receive['eventkey']}", false, $this->forceCustom);
            case 'scancode_push':
            case 'scancode_waitmsg':
                if (empty($this->receive['scancodeinfo'])) return false;
                if (empty($this->receive['scancodeinfo']['scanresult'])) return false;
                return $this->keys("wechat_keys#keys#{$this->receive['scancodeinfo']['scanresult']}", false, $this->forceCustom);
            case 'scan':
                if (empty($this->receive['eventkey'])) return false;
                return $this->keys("wechat_keys#keys#{$this->receive['eventkey']}", false, $this->forceCustom);
            default:
                return false;
        }
    }

    /**
     * 关键字处理
     * @param string $rule 关键字规则
     * @param boolean $isLast 重复回复消息处理
     * @param boolean $isCustom 是否使用客服消息发送
     * @return boolean|string
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function keys($rule, $isLast = false, $isCustom = false)
    {
//        custom_log('qweqwe','url=>' . 123);

        list($table, $field, $value) = explode('#', $rule . '##');

        $data = Db::name($table)->where([$field => $value])->find();
        if (empty($data['type']) || (array_key_exists('status', $data) && empty($data['status']))) {
            return $isLast ? false : $this->keys('wechat_keys#keys#default', true, $isCustom);
        }

        switch (strtolower($data['type'])) {
            case 'keys':
                $content = empty($data['content']) ? $data['name'] : $data['content'];
                return $this->keys("wechat_keys#keys#{$content}", $isLast, $isCustom);
            case 'text':
                return $this->sendMessage('text', ['content' => $data['content']], $isCustom);
            case 'customservice':
                return $this->sendMessage('customservice', ['content' => $data['content']], false);
            case 'voice':
                if (empty($data['voice_url']) || !($mediaId = MediaService::upload($data['voice_url'], 'voice'))) return false;
                return $this->sendMessage('voice', ['media_id' => $mediaId], $isCustom);
            case 'image':
                if (empty($data['image_url']) || !($mediaId = MediaService::upload($data['image_url'], 'image'))) return false;
                return $this->sendMessage('image', ['media_id' => $mediaId], $isCustom);
            case 'news':
                list($news, $articles) = [MediaService::news($data['news_id']), []];
                if (empty($news['articles'])) return false;
                foreach ($news['articles'] as $vo) array_push($articles, [
                    'url' => url("@wechat/api.review/view", '', false, true) . "?id={$vo['id']}",
                    'title' => $vo['title'], 'picurl' => $vo['local_url'], 'description' => $vo['digest'],
                ]);
                return $this->sendMessage('news', ['articles' => $articles], $isCustom);
            case 'music':
                if (empty($data['music_url']) || empty($data['music_title']) || empty($data['music_desc'])) return false;
                return $this->sendMessage('music', [
                    'thumb_media_id' => empty($data['music_image']) ? '' : MediaService::upload($data['music_image'], 'image'),
                    'description' => $data['music_desc'], 'title' => $data['music_title'],
                    'hqmusicurl' => $data['music_url'], 'musicurl' => $data['music_url'],
                ], $isCustom);
            case 'video':
                if (empty($data['video_url']) || empty($data['video_desc']) || empty($data['video_title'])) return false;
                $videoData = ['title' => $data['video_title'], 'introduction' => $data['video_desc']];
                if (!($mediaId = MediaService::upload($data['video_url'], 'video', $videoData))) return false;
                return $this->sendMessage('video', ['media_id' => $mediaId, 'title' => $data['video_title'], 'description' => $data['video_desc']], $isCustom);
            case 'card':
                    if (empty($data['card_url']) || !($mediaId = MediaService::upload($data['card_url'], 'image')) || empty($data['card_title'])){
                         custom_log('card_data',print_r($data,true));
                         return false;
                    }
                    $mediaId = MediaService::upload($data['card_url']);
                    custom_log('card_mediaId',$mediaId);
                    return $this->sendDiyMsg('card', [
                        'msgtype' => 'miniprogrampage',
                        'touser' => $this->openid,
                        'miniprogrampage' => [
                            "title" => $data['card_title'],
                            "appid" => $data['to_appid'],
                            "pagepath" => $data['pages'],
                            "thumb_media_id" => $mediaId,
                        ],
                    ], $isCustom);

                break;

                default:
                return false;
                break;
        }
    }
    public function sendDiyMsg($type,$data,$isCustom){

        $json = json_encode($data, JSON_UNESCAPED_UNICODE); // php5.4+
        $access_token = $this->get_accessToken();

        /*
         * POST发送https请求客服接口api
         */
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
        // 以'json'格式发送post的https请求
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($json)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        $output = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl); // 捕抓异常
        }
        curl_close($curl);
        if ($output == 0) {
            echo 'success';
            exit();
        }

        }


        public function get_accessToken()
        {
          return   \We::WeChatMedia(config('wechat.wechat'))->getAccessToken();
        }

        /**
         * 发送消息到微信
         * @param string $type 消息类型（text|image|voice|video|music|news|mpnews|wxcard）
         * @param array $data 消息内容数据对象
         * @param boolean $isCustom 是否使用客服消息发送
         * @return array|boolean
         * @throws \WeChat\Exceptions\InvalidDecryptException
         * @throws \WeChat\Exceptions\InvalidResponseException
         * @throws \WeChat\Exceptions\LocalCacheException
         * @throws \think\Exception
         * @throws \think\exception\PDOException
         */
        private
        function sendMessage($type, $data, $isCustom = false)
        {
            if ($isCustom) {
                WechatService::WeChatCustom()->send(['touser' => $this->openid, 'msgtype' => $type, "{$type}" => $data]);
            } else switch (strtolower($type)) {
                case 'text': // 发送文本消息
                    $reply = ['CreateTime' => time(), 'MsgType' => 'text', 'ToUserName' => $this->openid, 'FromUserName' => $this->fromOpenid, 'Content' => $data['content']];
                    return $this->forceJson ? json_encode($reply, JSON_UNESCAPED_UNICODE) : WechatService::WeChatReceive()->reply($reply, true, $this->encrypt);
                case 'image': // 发送图片消息
                    return $this->buildMessage($type, ['MediaId' => $data['media_id']]);
                case 'voice': // 发送语言消息
                    return $this->buildMessage($type, ['MediaId' => $data['media_id']]);
                case 'video': // 发送视频消息
                    return $this->buildMessage($type, ['Title' => $data['title'], 'MediaId' => $data['media_id'], 'Description' => $data['description']]);
                case 'music': // 发送音乐消息
                    return $this->buildMessage($type, ['Title' => $data['title'], 'Description' => $data['description'], 'MusicUrl' => $data['musicurl'], 'HQMusicUrl' => $data['musicurl'], 'ThumbMediaId' => $data['thumb_media_id']]);
                case 'customservice': // 转交客服消息
                    if ($data['content']) $this->sendMessage('text', $data, true);
                    return $this->buildMessage('transfer_customer_service');
                case 'news': // 发送图文消息
                    $articles = [];
                    foreach ($data['articles'] as $article) array_push($articles, ['PicUrl' => $article['picurl'], 'Title' => $article['title'], 'Description' => $article['description'], 'Url' => $article['url']]);
                    $reply = ['CreateTime' => time(), 'MsgType' => 'news', 'ToUserName' => $this->openid, 'FromUserName' => $this->fromOpenid, 'Articles' => $articles, 'ArticleCount' => count($articles)];
                    return $this->forceJson ? json_encode($reply, JSON_UNESCAPED_UNICODE) : WechatService::WeChatReceive()->reply($reply, true, $this->encrypt);
                default:
                    return 'success';
            }
        }

        /**
         * 消息数据生成
         * @param string $type 消息类型
         * @param string|array $data 消息数据
         * @return string
         * @throws \WeChat\Exceptions\InvalidDecryptException
         */
        private
        function buildMessage($type, $data = [])
        {
            $reply = ['CreateTime' => time(), 'MsgType' => strtolower($type), 'ToUserName' => $this->openid, 'FromUserName' => $this->fromOpenid];
            if (!empty($data)) $reply[ucfirst(strtolower($type))] = $data;
            return $this->forceJson ? json_encode($reply, JSON_UNESCAPED_UNICODE) : WechatService::WeChatReceive()->reply($reply, true, $this->encrypt);
        }

        /**
         * 同步粉丝状态
         * @param boolean $subscribe 关注状态
         * @return boolean
         * @throws \think\Exception
         * @throws \think\exception\PDOException
         */
        private
        function updateFansinfo($subscribe = true)
        {
            custom_log('关注公众号',print_r($subscribe,true));
            if ($subscribe) {
            
                $user = WechatService::WeChatUser()->getUserInfo($this->openid);
                custom_log('关注公众号-用户信息',print_r($user,true));
                $map = array();
                $map['openid'] = $this->openid;
                $is_have = db::name('wechat_fans')->where($where)->find();
                if (!$is_have) 
                {
                    //首次关注 松三次机会
                    $map = array();
                    $map['unionid'] = $user['unionid'];
                    $userinfo = db::name('userinfo')->where($map)->find();
                    if ($userinfo) 
                    {
                        $map = array();
                        $map['id'] = $userinfo['id'];
                        db::name('userinfo')->where($map)->setInc('count',3);
                        //添加增加记录
                        $params = [
                            'uid' => $userinfo['id'],
                            'type' => 1,
                            'count' => 3,
                            'remarks' => '关注公众号增加3次',
                            'create_at' => time()
                        ];
                        Db::name('tel_count')->strict(false)->insertGetId($params);

                        // ScoreService::instance()->weightScoreInc($userinfo['id'],28);
                    }
                    
                }else{
                    if(empty($is_have['subscribe_at'])){  //表里有数据  但是没关注 也算收关

                         //首次关注 松三次机会
                        $map = array();
                        $map['unionid'] = $user['unionid'];
                        $userinfo = db::name('userinfo')->where($map)->find();
                        if ($userinfo) 
                        {
                            $map = array();
                            $map['id'] = $userinfo['id'];
                            db::name('userinfo')->where($map)->setInc('count',3);
                            //添加增加记录
                            $params = [
                                'uid' => $userinfo['id'],
                                'type' => 1,
                                'count' => 3,
                                'remarks' => '关注公众号增加3次',
                                'create_at' => time()
                            ];
                            Db::name('tel_count')->strict(false)->insertGetId($params);
                        }
                    }
                }
                return $res = FansService::set(array_merge($user, ['subscribe' => '1', 'appid' => $this->appid]));

                

                
                
          
            } else {
                $user = ['subscribe' => '0', 'openid' => $this->openid, 'appid' => $this->appid];
                return data_save('WechatFans', $user, 'openid', ['appid' => $this->appid]);
            }
        }
		
		public function test(){
			 Hook::exec(['app\\api\\behavior\\Ad','collect'],['type'=>4,'basic_id'=>536,'look_uid'=>0]);
		}

    }
