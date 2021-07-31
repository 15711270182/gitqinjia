<?php
/**
 * User: tao
 * Date: 2021/7/16 13:53
 */


namespace app\admin\controller;

use library\Controller;
use library\tools\Data;
use think\Db;
use app\index\model\NewRecommend;
use app\index\model\Poster;
use app\index\controller\Index;
use app\index\controller\CreateShareImg;

class Algorithm extends Controller
{
    public $table = 'children';
    public function index()
    {
        $uid = 803;
        cache('shareposter-'.$uid, null);
        exit();
//        $index = new Index();
//        $re = $index->shareInfo();
//        var_dump($re);


        exit;
        $list = array();
        $uid = $this->request->get('uid');
        if ($uid){
            $user_info = Db::table($this->table)->where('uid', $uid)->find();
            $RECOMMEND = new NewRecommend();
            $recommend_list = $RECOMMEND->getRecommend($uid);

            array_unshift($list, $user_info);
            foreach ($recommend_list as $key => $value){
                $children = Db::table('children')->where('uid', $value['uid'])->find();
                array_push($list, $children);
            }

            foreach ($list as $key => $value){
                $parents = Db::table('userinfo')->where('id', $value['uid'])->find();
                $list[$key]['nickname'] = $parents['nickname'];
                $list[$key]['sex'] = $value['sex']==2?'女':'男';
                $list[$key]['education'] = $this->getEducation($value['education']);
                $list[$key]['expect_education'] = $this->getEducation($value['expect_education']);

                if ($value['min_age'] == 0) $value['min_age'] = '未填写';
                if ($value['min_age'] == 999) $value['min_age'] = '不限';
                if ($value['max_age'] == 0) $value['max_age'] = '未填写';
                if ($value['max_age'] == 999) $value['max_age'] = '不限';

                if ($value['min_height'] == 0) $value['min_height'] = '未填写';
                if ($value['min_height'] == 999) $value['min_height'] = '不限';
                if ($value['max_height'] == 0) $value['max_height'] = '未填写';
                if ($value['max_height'] == 999) $value['max_height'] = '不限';
                $list[$key]['expect_age'] = $value['min_age'].'~'.$value['max_age'];
                $list[$key]['expect_height'] = $value['min_height'].'~'.$value['max_height'];
            }
        }
        $this->assign('list', $list);
        $this->assign('title', '推荐算法测试');
        return $this->fetch();
    }

    public function getEducation($val)
    {
        $education = '';
        switch ($val) {
            case '1':
                $education = '中专及以下';
                break;
            case '2':
                $education ='高中';
                break;
            case '3':
                $education ='大专';
                break;
            case '4':
                $education ='本科';
                break;
            case '5':
                $education ='研究生';
                break;
            case '6':
                $education ='博士';
                break;
            default:
                $education = '不限';
                break;
        }
        return $education;
    }
}