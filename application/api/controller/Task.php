<?php


namespace app\api\controller;

use think\Db;
use app\api\controller\Base;
/**
 * Class Task
 */
class Task extends Base
{
    /**
     * @Notes:日推荐数 数据统计
     * @Interface day
     * @author: zy
     * @Time: 2021/4/20   11:55
     */
    public function tjday(){
        $date = date('Y-m-d',strtotime('-1 days'));
        $start = strtotime($date.'000000');
        $end = strtotime($date.'235959');
        $find = DB::name("statistical_report")->where(['date'=>$date])->find();
        if(!empty($find)){
             echo 'ok';die;
        }
        $rdate = date('Ymd',strtotime($date));
        $rcount = Db::table('recommend_record')->where(['date'=>$rdate])->group('recommendid')->count();
        $tcount = $this->getTelList($start,$end);
        $data = [
            'rcount'=>$rcount,
            'tcount'=>count($tcount),
            'date'=>$date,
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $res = DB::table('statistical_report')->insertGetId($data);
        if($res){
             echo 'ok';die;
        }
        echo 'error';die;
    }

    public function getTelList($start, $end)
    {
        return Db::table('tel_collection')->where('create_at', 'between',[$start, $end])->where(['status'=>1,'type'=>1])->order('create_at desc')->select();
//
//        return DB::name("tel_collection")->alias('t')->join('tel_collection telB','t.uid = telB.bid and t.bid = telB.uid')
//            ->where('t.create_at', 'between',[$start, $end])->where(['t.status'=>1,'telB.status'=>1])->order('t.create_at desc')->select();
    }
}