<?php
/**
 *推荐模型
 * @authors wzs
 * @date    2018-03-14
 */
namespace app\index\model;

use think\Model;
use Endroid\QrCode\QrCode;
use library\Controller;
use think\Db;
use library\service\AdminService;

class Recommend extends Model
{
    
    private static $aes_key;
    public function __construct()
    {
        $this::$aes_key = '5b9c2ed3e19c40e5';
    }


    /**
     *获取明日推荐
     * wzs
     *uid：用户id  num：当前推荐次数
     */
    public function gettomorrow($uid)
    {
        $map = array();
        $map['uid'] = $uid;
        //查询用户详情
        $child = db::name('children')->where($map)->find();
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = date('Ymd');
        $tomorrow = db::name('tomorrow_recommend')->where($map)->select();
        if ($tomorrow) 
        {
            return $tomorrow;
        }
        //近三天推荐
        $three = date('Ymd',strtotime('-3 days'));
        $map = array();
        $map['uid'] = $uid; 
        $three_list = db::name('recommend_record')->where($map)->where('addtime','>=',$three)->select();
        //取出我收藏的
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $collection = db::name('collection')->where($map)->field('bid')->select();
        //取出联系人
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $tel = db::name('tel_collection')->where($map)->field('bid')->select();
        $str = $uid;
        if ($three_list) 
        {
            foreach ($three_list as $key => $value) 
            {
                $str = $str.','.$value['recommendid'];
            }
        }
        if ($collection)
        {
            foreach ($collection as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }
        
        if ($tel) 
        {
            foreach ($tel as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }
        // dump($str);exit;

        $connection = '';

        $db = Db::name('children');
        $map = array();
        $map['is_del'] = 1;
        $map['sex'] = $child['sex']==1?2:1;
        $temp = $child["residence"]; 
        $map['residence'] = "$temp";
        $db->where($map);
        $max_year = $child['year']+5;
        $min_year = $child['year']-5;
        $db->where('year','between',"$min_year,$max_year");
        if ($str)
        {
            $db->where('id','notin',explode(",", $str));
        }

        $tomorrow = $db->limit(2)->order('today_score desc')->select();
        if ($tomorrow) 
        {
            foreach ($tomorrow as $key => $value) 
            {
                $str = $str.','.$value['uid'];
            }
        }
        $tomorrow1 = array();
        if (count($tomorrow) < 2) 
        {
            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            $map['sex'] = $child['sex']==1?2:1;
            $db->where($map);
            $max_year = $child['year']+20;
            $min_year = $child['year']-20;
            $db->where('year','between',"$min_year,$max_year");
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }

            $tomorrow1 = $db->limit(2-count($tomorrow))->order('today_score desc')->select();
        }
        if ($tomorrow1) 
        {
            $tomorrow = array_merge($tomorrow1,$tomorrow);
        }
        
        foreach ($tomorrow as $key => $value) 
        {
            $data = array();
            $data['uid'] = $uid;
            $data['is_match'] = 1;
            $data['recommendid'] = $value['uid'];
            $data['date'] = date('Ymd');
            $data['addtime'] = time();
            db::name('tomorrow_recommend')->insert($data);
            $tomorrow[$key]['recommendid'] = $value['uid'];
        }
        return $tomorrow;
    }

    
    /**
     *获取推荐列表
     * wzs
     *uid：用户id  num：当前推荐次数
     */
    public function getrecommend($uid,$num)
    {
        // dump(1);exit;
        //查询改用户今日是否有推荐
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = date('Ymd');
        $is_recommend = db::name('recommend_record')->where($map)->select();
        
        if ($is_recommend) 
        {
            $list = array();
            foreach ($is_recommend as $key => $value) 
            {
                $map = array();
                $map['uid'] = $value['recommendid'];
                $map['status'] = 1;
                $list[$key] = db::name('children')->where($map)->find();
                $list[$key]['is_match'] = $value['is_match'];
             }
             return $list;exit;
        }
        $map = array();
        $map['uid'] = $uid;
        //查询用户详情
        $child = db::name('children')->where($map)->find();
        // dump($child);exit;
        // dump($child);exit;
        //取出昨日推荐的放到今日推荐里面
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = date('Ymd',strtotime('-1 days'));
        $tomorrow = db::name('tomorrow_recommend')->where($map)->select();

        //step1 取出最近和用户发生联系的人的账号 不予推荐
        //近三天推荐
        $three = date('Ymd',strtotime('-3 days'));
        $map = array();
        $map['uid'] = $uid; 
        $three_list = db::name('recommend_record')->where($map)->where('addtime','>=',$three)->select();
        //取出我收藏的
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $collection = db::name('collection')->where($map)->field('bid')->select();
        //取出联系人
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $tel = db::name('tel_collection')->where($map)->field('bid')->select();
        $str = $child['uid'];
        if ($three_list) 
        {
            foreach ($three_list as $key => $value) 
            {
                $str = $str.','.$value['recommendid'];
            }
        }
        if ($tomorrow) 
        {
            foreach ($tomorrow as $key => $value) 
            {
                $str = $str.','.$value['recommendid'];
            } 
        }
        if ($collection)
        {
            foreach ($collection as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }
        
        if ($tel) 
        {
            foreach ($tel as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }

        $connection = '';
        $mate = array();//匹配
        $high = array();//高分
        $plunk = array();//分低的扶持
        //step2 看用户是否有要求
        if ($child['expect_education'] || $child['min_age'] || $child['max_age'] || $child['min_height'] || $child['max_height']) 
        {
            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            // dump($str);exit;
            
            $map['sex'] = $child['sex']==1?2:1;
            $temp = $child["residence"]; 
            $map['residence'] = "$temp";
            $map['status'] = 1;

            if ($child['expect_education']) 
            {
                $map['education'] = $child['expect_education'];
            }
            $db->where($map);
            //年龄
            if ($child['min_age'] || $child['max_age']) 
            {
                if ($child['max_age'] == 0) 
                {
                    $child['max_age'] = 80;
                }
                if ($child['min_age'] == 999) 
                {
                    $child['min_age'] = 0;
                }
                $max_year = $this->age2year($child['min_age']);
                $min_year = $this->age2year($child['max_age']);
                $db->where('year','between',"$min_year,$max_year");

            }
            if ($child['min_height'] || $child['max_height']) 
            {
                if ($child['max_height'] == 0) 
                {
                    $child['max_height'] = 250;
                }
                if ($child['min_height'] == 999) 
                {
                    $child['min_height'] = 0;
                }
                $min_height =$child['min_height'];
                $max_height =$child['max_height'];
                $db->where('height','between',"$min_height,$max_height");

            }
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }
            $mate = $db->limit(5)->order('today_score desc')->select();
        }
        foreach ($mate as $key => $value) 
        {
            $str = $str.','.$value['uid'];
            $mate[$key]['is_match']  = 2;
        }
        

        //取s剩余的条数不上
        $last_num = $num-count($mate)-count($tomorrow);
        $tomorrow_arr = array();
        // dump($tomorrow);
        foreach ($tomorrow as $key => $value) 
        {
            $map = array();
            $map['uid'] = $value['recommendid'];
            $map['status'] = 1;
            $tomorrow_arr[$key] = db::name('children')->where($map)->find();
            $tomorrow_arr[$key]['is_match'] = $value['is_match'];
        }
        // dump($tomorrow_arr);exit;

        $db = Db::name('children');
        $map = array();
        $map['is_del'] = 1;
        $map['status'] = 1;
        $map['sex'] = $child['sex']==1?2:1;
        $map['education'] = $child['education'];
        $temp = $child["residence"]; 
        $map['residence'] = "$temp";
        $db->where($map);
        $max_year = $child['year']+10;
        $min_year = $child['year']-10;
        $db->where('year','between',"$min_year,$max_year");
        if ($str)
        {
            $db->where('id','notin',explode(",", $str));
        }

        $high = $db->limit($last_num)->order('today_score desc')->select();
        // dump($high);exit;
        foreach ($high as $key => $value) 
        {
            $high[$key]['is_match'] = 1;
        }
        // dump($high);exit;
        $list  = array();
        $list = array_merge($mate,$high);
        // dump($list);exit;

        $list  = array_merge($list,$tomorrow_arr);
        foreach ($list as $key => $value) 
        {
            $str = $str.','.$value['uid'];
        }
        shuffle($list);
        if (count($list) < 15) 
        {   
            $last_num = 15 - count($list) ;
            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            $map['status'] = 1;
            $map['sex'] = $child['sex']==1?2:1;
            // $map['education'] = $child['education'];
            $db->where($map);
            $max_year = $child['year']+20;
            $min_year = $child['year']-20;
            $db->where('year','between',"$min_year,$max_year");
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }

            $bc = $db->limit($last_num)->order('today_score desc')->select();
            foreach ($bc as $key => $value) 
            {
                $bc[$key]['is_match'] = 1;
            }
            $list  = array_merge($list,$bc);
        }
        // dump($list);exit;

        

        //打乱顺序

        foreach ($list as $key => $value) 
        {
            // dump($value['uid']);
            //今天分数-1
            $map = array();
            $map['uid'] = $value['uid'];
            db::name('children')->where($map)->setDec('today_score',5);
            $data = array();
            $data['uid'] = $uid;
            $data['recommendid'] = $value['uid'];
            $data['date'] = date('Ymd');
            $data['addtime'] = time();
            $data['is_match'] = $value['is_match'];
            db::name('recommend_record')->insert($data);
        }
        // exit;
        return $list;

        
    }


    /**
     *获取推荐列表
     * wzs
     *uid：用户id  num：当前推荐次数
     */
    public function getrecommendnew($uid,$date,$num)
    {
        // dump(1);exit;
        //查询改用户今日是否有推荐
        $map = array();
        $map['uid'] = $uid;
        $map['date'] = $date;
        // dump($map);
        $is_recommend = db::name('recommend_record')->where($map)->select();
        // dump($is_recommend);exit;
        
        if ($is_recommend) 
        {
            $list = array();
            foreach ($is_recommend as $key => $value) 
            {
                $map = array();
                $map['uid'] = $value['recommendid'];
                $list[$key] = db::name('children')->where($map)->find();
                $list[$key]['is_match'] = $value['is_match'];
             }
             return $list;exit;
        }
        $map = array();
        $map['uid'] = $uid;
        //查询用户详情
        $child = db::name('children')->where($map)->find();

        //step1 取出最近和用户发生联系的人的账号 不予推荐
        //近三天推荐
        $three = date('Ymd',strtotime('-3 days'));
        $map = array();
        $map['uid'] = $uid; 
        $three_list = db::name('recommend_record')->where($map)->where('addtime','>=',$three)->select();
        //取出我收藏的
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $collection = db::name('collection')->where($map)->field('bid')->select();
        //取出联系人
        $map = array();
        $map['uid'] = $uid;
        $map['is_del'] = 1;
        $tel = db::name('tel_collection')->where($map)->field('bid')->select();
        $str = $child['uid'];
        if ($three_list) 
        {
            foreach ($three_list as $key => $value) 
            {
                $str = $str.','.$value['recommendid'];
            }
        }
        if ($collection)
        {
            foreach ($collection as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }
        
        if ($tel) 
        {
            foreach ($tel as $key => $value) 
            {
                $str = $str.','.$value['bid'];
            }
        }

        $connection = '';
        $mate = array();//匹配
        $high = array();//高分
        $plunk = array();//分低的扶持
        //step2 看用户是否有要求
        if ($child['expect_education'] || $child['min_age'] || $child['max_age'] || $child['min_height'] || $child['max_height']) 
        {
            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            // dump($str);exit;
            
            $map['sex'] = $child['sex']==1?2:1;
            $temp = $child["residence"]; 
            $map['residence'] = "$temp";
            $map['status'] = 1;

            if ($child['expect_education']) 
            {
                $map['education'] = $child['expect_education'];
            }
            $db->where($map);
            //年龄
            if ($child['min_age'] || $child['max_age']) 
            {
                if ($child['max_age'] == 0) 
                {
                    $child['max_age'] = 80;
                }
                if ($child['min_age'] == 999) 
                {
                    $child['min_age'] = 0;
                }
                $max_year = $this->age2year($child['min_age']);
                $min_year = $this->age2year($child['max_age']);
                $db->where('year','between',"$min_year,$max_year");

            }
            if ($child['min_height'] || $child['max_height']) 
            {
                if ($child['max_height'] == 0) 
                {
                    $child['max_height'] = 250;
                }
                if ($child['min_height'] == 999) 
                {
                    $child['min_height'] = 0;
                }
                $min_height =$child['min_height'];
                $max_height =$child['max_height'];
                $db->where('height','between',"$min_height,$max_height");

            }
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }
            $mate = $db->limit(7)->order('today_score desc')->select();
        }
        foreach ($mate as $key => $value) 
        {
            $str = $str.','.$value['uid'];
            $mate[$key]['is_match']  = 2;
        }
        

        //取s剩余的条数不上
        $last_num = $num-count($mate);
   
        // dump($tomorrow_arr);exit;

        $db = Db::name('children');
        $map = array();
        $map['is_del'] = 1;
        $map['sex'] = $child['sex']==1?2:1;
        $map['education'] = $child['education'];
        $temp = $child["residence"]; 
        $map['residence'] = "$temp";
        $map['status'] = 1;
        $db->where($map);
        // $max_year = $child['year']+10;
        // $min_year = $child['year']-10;
        // $db->where('year','between',"$min_year,$max_year");
        if ($str)
        {
            $db->where('id','notin',explode(",", $str));
        }

        $high = $db->limit($last_num)->order('today_score desc')->select();
        // dump($high);exit;
        foreach ($high as $key => $value) 
        {
            $high[$key]['is_match'] = 1;
        }
        // dump($high);exit;
        $list  = array();
        $list = array_merge($mate,$high);
        // dump($list);exit;

        // $list  = array_merge($list,$tomorrow_arr);
        foreach ($list as $key => $value) 
        {
            $str = $str.','.$value['uid'];
        }
        shuffle($list);
        //如果高分和匹配我的没满足次数 则随机补齐
        if (count($list) < $num) 
        {   
            $last_num = $num - count($list) ;
            $db = Db::name('children');
            $map = array();
            $map['is_del'] = 1;
            $map['sex'] = $child['sex']==1?2:1;
            // $map['education'] = $child['education'];
            $map['status'] = 1;
            $db->where($map);
            $max_year = $child['year']+20;
            $min_year = $child['year']-20;
            $db->where('year','between',"$min_year,$max_year");
            if ($str)
            {
                $db->where('id','notin',explode(",", $str));
            }
            $bc = $db->limit($last_num)->order('today_score desc')->select();
            foreach ($bc as $key => $value) 
            {
                $bc[$key]['is_match'] = 1;
            }
            $list  = array_merge($list,$bc);
        }
        //打乱顺序
        shuffle($list);
        //记录并扣分
        foreach ($list as $key => $value) 
        {
            // dump($value['uid']);
            //今天分数-1
            $map = array();
            $map['uid'] = $value['uid'];
            db::name('children')->where($map)->setDec('today_score',5);
            $data = array();
            $data['uid'] = $uid;
            $data['recommendid'] = $value['uid'];
            $data['date'] = $date;
            $data['addtime'] = time();
            $data['is_match'] = $value['is_match'];
            db::name('recommend_record')->insert($data);
        }
        return $list;

        
    }


    /**
     *出生年月转化
     * wzs
     */
    public function age2year($age)
    {
        $year = date('Y') -$age;
        return $year;

    }




}