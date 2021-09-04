<?php


namespace app\api\controller;

use think\Db;
use app\api\controller\Base;
use app\api\service\Send as SendService;
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
        $start_time = date('Y-m-d 00:00:00',strtotime($date));
        $end_time = date('Y-m-d 23:59:59',strtotime($date));
        $browse_num = Db::table('view_info_record')->where('create_time', 'between',[$start_time, $end_time])->group('uid')->count(); //今日浏览人数
        $browsed_num = Db::table('view_info_record')->where('create_time', 'between',[$start_time, $end_time])->group('bid')->count(); //今日被浏览人数

        $tcount = $this->getTelList($start,$end);
        $data = [
            'rcount'=>$rcount,
            'tcount'=>count($tcount),
            'browse_num'=>$browse_num,
            'browsed_num'=>$browsed_num,
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

   public function send(){
        $to = '15711270182';                                                            //收信人 手机号码
        $project_id = 'pjjUb4';                                                           //模板ID
        $vars = json_encode([                                                  //模板对应变量
            'realname' => '张',
            'url' => 'v1kj.cn'
        ]);
        $send = new SendService();
        $res = $send->sendMsg($to,$project_id,$vars);
        var_dump($res);
   }

}