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
        $this->bid = input('bid');
        $bid = input('bid');
        $age_min = input('age_min','');
        $age_max = input('age_max','');
        $this->age_min    = $age_min;
        $this->age_max    = $age_max;
        $where = "u.headimgurl <> ''";
        if(!empty($bid)){
            $uid = DB::name('relation')->where(['bid'=>$bid])->column('uid');
            if(!empty($uid)){
                $uid = implode(',',$uid);
                //var_dump($uid);die;
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
        $field = "u.id,u.nickname,u.headimgurl,u.is_vip,u.add_time,u.count,u.status,c.id as cid,c.phone,c.sex as xingbie,c.is_ban,c.year,c.province,c.residence,c.team_status";
        $this->_query($this->table)
                ->alias('u')
                ->field($field)
                ->join('children c', 'u.id = c.uid')
                ->equal('u.id#id,u.nickname#nickname,u.is_vip#is_vip,c.phone#phone,c.sex#sex,u.status#status')
                ->timeBetween('c.create_at#create_at')
                ->where($where)
                ->order('u.id desc')->page();
//        var_dump(DB::name('userinfo')->getLastSql());die;
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
        $res = DB::name('children')->where(['id'=>$id])->update($params);
        if ($res){
            $uid = DB::name('children')->where(['id'=>$id])->value('uid');
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
        $back_shi = './share/shareshi1.png';
        $back_zhen = './uploads/backgroud/zhen.jpg';
        $header1 = [];
        $header1['path'] = $head_img_path;
        $header1['size'] = 86;
        $header1['locate'] = [36,95];
        $header1['xPos'] = 'left';
        $header2 = [];
        $header2['path'] = $back_shi;
        $header2['size'] = 38;
        $header2['locate'] = [84,142];
        $header2['xPos'] = 'left';
        $header2['yPos'] = 'top';
        $header3 = [];
        $header3['path'] = $back_zhen;
        $header3['size'] = 22;
        $header3['locate'] = [196,105];
        $header3['xPos'] = 'left';
        $local_path =  (new Qrcode())->generateQrCode($path, $sid, $page_path);
        $qrcode['path'] = $local_path;
        $qrcode['size'] = 90;
        $qrcode['locate'] = [249,476];
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
        $text_array[0]['location'] = '132,124';
        $text_array[0]['text'] = $name;
        $text_array[0]['font_size'] = 20;
        $text_array[0]['font_color'] = '#202020';
        $text_array[1]['location'] = '132,154';
        $text_array[1]['text'] = '觉得不错欢迎聊聊,请联系我';
        $text_array[1]['font_size'] = 16;
        $text_array[1]['font_color'] = '#909090';
        //基础信息
        $text_array[2]['location'] ='36,216';
        $text_array[2]['text'] = '年份';
        $text_array[2]['font_size'] = 16;
        $text_array[2]['font_color'] = '#606060';
        $text_array[3]['location'] = '80,218';
        $text_array[3]['text'] =  $year;
        $text_array[3]['font_size'] = 20;
        $text_array[3]['font_color'] = '#202020';

        $text_array[4]['location'] ='187,216';
        $text_array[4]['text'] = '性别';
        $text_array[4]['font_size'] = 16;
        $text_array[4]['font_color'] = '#606060';
        $text_array[5]['location'] ='231,216';
        $text_array[5]['text'] = $sex;
        $text_array[5]['font_size'] = 20;
        $text_array[5]['font_color'] = '#202020';

        $text_array[6]['location'] ='36,256';
        $text_array[6]['text'] = '地区';
        $text_array[6]['font_size'] = 16;
        $text_array[6]['font_color'] = '#606060';
        $text_array[7]['location'] ='80,256';
        $text_array[7]['text'] = $residence;
        $text_array[7]['font_size'] = 20;
        $text_array[7]['font_color'] = '#202020';

        $text_array[8]['location'] ='187,256';
        $text_array[8]['text'] = '身高';
        $text_array[8]['font_size'] = 16;
        $text_array[8]['font_color'] = '#606060';
        $text_array[9]['location'] ='231,258';
        $text_array[9]['text'] = $height;
        $text_array[9]['font_size'] = 20;
        $text_array[9]['font_color'] = '#202020';

        $text_array[10]['location'] ='36,296';
        $text_array[10]['text'] = '学历';
        $text_array[10]['font_size'] = 16;
        $text_array[10]['font_color'] = '#606060';
        $text_array[11]['location'] ='80,296';
        $text_array[11]['text'] = $education;
        $text_array[11]['font_size'] = 20;
        $text_array[11]['font_color'] = '#202020';

        $text_array[12]['location'] ='187,296';
        $text_array[12]['text'] = '职业';
        $text_array[12]['font_size'] = 16;
        $text_array[12]['font_color'] = '#606060';
        $text_array[13]['location'] ='231,296';
        $text_array[13]['text'] = $work;
        $text_array[13]['font_size'] = 20;
        $text_array[13]['font_color'] = '#202020';
        //说明
        $text1 = mb_substr($remarks, 0, 16);
        $text_array[14]['location'] ='36,360';
        $text_array[14]['text'] = $text1;
        $text_array[14]['font_size'] = 18;
        $text_array[14]['font_color'] = '#606060';
        if (mb_strlen($remarks) > 16) {
            $text2 = mb_substr($remarks, 16, 16);
            $text_array[16]['location'] ='36,390';
            $text_array[16]['text'] = $text2;
            $text_array[16]['font_size'] = 18;
            $text_array[16]['font_color'] = '#606060';
            if (mb_strlen($remarks) > 32) {
                $text3 = mb_substr($remarks, 32, 16);
                $text_array[15]['location'] ='36,420';
                $text_array[15]['text'] = $text3;
                $text_array[15]['font_size'] = 18;
                $text_array[15]['font_color'] = '#606060';
                if(mb_strlen($remarks) > 48){
                    $text4 = '...';
                    $text_array[17]['location'] ='36,435';
                    $text_array[17]['text'] = $text4;
                    $text_array[17]['font_size'] = 18;
                    $text_array[17]['font_color'] = '#606060';
                }
            }
            
        }
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
    public function test(){
        $uid = '1001';
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
//        $head_name = 'img_poster'.$uid.'.png';
//        $head_img_path = $path.'/'.$head_name;
//        if (!file_exists($head_img_path)){
//            $head_path = $poster->getImage($data['headimgurl'], $path, $head_name);
//            $head_img_path = $head_path['save_path'];
//        }
        $head_img_path = localWeixinAvatar($data['headimgurl'],$path,$uid,132);
        $head_img_path = $poster->ssimg1($path.'/', $head_img_path, 80, 80);
        $sid = $uid;
        $path = './uploads/qrcode/';
        $page_path = 'pages/details/details';
        $share_back_path = './uploads/backgroud/bj.png';
        $back_shi = './share/shareshi1.png';
        $back_zhen = './uploads/backgroud/zhen.jpg';
        $header1 = [];
        $header1['path'] = $head_img_path;
        $header1['size'] = 86;
        $header1['locate'] = [36,95];
        $header1['xPos'] = 'left';
        $header2 = [];
        $header2['path'] = $back_shi;
        $header2['size'] = 38;
        $header2['locate'] = [84,142];
        $header2['xPos'] = 'left';
        $header2['yPos'] = 'top';
        $header3 = [];
        $header3['path'] = $back_zhen;
        $header3['size'] = 22;
        $header3['locate'] = [196,105];
        $header3['xPos'] = 'left';
        $local_path =  (new Qrcode())->generateQrCode($path, $sid, $page_path);
        $qrcode['path'] = $local_path;
        $qrcode['size'] = 90;
        $qrcode['locate'] = [249,476];
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
        $text_array[0]['location'] = '132,124';
        $text_array[0]['text'] = $name;
        $text_array[0]['font_size'] = 20;
        $text_array[0]['font_color'] = '#202020';
        $text_array[1]['location'] = '132,154';
        $text_array[1]['text'] = '觉得不错欢迎聊聊,请联系我';
        $text_array[1]['font_size'] = 16;
        $text_array[1]['font_color'] = '#909090';
        //基础信息
        $text_array[2]['location'] ='36,216';
        $text_array[2]['text'] = '年份';
        $text_array[2]['font_size'] = 16;
        $text_array[2]['font_color'] = '#606060';
        $text_array[3]['location'] = '80,218';
        $text_array[3]['text'] =  $year;
        $text_array[3]['font_size'] = 20;
        $text_array[3]['font_color'] = '#202020';

        $text_array[4]['location'] ='187,216';
        $text_array[4]['text'] = '性别';
        $text_array[4]['font_size'] = 16;
        $text_array[4]['font_color'] = '#606060';
        $text_array[5]['location'] ='231,216';
        $text_array[5]['text'] = $sex;
        $text_array[5]['font_size'] = 20;
        $text_array[5]['font_color'] = '#202020';

        $text_array[6]['location'] ='36,256';
        $text_array[6]['text'] = '地区';
        $text_array[6]['font_size'] = 16;
        $text_array[6]['font_color'] = '#606060';
        $text_array[7]['location'] ='80,256';
        $text_array[7]['text'] = $residence;
        $text_array[7]['font_size'] = 20;
        $text_array[7]['font_color'] = '#202020';

        $text_array[8]['location'] ='187,256';
        $text_array[8]['text'] = '身高';
        $text_array[8]['font_size'] = 16;
        $text_array[8]['font_color'] = '#606060';
        $text_array[9]['location'] ='231,258';
        $text_array[9]['text'] = $height;
        $text_array[9]['font_size'] = 20;
        $text_array[9]['font_color'] = '#202020';

        $text_array[10]['location'] ='36,296';
        $text_array[10]['text'] = '学历';
        $text_array[10]['font_size'] = 16;
        $text_array[10]['font_color'] = '#606060';
        $text_array[11]['location'] ='80,296';
        $text_array[11]['text'] = $education;
        $text_array[11]['font_size'] = 20;
        $text_array[11]['font_color'] = '#202020';

        $text_array[12]['location'] ='187,296';
        $text_array[12]['text'] = '职业';
        $text_array[12]['font_size'] = 16;
        $text_array[12]['font_color'] = '#606060';
        $text_array[13]['location'] ='231,296';
        $text_array[13]['text'] = $work;
        $text_array[13]['font_size'] = 20;
        $text_array[13]['font_color'] = '#202020';
        //说明
        $text1 = mb_substr($remarks, 0, 16);
        $text_array[14]['location'] ='36,360';
        $text_array[14]['text'] = $text1;
        $text_array[14]['font_size'] = 18;
        $text_array[14]['font_color'] = '#606060';
        if (mb_strlen($remarks) > 16) {
            $text2 = mb_substr($remarks, 16, 16);
            $text_array[16]['location'] ='36,390';
            $text_array[16]['text'] = $text2;
            $text_array[16]['font_size'] = 18;
            $text_array[16]['font_color'] = '#606060';
            if (mb_strlen($remarks) > 32) {
                $text3 = mb_substr($remarks, 32, 16);
                $text_array[15]['location'] ='36,420';
                $text_array[15]['text'] = $text3;
                $text_array[15]['font_size'] = 18;
                $text_array[15]['font_color'] = '#606060';
                if(mb_strlen($remarks) > 48){
                    $text4 = '...';
                    $text_array[17]['location'] ='36,435';
                    $text_array[17]['text'] = $text4;
                    $text_array[17]['font_size'] = 18;
                    $text_array[17]['font_color'] = '#606060';
                }
            }

        }
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
}
