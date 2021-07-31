<?php
/**
 * Created by PhpStorm.
 * User: it-zy
 * Date: 2020/13/52
 * Time: 18:57
 */
use think\Cache;
class Easemob
{
    public function __construct()
    {
        $easemob_config = config('easemob.easemob_config');
        $this->url = $easemob_config['url'];
    }

    /*
     * 环信授权注册
     */
    public function easenobAuthorizedRegistration($url,$jsonStr,$token){
        $easemob_config = config('easemob.easemob_config');
//        $url = 'https://a1.easemob.com/1157180602228592/bossche/users';
//        $jsonStr =  '{"username":"790623101","password":"123456"}';
        if(empty($url)||empty($jsonStr)||empty($token)){
            return false;
        }
        $httpHeader = $easemob_config['header'].$token;
        $re = $this->easemob_curl('POST',$url,json_encode($jsonStr),$httpHeader);
        return json_decode($re,true);
    }


    /**
     * 获取环信token
     * @return mixed
     */
    public function getEasemobToken(){
        $token = cache('easemob_token');
        if (!empty($token)){
            return $token;
        }
        $easemob_config = config('easemob.easemob_config');
        $url = $easemob_config['url'].'token';
        $data = $easemob_config['token_data'];
        $header = $easemob_config['token_header'];
        $rest_data = $this->easemob_curl('POST',$url,json_encode($data),$header);
        $rest_data = json_decode($rest_data,true);
        if (isset($rest_data['access_token'])){
            $token = $rest_data['access_token'];
            cache('easemob_token',$token,7200);
            return $token;
        }else{
            return false;
        }
    }


    /*
    * 处理环信请求数据
    */
    private function easemobJson($url,$jsonStr='',$method){
        $token = $this->getEasemobToken();

        if(empty($url)||empty($token)||empty($method)){
            return false;
        }
        $httpHeader = 'Authorization: Bearer '.$token;

        $re = $this->easemob_curl($method,$url,json_encode($jsonStr),$httpHeader);
        return json_decode($re,true);
    }
    /*
     * curl
     * 环信发送请求
     */
    private function easemob_curl($method,$url,$data='',$httpHeader=''){
        if(empty($url)){
            return false;
        }
        $ch = curl_init();
        $timeout = 300;
        curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_REFERER, "http://www.111cn.net/"); // 伪装来路

