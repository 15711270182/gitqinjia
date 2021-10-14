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

namespace app\admin\controller;

use app\api\service\RecommendService;
use library\Controller;
use app\api\controller\Poster;
use app\api\controller\Base;
use app\api\service\Qrcode;
use app\api\model\Poster as PosterModel;
use app\api\model\User as UserModel;
use app\api\service\Image as Image;
use app\api\service\Upload;
use library\tools\Data;
use think\Db;

/**
 * 系统用户管理
 * Class User
 * @package app\admin\controller
 */
class Member extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'userinfo';
    public $table2 = 'children';
    public $table3 = 'view_info_record';
    /**
     * 系统用户管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '用户管理';
        $search_tel = input('search_tel');
        if($search_tel == 'asc'){
            $order = "look_tel asc";
        }
        if($search_tel == 'desc'){
           $order = "look_tel desc";
        }
        if(empty($order)){
            $order = 'u.id desc';
        }
        $content = input('content','');
        $type_c = input('type_c','');
        $bid = input('bid');
        $age_min = input('age_min','');
        $age_max = input('age_max','');
        $height_min = input('height_min','');
        $height_max = input('height_max','');
        $province = input('province','');
        $residence = input('residence','');

        $hometown = input('hometown','');
        $filename = env('root_path') . 'public/static/plugs/jquery/area/area.json';
        $this->provinces  = array_column(json_decode(file_get_contents($filename), true), 'name');
        $this->hometown = $hometown;

        $this->url = urldecode($_SERVER['REQUEST_URI']);
        $this->url = str_replace("&search_tel=asc", '', $this->url);
        $this->url = str_replace("&search_tel=desc", '', $this->url);
        $this->content    = $content;
        $this->type_c     = $type_c;
        $this->bid = input('bid');
        $this->age_min    = $age_min;
        $this->age_max    = $age_max;
        $this->height_min    = $height_min;
        $this->height_max    = $height_max;
        $where = "u.unionid <> ''";
        if($type_c){
            switch ($content) { //内容筛选
                case '1':$where .= " and u.id = {$type_c}";break;
                case '2':$where .= " and u.nickname like '%{$type_c}%'";break;
                case '3':$where .= " and c.phone = {$type_c}";break;
                case '4':
                    $uid = DB::name('relation')->where(['bid'=>$type_c])->column('uid');
                    if(!empty($uid)){
                        $uid = implode(',',$uid);
                        $where .=" and u.id in ({$uid})";
                    }else{
                        $where .=" and u.id  = ''";
                    }
                    break;
                default:break;
            }
        }
        //现居地筛选
        if(!empty($province) && !empty($residence)){
            if($residence != '请选择'){
                $residence =  str_replace(['市'],'',$residence);
                $where .= " and c.residence like '%{$residence}%'";
            }
        }
        if(!empty($bid)){
            $uid = DB::name('relation')->where(['bid'=>$bid])->column('uid');
            if(!empty($uid)){
                $uid = implode(',',$uid);
                $where .=" and u.id in ({$uid})";
            }else{
                $where .=" and u.id  = ''";
            }
        }
        if(!empty($age_min) || !empty($age_max)) {
            if (!empty($age_min) && !empty($age_max)) {
                if($age_min == $age_max){
                    $this->error('年龄区间重复');
                }
                if($age_min > $age_max){
                    $this->error('年龄最小值不能大于最大值');
                }
                $e_year = date('Y') - $age_min;
                $s_year = date('Y') - $age_max;
                $where .= " and c.year between '{$s_year}' and '{$e_year}'";
            }elseif(!empty($age_min)){
                $year = date('Y') - $age_min;
                $where .= " and c.year = '{$year}'";
            }else{
                $year = date('Y') - $age_max;
                $where .= " and c.year = '{$year}'";
            }
        }
        if(!empty($height_min) || !empty($height_max)) {
            if (!empty($height_min) && !empty($height_max)) {
                if($height_min == $height_max){
                    $this->error('身高区间重复');
                }
                if($height_min > $height_max){
                    $this->error('身高最小值不能大于最大值');
                }
                $where .= " and c.height between '{$height_min}' and '{$height_max}'";
            }elseif(!empty($height_min)){
                $where .= " and c.height = '{$height_min}'";
            }else{
                $where .= " and c.height = '{$height_max}'";
            }
        }
        $info_status = input('info_status');
        if(!empty($info_status)){
            if($info_status == 1){ //完善资料
                $where .= " and c.expect_education !=0 and c.min_age !=0 and c.min_height !=0";
            }else{
                $where .= " and c.expect_education = 0 and c.min_age = 0 and c.min_height = 0";
            }
        }
        $is_vip = input('is_vip','');
        // var_dump($is_vip);
        $time = time();
        if($is_vip == 1){
            $where .= " and u.is_vip = 1 and u.endtime >= '{$time}'";
        }
        if($is_vip == 2){
            $where .= " and u.endtime < '{$time}'";
        }
        $field = "u.pair_last_num,u.id,u.nickname,u.headimgurl,u.is_vip,u.add_time,u.endtime,u.count,u.status,c.expect_education,c.min_age,c.min_height,c.id as cid,c.phone,
        c.sex as xingbie,c.is_ban,c.year,c.province,c.residence,c.team_status,c.weight_score,c.remarks_text,(select count(*) from tel_collection t where t.bid = c.uid and t.status=1) as look_tel";
        $equal = 'u.id#id,u.nickname#nickname,c.phone#phone,c.sex#sex,u.status#status,c.education#education,c.year#year,c.team_status#team_status,c.cart#cart,c.house#house,c.hometown#hometown';
        $this->_query($this->table)
                ->alias('u')
                ->field($field)
                ->join('children c', 'u.id = c.uid')
                ->equal($equal)
                ->timeBetween('u.add_time#add_time')
                ->where($where)
                ->order($order)->page();
        // var_dump(DB::name('userinfo')->getLastSql());die;
    }
    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['addtime'] = date('Y-m-d H:i:s',$vo['add_time']);
            $vo['nickname'] = emojiDecode($vo['nickname']);
            $vo['is_false']  = '否';
            if ($vo['id'] <= 200){// 判断是否是测试用户
                $vo['is_false']  = '是';
            }
            $vo['sex'] = '女';
            if ($vo['xingbie'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$vo['year'];
            $vo['address'] = $vo['province']. '-' .$vo['residence'];
            $vo['is_children']  = '是';
            $relation_info = DB::name('relation')->where('uid', $vo['id'])->find();
            $vo['relation_id'] = $relation_info['bid'];
            $relation_user_info = DB::name($this->table)->where('id', $relation_info['bid'])->find();
            $vo['relation_name'] = emojiDecode($relation_user_info['nickname']);

            //被访问数
            $vo['blook_count'] = DB::name('view_info_record')->where(['bid'=>$vo['id']])->group('uid')->count();
            //浏览记录
            $vo['look_count'] = DB::name('view_info_record')->where(['uid'=>$vo['id']])->group('bid')->count();

            //被查看号码数   倒叙排
//            $vo['look_tel'] = DB::name('tel_collection')->where(['bid'=>$vo['id'],'status'=>1])->count();
            //是否完善资料   择偶三项填完
            $vo['info_status'] = 0; //未完善
            if(!empty($vo['expect_education']) && !empty($vo['min_age']) && !empty($vo['min_height'])){
                $vo['info_status'] = 1; //已完善
            }
            $vo['sub_remarks_text'] = $this->subtext($vo['remarks_text'],8);
            $vo['vip'] = 0;//是否是会员
            if($vo['is_vip']== 1 && $vo['endtime']>= time()){ //判断用户是否是会员
                $vo['vip'] = 1;
            }

        }
    }
    public function subtext($text, $length)
    {
        if(mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8').'...';
        } else {
            return $text;
        }

    }
    public function telCountInfo()
    {
        $id = input('id');
        $info_list = Db::table('tel_count')->where('uid', $id)->order('create_at desc')->select();
        foreach ($info_list as $key => $value){
            $info_list[$key]['type'] = $value['type'] == 1 ? '增加' : '消耗';
            $info_list[$key]['create_at'] = date('Y-m-d H:i:s', $value['create_at']);
        }
        $this->assign('title', '号码查看记录');
        $this->assign('list', $info_list);
        return $this->fetch();
    }

    /**
     * @Notes:查看子女详情
     * @Interface look
     * @author: zy
     * @Time: 2021/07/20 11:33
     */
    public function look()
    {
        $id = input("id", '', 'htmlspecialchars_decode');
        $map = ['uid' => $id];
        $realname = Db::name('userinfo')->where(['id'=>$id])->value('realname');
        $pair_last_num = Db::name('userinfo')->where(['id'=>$id])->value('pair_last_num');
        $is_vip = Db::name('userinfo')->where(['id'=>$id])->value('is_vip');
        $endtime = Db::name('userinfo')->where(['id'=>$id])->value('endtime');
        $Children = Db::name('Children')->where($map)->find();
        if ($Children['sex'] == 1){
            $Children['sex_name'] = '男';
        }else{
            $Children['sex_name'] = '女';
        }
        if ($Children['min_height'] == 0) $Children['min_height'] = '未填写';
        if ($Children['min_height'] == 999) $Children['min_height'] = '不限';
        if ($Children['max_height'] == 0) $Children['max_height'] = '未填写';
        if ($Children['max_height'] == 999) $Children['max_height'] = '不限';
        if ($Children['min_age'] == 0) $Children['min_age'] = '未填写';
        if ($Children['min_age'] == 999) $Children['min_age'] = '不限';
        if ($Children['max_age'] == 0) $Children['max_age'] = '未填写';
        if ($Children['max_age'] == 999) $Children['max_age'] = '不限';

        $sex_list = array(
            1 => '男',
            2 => '女'
        );
        // 学历列表学历
        $education_list = array(
            '0' => '暂未填写',
            '1' => '中专',
            '2' => '高中',
            '3' => '大专',
            '4' => '本科',
            '5' => '研究生',
            '6' => '博士'
        );
        // 学历列表学历
        $expect_education_list = array(
            '0' => '暂未填写',
            '1'=>'不限学历',
            '2' => '中专及以下',
            '3' => '高中',
            '4' => '大专以上',
            '5' => '本科以上',
            '6' => '研究生以上'
        );
        // 月收入列表
        $income_list = array(
            '0'=>'暂不填写',
            '1' => '5000 以下',
            '2' => '5000 ~ 8000',
            '3' => '8000 ~ 12000',
            '4' => '12000 ~ 20000',
            '5' => '20000 ~ 30000',
            '6' => '30000 以上'
        );
        $house_list = array(
           '0' => '暂未填写',
            '1' => '已购房',
            '2' => '父母同住',
            '3' => '租房'
        );
        $car_list = array(
            '0' => '暂未填写',
            '1' => '已购车',
            '2' => '近期购车',
            '3' => '无车'
        );

        $img_path_arr = DB::name("qx_user_pictures")->where(['uid'=>$id,'is_del'=>0])->column('img_path'); //查询用户生活照片
        $slider  = implode('|',$img_path_arr);

        $this->is_vip = $is_vip;
        $this->endtime = date('Y-m-d H:i:s',$endtime);
        $this->slider = $slider;
        $this->pair_last_num = $pair_last_num;
        $this->realname = $realname;
        $this->sex_list = $sex_list;
        $this->children = $Children;
        $this->car_list = $car_list;
        $this->house_list = $house_list;
        $this->income_list = $income_list;
        $this->education_list = $education_list;
        $this->expect_education_list = $expect_education_list;
        $this->fetch();
    }
    //保存子女信息
    public function saveChildrenInfo()
    {
        $params = $this->request->post();
        if (empty($params)) $this->error('缺少必要参数!');
        $id = isset($params['id']) ? $params['id'] : 0;
        if ($id == 0) $this->error('缺少必要参数!');
        if ($params['min_height'] == '不限') $params['min_height'] = 999;
        if ($params['min_height'] == '未填写') $params['min_height'] = 0;
        if ($params['max_height'] == '不限') $params['max_height'] = 999;
        if ($params['max_height'] == '未填写') $params['max_height'] = 0;
        if ($params['min_age'] == '不限') $params['min_age'] = 999;
        if ($params['min_age'] == '未填写') $params['min_age'] = 0;
        if ($params['max_age'] == '不限') $params['max_age'] = 999;
        if ($params['max_age'] == '未填写') $params['max_age'] = 0;
        if($params['min_height'] > 0 && $params['max_height'] == 0){
            $params['max_height'] = '999';
        }
        if($params['min_height'] == 0 && $params['max_height'] > 0){
            $params['min_height'] = '999';
        }
        if($params['min_age'] > 0 && $params['max_age'] == 0){
            $params['max_age'] = '999';
        }
        if($params['min_age'] == 0 && $params['max_age'] > 0){
            $params['min_age'] = '999';
        }
        //真实姓名
        $realname = $params['realname'];
        if(isset($params['pair_last_num'])){
            $save_user['pair_last_num'] = $params['pair_last_num'];
        }
        $save_user['realname'] = $realname;
        $save_user['update_time'] = date('Y-m-d H:i::s');
        $uid = DB::name('children')->where(['id'=>$id])->value('uid');
        $res1 = DB::name('userinfo')->where(['id'=>$uid])->update($save_user);
        //头像
        $slider = $this->request->post('slider');
        $img_arr = [];
        if($slider){
            $img_path_arr = DB::name("qx_user_pictures")->where(['uid'=>$uid,'is_del'=>0])->column('img_path');
            $slider_arr =   explode('|',$slider);
            foreach ($slider_arr as $kk=>$vv){
                if(in_array($vv,$img_path_arr)){
                    unset($slider_arr[$kk]);
                }
            }
            if($slider_arr){
                 foreach($slider_arr as $k=>$v){
                    $img_arr[$k]['uid'] = $uid;
                    $img_arr[$k]['img_path'] = $v;
                    $img_arr[$k]['create_time'] = date('Y-m-d H:i:s');
                }
                DB::name("qx_user_pictures")->insertAll($img_arr);
            }
        }
        unset($params['realname']);
        unset($params['slider']);
        if(isset($params['pair_last_num'])){
            unset($params['pair_last_num']);
        }
        //修改子女资料信息
        $params['update_time'] = date('Y-m-d H:i:s');
        $res2 = DB::name('children')->where(['id'=>$id])->update($params);
        if ($res1 && $res2){
            cache('shareposter-'.$uid,NULL);
            cache('getposter-'.$uid,NULL);
            $this->success('保存成功!');
        }else{
            $this->error('保存失败!');
        }
    }
    /**
     * 人工审核电话页面
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function checkphone()
    {
        $id = input('param.id');
        $children = DB::name('children')->where(['uid'=>$id])->find();
        $this->assign('info',$children);
        return $this->fetch();
    }

    /**
     * 人工审核电话页面
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function passphone()
    {
        $id = input('param.id');
        if (!$id){
            $this->error('参数错误！');
        }
        $res = DB::name('children')->where(['id'=>$id])->update(['team_status'=>2]);
        if ($res){
            $this->success('已通过','');
        }else{
            $this->error('重试');
        }
    }


    /**
     * 添加系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->applyCsrfToken();
        $this->_form($this->table, 'form');
    }

    /**
     * 编辑系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->applyCsrfToken();
        $this->_form($this->table, 'form');
    }
    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _form_filter(&$data)
    {
        if ($this->request->isPost()) {
            // 用户权限处理
            $data['authorize'] = (isset($data['authorize']) && is_array($data['authorize'])) ? join(',', $data['authorize']) : '';
            // 用户账号重复检查
            if (isset($data['id'])) unset($data['username']);
            elseif (Db::name($this->table)->where(['username' => $data['username'], 'is_deleted' => '0'])->count() > 0) {
                $this->error("账号{$data['username']}已经存在，请使用其它账号！");
            }
        } else {
            $data['authorize'] = explode(',', isset($data['authorize']) ? $data['authorize'] : '');
            $this->authorizes = Db::name('SystemAuth')->where(['status' => '1'])->order('sort desc,id desc')->select();
        }
    }

    /**
     * 禁用系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止操作！');
        }
        $this->applyCsrfToken();
        $this->_save($this->table2, ['is_ban' => '0']);
    }

    /**
     * 启用系统用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->applyCsrfToken();
        $this->_save($this->table2, ['is_ban' => '1']);
    }

    /**
     * 删除用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止删除！');
        }
        $this->applyCsrfToken();
        $this->_delete($this->table);
    }

    /**
     * 注销用户
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function newglory()
    {
        $this->applyCsrfToken();
        $user_id = $this->request->post('id');
        $user_status = $this->request->post('status');
        $user_info = Db::table($this->table)->where('id', $user_id)->find();
        if($user_status){
            $up_data['status'] = 0; //注销用户时,设置为 0; 该字段为后添加字段,只有 status = 0 时为注销;
        }else{
            $up_data['status'] = 1; //激活用户时,设置为 1;
        }
        Db::startTrans();
        try {
            Db::table($this->table)->where('id', $user_id)->update($up_data);
            Db::table('children')->where('uid', $user_id)->update($up_data);
            Db::table('wechat_fans')->where('unionid', $user_info['unionid'])->update($up_data);
            Db::commit();
            $this->success('修改成功!','');
        } catch (\ErrorException $e){
            $this->error('重试');
        }
    }

    /**
     * @Notes:获取分享海报
     * @Interface getuserposter
     * @author: zy
     * @Time: 2021/08/18
     */
    public function getuserposter(){
        $uid = input('id');
        $cache_url = cache('getposter-'.$uid);
        if($cache_url){
            $share_poster = Db::name('userinfo')->where(['id'=>$uid])->value('share_poster');
            echo '<img src="' . $share_poster . '"  width="750px" height="1200px" alt="">';
            die;
        }
        $field = 'u.nickname,u.headimgurl,u.realname,c.uid,c.year,c.sex,c.height,c.province,c.residence,c.education,c.work,c.remarks';
        $info = Db::table('children')
            ->alias('c')
            ->field($field)
            ->join('userinfo u','c.uid = u.id')
            ->where(['c.uid'=>$uid])
            ->find();
        $data['headimgurl'] = !empty($info['headimgurl'])?$info['headimgurl']:'https://pics.njzec.com/default.png';
        $poster = new Poster();
        $path = './uploads/poster/headImg';
        if(!is_dir($path)){
            mkdir($path,0700,true);
        }
        $head_name = 'img_poster'.$uid.'.png';
        $head_img_path = $path.'/'.$head_name;
        if (!file_exists($head_img_path)){
            $head_path = $poster->getImage($data['headimgurl'], $path, $head_name);
            $head_img_path = $head_path['save_path'];
        }
        $head_img_path = $poster->ssimg1($path.'/', $head_img_path, 80, 80);
        $sid = $uid;
        $path = './uploads/qrcode/';
        $page_path = 'pages/details/details';
        $share_back_path = './uploads/backgroud/bj.png';
        $back_shi = './uploads/backgroud/real@2x.png';
        $back_zhen = './uploads/backgroud/zhen.jpg';
        $header1 = [];
        $header1['path'] = $head_img_path;
        $header1['size'] = 86*2;
        $header1['locate'] = [36*2,95*2];
        $header1['xPos'] = 'left';
        $header2 = [];
        $header2['path'] = $back_shi;
        $header2['size'] = 38*2;
        $header2['locate'] = [84*2,142*2];
        $header2['xPos'] = 'left';
        $header2['yPos'] = 'top';
        $header3 = [];
        $header3['path'] = $back_zhen;
        $header3['size'] = 22*2;
        $header3['locate'] = [196*2,105*2];
        $header3['xPos'] = 'left';
        $local_path =  (new Qrcode())->generateQrCode($path, $sid, $page_path);
        $qrcode['path'] = $local_path;
        $qrcode['size'] = 90*2;
        $qrcode['locate'] = [249*2,476*2];
        $qrcode['xPos'] = 'left';

        $images = [
            $header1,$header2,$header3,$qrcode
        ];

        $len = mb_strlen($info['work']);
        if($len<=4){
            $work = $info['work'];
        }else{
            $work = mb_substr($info['work'], 0,5).'...';
        }
        if(mb_strlen($info['realname']) == 0){
            $name = '家长';
        }else{
            $name = mb_substr($info['realname'], 0,1 ).'家长';
        }
        $sex = '女';
        if($info['sex'] == 1){
            $sex = '男';
        }
        $year = $info['year'].'年';
        $residence = mb_substr($info['residence'], 0,3 );
        $height = $info['height'].'CM';
        switch ($info['education']) {
            case '1':
                $education = '中专';
                break;
            case '2':
                $education = '高中';
                break;
            case '3':
                $education = '大专';
                break;
            case '4':
                $education = '本科';
                break;
            case '5':
                $education = '研究生';
                break;
            case '6':
                $education = '博士';
                break;
            default:
                $education = '本科';
                break;
        }
        $remarks = $info['remarks'];
        //姓名
        $text_array[0]['location'] = '264,248';
        $text_array[0]['text'] = $name;
        $text_array[0]['font_size'] = 40;
        $text_array[0]['font_color'] = '#202020';
        $text_array[1]['location'] = '264,308';
        $text_array[1]['text'] = '觉得不错欢迎聊聊,请联系我';
        $text_array[1]['font_size'] = 32;
        $text_array[1]['font_color'] = '#909090';
        //基础信息
        $text_array[2]['location'] ='72,432';
        $text_array[2]['text'] = '年份';
        $text_array[2]['font_size'] = 32;
        $text_array[2]['font_color'] = '#606060';
        $text_array[3]['location'] = '160,436';
        $text_array[3]['text'] =  $year;
        $text_array[3]['font_size'] = 40;
        $text_array[3]['font_color'] = '#202020';

        $text_array[4]['location'] ='374,432';
        $text_array[4]['text'] = '性别';
        $text_array[4]['font_size'] = 32;
        $text_array[4]['font_color'] = '#606060';
        $text_array[5]['location'] ='462,432';
        $text_array[5]['text'] = $sex;
        $text_array[5]['font_size'] = 40;
        $text_array[5]['font_color'] = '#202020';

        $text_array[6]['location'] ='72,512';
        $text_array[6]['text'] = '地区';
        $text_array[6]['font_size'] = 32;
        $text_array[6]['font_color'] = '#606060';
        $text_array[7]['location'] ='160,512';
        $text_array[7]['text'] = $residence;
        $text_array[7]['font_size'] = 40;
        $text_array[7]['font_color'] = '#202020';

        $text_array[8]['location'] ='374,512';
        $text_array[8]['text'] = '身高';
        $text_array[8]['font_size'] = 32;
        $text_array[8]['font_color'] = '#606060';
        $text_array[9]['location'] ='462,516';
        $text_array[9]['text'] = $height;
        $text_array[9]['font_size'] = 40;
        $text_array[9]['font_color'] = '#202020';

        $text_array[10]['location'] ='72,592';
        $text_array[10]['text'] = '学历';
        $text_array[10]['font_size'] = 32;
        $text_array[10]['font_color'] = '#606060';
        $text_array[11]['location'] ='160,592';
        $text_array[11]['text'] = $education;
        $text_array[11]['font_size'] = 40;
        $text_array[11]['font_color'] = '#202020';

        $text_array[12]['location'] ='374,592';
        $text_array[12]['text'] = '职业';
        $text_array[12]['font_size'] = 32;
        $text_array[12]['font_color'] = '#606060';
        $text_array[13]['location'] ='462,592';
        $text_array[13]['text'] = $work;
        $text_array[13]['font_size'] = 40;
        $text_array[13]['font_color'] = '#202020';
        //说明
        if(!empty($remarks)){
            $str = str_replace(array("/r/n", "/r", "/n"), '', $remarks);
            $remarks = preg_replace('# #', '', $str);
            $text1 = mb_substr($remarks, 0, 16);
            $text_array[14]['location'] ='72,720';
            $text_array[14]['text'] = $text1;
            $text_array[14]['font_size'] = 36;
            $text_array[14]['font_color'] = '#606060';
            if (mb_strlen($remarks) > 16) {
                $text2 = mb_substr($remarks, 16, 16);
                $text_array[16]['location'] ='72,780';
                $text_array[16]['text'] = $text2;
                $text_array[16]['font_size'] = 36;
                $text_array[16]['font_color'] = '#606060';
                if (mb_strlen($remarks) > 32) {
                    $text3 = mb_substr($remarks, 32, 16);
                    $text_array[15]['location'] ='72,840';
                    $text_array[15]['text'] = $text3;
                    $text_array[15]['font_size'] = 36;
                    $text_array[15]['font_color'] = '#606060';
                    if(mb_strlen($remarks) > 48){
                        $text4 = '...';
                        $text_array[17]['location'] ='72,870';
                        $text_array[17]['text'] = $text4;
                        $text_array[17]['font_size'] = 36;
                        $text_array[17]['font_color'] = '#606060';
                    }
                }

            }
        }
        $rand=range(0,9);
        shuffle($rand);
        $randId = $rand[0].$rand[1].$rand[2];
        $num = $randId.$info['uid'];
        $text_array[18]['location'] ='10,27';
        $text_array[18]['text'] = '#'.$num;
        $text_array[18]['font_size'] = 36;
        $text_array[18]['font_color'] = '#ffffff';

        $posterModel = new PosterModel();
        $local_path = $posterModel->creates($uid,$share_back_path,$images,$text_array);
        $upload = new Upload();
        $img_url_data = $upload->index($local_path);//获取七牛图片
        $img_url_data = json_decode($img_url_data, 1);
        if ($img_url_data['code'] == 200) {
            unlink($local_path);
            $save['share_poster'] = $img_url_data['img'];
            Db::name('userinfo')->where(['id'=>$uid])->update($save);
            cache('getposter-'.$uid,$img_url_data['img']);
            echo '<img src="' . $img_url_data['img'] . '"  width="750px" height="1200px" alt="">';
            die;
        } else {
            unlink($local_path);
            echo 'error!';die;
        }
    }

    /**
     * @Notes: 浏览记录列表
     * @Interface infoList
     * @author: zy
     * @Time: 2021/08/23
     */
    public function infoList(){
        $this->title = '查看列表';
        $uid = input('uid');
        $bid = input('bid');
        $ckCount = 0;
        $bckCount = 0;
        if($uid){
            $ckCount = DB::name("view_info_record")->where(['uid'=>$uid])->group('bid')->count();
        }
        if($bid){
            $bckCount = DB::name("view_info_record")->where(['bid'=>$bid])->group('uid')->count();
        }
        $this->ckCount = $ckCount;
        $this->bckCount = $bckCount;
        $query = $this->_query($this->table3);
        $query->equal("uid,bid")->dateBetween('create_time')->order('create_time desc')->page();
    }
    protected function _infoList_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $field = 'sex,year,weight_score,province';
            $uinfo = DB::name("children")->field($field)->where(['uid'=>$vo['uid']])->find();
            $tinfo = DB::name("children")->field($field)->where(['uid'=>$vo['bid']])->find();
            $u_nickname = DB::name("userinfo")->where(['id'=>$vo['uid']])->value('nickname');
            $t_nickname = DB::name("userinfo")->where(['id'=>$vo['bid']])->value('nickname');
            //接受方
            $vo['u_nickname'] = emoji_decode($u_nickname);
            $vo['u_sex'] = $uinfo['sex'];
            $vo['u_age'] = getage($uinfo['year']);
            $vo['u_weight_score'] = $uinfo['weight_score'];
            //推荐方
            $vo['t_nickname'] = emoji_decode($t_nickname);
            $vo['t_sex'] = $tinfo['sex'];
            $vo['t_age'] = getage($tinfo['year']);
            $vo['t_weight_score'] = $tinfo['weight_score'];

        }
    }
    /**
     * @Notes: 被查看记录列表
     * @Interface infoList
     * @author: zy
     * @Time: 2021/08/23
     */
    public function looktelList(){
        $this->title = '被查看记录列表';
        $query = $this->_query('tel_collection');
        $query->equal("uid,bid")->timeBetween('create_at')->where(['status'=>1])->order('create_at desc')->page();
    }
    protected function _looktelList_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['create_at'] = date('Y-m-d H:i:s',$vo['create_at']);
            $field = 'sex,year,weight_score,province';
            $uinfo = DB::name("children")->field($field)->where(['uid'=>$vo['uid']])->find();
            $tinfo = DB::name("children")->field($field)->where(['uid'=>$vo['bid']])->find();
            $u_nickname = DB::name("userinfo")->where(['id'=>$vo['uid']])->value('nickname');
            $t_nickname = DB::name("userinfo")->where(['id'=>$vo['bid']])->value('nickname');
            //查看方
            $vo['u_nickname'] = emoji_decode($u_nickname);
            $vo['u_sex'] = $uinfo['sex'];
            $vo['u_age'] = getage($uinfo['year']);
            //被查看方
            $vo['t_nickname'] = emoji_decode($t_nickname);
            $vo['t_sex'] = $tinfo['sex'];
            $vo['t_age'] = getage($tinfo['year']);
        }
    }

}