        switch ($method){
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);//TRUE 时会设置 HTTP 的 method 为 GET，由于默认是 GET，所以只有 method 被修改时才需要这个选项。
                break;
            case "POST":
                #curl_setopt($curl, CURLOPT_POST,true);//TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。
                #curl_setopt($curl, CURLOPT_NOBODY, true);//TRUE 时将不输出 BODY 部分。同时 Mehtod 变成了 HEAD。修改为 FALSE 时不会变成 GET。
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");//HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
                //设置提交的信息
                if(!empty($data)){
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);//全部数据使用HTTP协议中的 "POST" 操作来发送。
                }
                break;
            case "PUT" :
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
                break;
            case "DELETE":
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
                break;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // URL为SSL时添加这行可解决页面空白
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$httpHeader]);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }


    /**
     * 取汉字的第一个字的首字母
     * @param  $str
     * @return string|null
     */
    public function getFirstCharter($str){
        if(empty($str)){return '';}
        $fchar=ord($str{0});
        if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
        $s1=iconv('UTF-8','GBK//IGNORE',$str);
        $s2=iconv('GBK//IGNORE','UTF-8',$s1);
        $s=$s2==$str?$s1:$str;
        $asc=ord($s{0})*256+ord($s{1})-65536;
        if($asc>=-20319&&$asc<=-20284) return 'A';
        if($asc>=-20283&&$asc<=-19776) return 'B';
        if($asc>=-19775&&$asc<=-19219) return 'C';
        if($asc>=-19218&&$asc<=-18711) return 'D';
        if($asc>=-18710&&$asc<=-18527) return 'E';
        if($asc>=-18526&&$asc<=-18240) return 'F';
        if($asc>=-18239&&$asc<=-17923) return 'G';
        if($asc>=-17922&&$asc<=-17418) return 'H';
        if($asc>=-17417&&$asc<=-16475) return 'J';
        if($asc>=-16474&&$asc<=-16213) return 'K';
        if($asc>=-16212&&$asc<=-15641) return 'L';
        if($asc>=-15640&&$asc<=-15166) return 'M';
        if($asc>=-15165&&$asc<=-14923) return 'N';
        if($asc>=-14922&&$asc<=-14915) return 'O';
        if($asc>=-14914&&$asc<=-14631) return 'P';
        if($asc>=-14630&&$asc<=-14150) return 'Q';
        if($asc>=-14149&&$asc<=-14091) return 'R';
        if($asc>=-14090&&$asc<=-13319) return 'S';
        if($asc>=-13318&&$asc<=-12839) return 'T';
        if($asc>=-12838&&$asc<=-12557) return 'W';
        if($asc>=-12556&&$asc<=-11848) return 'X';
        if($asc>=-11847&&$asc<=-11056) return 'Y';
        if($asc>=-11055&&$asc<=-10247) return 'Z';
        return '#';
    }



    /*
     * 用户重置环信密码
     */
    public function easenobUserReset($url,$jsonStr='',$token){
        if(empty($url)||empty($jsonStr)||empty($token)){
            return false;
        }
        $httpHeader = 'Authorization: Bearer '.$token;

        $re = $this->easemob_curl('PUT',$url,json_encode($jsonStr),$httpHeader);
        return json_decode($re,true);
    }

    /*
    * 获取App中所有的群组（可分页）
    */
    public function easenobChatGroups($limit=''){
        if(!empty($limit)){
            $url = $this->url.'chatgroups?limit='.$limit;
        }else{
            $url = $this->url.'chatgroups';
        }
        if(empty($url)){
            return false;
        }
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 获取一个用户参与的所有群组
     * @param $username 要查询的用户所在群组帐号ID
    */
    public function easenobJoinedChatGroups($username){
        if(empty($username)){
            return false;
        }
        $url = $this->url.'users/'.$username.'/joined_chatgroups';
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 获取群组详情
     * @param $group_ids 需要获取的群组ID
    */
    public function easenobChatGroupsDetail($group_ids){
        if(empty($group_ids)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_ids;
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 创建一个群组
     * @param $groupname 群组名称，必传
     * @param $desc 群组描述，必传
     * @param $public 是否是公开群，ture,false  必传
     * @param $maxusers 群组成员最大数（包括群主），值为数值类型，默认值200，最大值2000，非必传
     * @param $members_only 加入群是否需要群主或者群管理员审批，默认是false  非必传
     * @param $allowinvites 是否允许群成员邀请别人加入此群, true:允许群成员邀请人加入此群，false:只有群主或者管理员才可以往群里加人 非必传
     * @param $owner 群组的管理员帐号 必传
     * @param $members 群组成员  数组元素至少一个 如 ['user2','user3'](注：群主user1不需要写入到members里面) 非必传
    */
    public function easenobCreateGroups($groupname,$desc,$public,$maxusers = 200,$members_only = '',$allowinvites = '',$owner,$members = []){
        if(empty($groupname)||empty($desc)||empty($public)||empty($owner)){
            return false;
        }
        $jsonStr =  [
            'groupname' => $groupname,
            'desc' => $desc,
            'public'=>$public,
            'maxusers'=>!empty($maxusers)?$maxusers:200,
            'members_only'=>!empty($members_only)?$members_only:false,
            'allowinvites'=>!empty($allowinvites)?$allowinvites:true,
            'owner'=>$owner,
            'members'=>$members

        ];
        $url = $this->url.'chatgroups';
        $data = $this->easemobJson($url,$jsonStr,'POST');
        return $data;
    }
    /*
     * 修改群组信息
     * @param $group_id 需要修改的群组ID
     * @param $groupname 群组名称 修改时值不能包含斜杠（“/“），非必传
     * @param $description 群组描述 修改时值不能包含斜杠（”/“），非必传
     * @param $maxusers  群组成员最大数（包括群主)  非必传
     * @param $membersonly 加入群组是否需要群主或者群管理员审批 true:是，false:否 非必传
    */
    public function easenobEditGroups($group_id,$groupname='',$desc='',$maxusers='',$members_only=''){
        if(empty($group_id)){
            return false;
        }
        $jsonStr =  [
            'groupname' => $groupname,
            'description' => $desc,
            'maxusers'=>$maxusers,
            'membersonly'=>$members_only
        ];
        if(empty($jsonStr['groupname'])){
            unset($jsonStr['groupname']);
        }
        if(empty($jsonStr['description'])){
            unset($jsonStr['description']);
        }
        if(empty($jsonStr['maxusers'])){
            unset($jsonStr['maxusers']);
        }
        if(empty($jsonStr['membersonly'])){
            unset($jsonStr['membersonly']);
        }
        $jsonStr = !empty($jsonStr)?$jsonStr:'';
        $url = $this->url.'chatgroups/'.$group_id;
        $data = $this->easemobJson($url,$jsonStr,'PUT');
        return $data;
    }
    /*
     * 删除一个群组
     * @param $group_id 需要删除的群组ID
    */
    public function easenobDeleteGroups($group_id){
        if(empty($group_id)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }
    /*
     * 分页获取群组成员
     * @param $group_id 需要获取的群组ID
     * @param $pagenum 当前页数
     * @param $pagesize 每页条数 默认200条
    */
    public function easenobChatGroupsUsers($group_id,$pagenum='',$pagesize=''){
        if(empty($group_id)){
            return false;
        }
        $pagenum = !empty($pagenum)?$pagenum:1;
        $pagesize = !empty($pagesize)?$pagesize:200;
        $url = $this->url.'chatgroups/'.$group_id.'/users?pagenum='.$pagenum.'&pagesize='.$pagesize;
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 添加单个群组成员
     * @param $group_id 需要获取的群组ID
     * @param $username 需要添加的 IM 用户名
    */
    public function easenobAddGroupsUser($group_id,$username){
        if(empty($group_id)||empty($username)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/users/'.$username;
        $data = $this->easemobJson($url,'','POST');
        return $data;
    }
    /*
     * 批量添加群组成员
     * @param $group_id 需要获取的群组ID
     * @param $usernames 要添加到群中的成员用户名 ['user1','user2']
    */
    public function easenobAddGroupsUsers($group_id,$usernames = []){
        if(empty($group_id)||empty($usernames)){
            return false;
        }
        $jsonStr['usernames']=$usernames;
        $url = $this->url.'chatgroups/'.$group_id.'/users';
        $data = $this->easemobJson($url,$jsonStr,'POST');
        return $data;
    }
    /*
     * 移除单个群组成员
     * @param $group_id 需要获取的群组ID
     * @param $username 需要移除的 IM 用户名
    */
    public function easenobDeleteGroupsUser($group_id,$username){
        if(empty($group_id)||empty($username)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/users/'.$username;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }

    /*
     * 批量移除群组成员
     * @param $group_id 需要获取的群组ID
     * @param $memebers 移除群成员，用户名之间用英文逗号分隔 如:user1,user2,user3
     *
    */
    public function easenobDeleteGroupsUsers($group_id,$memebers){
        if(empty($group_id)||empty($memebers)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/users/'.$memebers;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }
    /*
     * 获取群管理员列表
     * @param $group_id 需要获取的群组ID
     */
    public function easenobGroupsAdminList($group_id){
        if(empty($group_id)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/admin';
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 添加群管理员
     * @param $group_id 需要获取的群组ID
     * @param $newadmin 添加的新管理员用户帐号ID 必传
    */
    public function easenobAddGroupsAdmin($group_id,$newadmin){
        if(empty($group_id)||empty($newadmin)){
            return false;
        }
        $jsonStr['newadmin'] = $newadmin;
        $url = $this->url.'chatgroups/'.$group_id.'/admin';
        $data = $this->easemobJson($url,$jsonStr,'POST');
        return $data;
    }
    /*
     * 批量移除群组成员
     * @param $group_id 需要移除的管理员所在群组ID
     * @param $oldadmin 需移除的管理员的用户名
    */
    public function easenobDeleteGroupsAdmin($group_id,$oldadmin){
        if(empty($group_id)||empty($oldadmin)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/admin/'.$oldadmin;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }
    /*
     * 转让群组
     * @param $group_id 需要转让的群组ID 必传
     * @param $newowner 群组的新管理员ID 必传
    */
    public function easenobMoveChatGroups($group_id,$newowner){
        if(empty($group_id)||empty($newowner)){
            return false;
        }
        $jsonStr['newowner'] = $newowner;
        $url = $this->url.'chatgroups/'.$group_id;
        $data = $this->easemobJson($url,$jsonStr,'PUT');
        return $data;
    }
    /*
     * 查询群组黑名单
     * @param $group_id 需要查询的群组ID 必传
    */
    public function easenobBlocksChatGroups($group_id){
        if(empty($group_id)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/blocks/users';
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 添加单个用户至群组黑名单
     * @param $group_id 需要添加黑名单的群组ID 必传
     * @param $username 要添加的 IM 用户名 必传
    */
    public function easenobAddUserBlocks($group_id,$username){
        if(empty($group_id)||empty($username)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/blocks/users/'.$username;
        $data = $this->easemobJson($url,'','POST');
        return $data;
    }
    /*
     * 批量添加用户至群组黑名单
     * @param $group_id 需要添加黑名单的群组ID 必传
     * @param $usernames 添加的用户名ID ['user1','user2']必传
    */
    public function easenobAddUsersBlocks($group_id,$usernames = []){
        if(empty($group_id)||empty($usernames)){
            return false;
        }
        $jsonStr['usernames']=$usernames;
        $url = $this->url.'chatgroups/'.$group_id.'/blocks/users';
        $data = $this->easemobJson($url,$jsonStr,'POST');
        return $data;
    }
    /*
     * 从群组黑名单移除单个用户
     * @param $group_id 需要移除黑名单的群组ID 必传
     * @param $username 要移除的IM 用户名 必传
    */
    public function easenobDeleteUserBlocksGroups($group_id,$username){
        if(empty($group_id)||empty($username)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/blocks/users/'.$username;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }
    /*
     * 批量从群组黑名单移除用户
     * @param $group_id 需要移除黑名单的群组ID
     * @param $usernames 要移除的IM 用户名 如 user1,user2,user3
     */
    public function easenobDeleteUsersBlocksGroups($group_id,$usernames){
        if(empty($group_id)||empty($usernames)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/blocks/users/'.$usernames;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }

    /*
     * 添加禁言
     * @param $group_id 需要禁言的群组ID 必传
     * $param $mute_duration 禁言的时间，单位毫秒，如果是“-1000”代表永久 必传
     * $param $usernames 要被添加禁言用户的 ID ['user1','user2'] 必传
     */
    public function easenobMuteChatGroups($group_id,$mute_duration,$usernames){
        if(empty($group_id) || empty($mute_duration) || empty($usernames)){
            return false;
        }
        $jsonStr = [
            'mute_duration'=>$mute_duration,
            'usernames'=>$usernames
        ];
        $url = $this->url.'chatgroups/'.$group_id.'/mute';
        $data = $this->easemobJson($url,$jsonStr,'POST');
        return $data;
    }
    /*
     * 移除禁言
     * @param $group_id 需要移除禁言的群组ID
     * @param $members 要移除禁言的IM 用户名 如 user1,user2,user3
     */
    public function easenobDeleteMuteChatGroups($group_id,$members){
        if(empty($group_id)||empty($members)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/mute/'.$members;
        $data = $this->easemobJson($url,'','DELETE');
        return $data;
    }
    /*
     * 获取当前群组的禁言用户列表
     * @param $group_id 需要获取的禁言群组ID
     */
    public function easenobMuteListChatGroups($group_id){
        if(empty($group_id)){
            return false;
        }
        $url = $this->url.'chatgroups/'.$group_id.'/mute';
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
    /*
     * 查看用户在线状态
     * @param $username 用户名
    */
    public function easenobOnlineStatus($username){
        if(empty($username)){
            return false;
        }
        $url = $this->url.'users/'.$username.'/status';
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
     /*
     * 查询离线消息数
     * @param $owner_username 用户名
    */
    public function easenobOfflineMsgCount($owner_username){
        if(empty($owner_username)){
            return false;
        }
        $url = $this->url.'users/'.$owner_username.'/offline_msg_count';
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }
     /*
     * 查询离线消息状态
     * @param $username 用户名
    */
    public function easenobOfflineMsgStatus($username,$msg_id){
        if(empty($username)){
            return false;
        }
        $url = $this->url.'users/'.$username.'/offline_msg_status/'.$msg_id;
        $data = $this->easemobJson($url,'','GET');
        return $data;
    }

}