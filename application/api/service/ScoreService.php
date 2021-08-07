<?php
/**
 * Created by PhpStorm.
 * User: LiDaShanRen
 * Date: 2021/7/1
 * Time: 15:01
 */

namespace app\api\service;
use app\api\model\Children as ChildrenModel;
use app\api\model\WeightScore as WeightModel;
use library\Service;
use think\Db;

class ScoreService extends Service
{
    /**
     * @Notes:处理编辑资料的权重分
     * @Interface editScoreInc
     * @author: zy
     * @Time: 2021/08/06
     */
    public function editScoreInc($uid,$field,$value){

        switch($field){
            case 'height':$this->weightScoreInc($uid,32);//身高
                break;
            case 'residence':$this->weightScoreInc($uid,2);//现居地
                break;
            case 'education':$this->weightScoreInc($uid,3);//学历
                break;
            case 'income':$this->weightScoreInc($uid,4);//收入
                break;
            case 'bro':$this->weightScoreInc($uid,5);//家中排行
                break;
            case 'parents':$this->weightScoreInc($uid,6);//父母情况
                break;
            case 'native_place':$this->weightScoreInc($uid,7);//户籍地
                break;
            case 'hometown':$this->weightScoreInc($uid,8);//家乡
                break;
            case 'school':$this->weightScoreInc($uid,9);//学校
                break;
            case 'work':$this->weightScoreInc($uid,10);//工作
                break;
            case 'house':
                if($value == 1){ //1 已购房 2父母同住 3租房
                    $this->weightScoreInc($uid,12);
                }elseif($value == 2){
                    $this->weightScoreInc($uid,13);
                }else{
                    $this->weightScoreInc($uid,14);
                }
                break;
            case 'cart':
               if($value == 1){ //1 已购车 2近期购车 3无车
                    $this->weightScoreInc($uid,15);
                }elseif($value == 2){
                    $this->weightScoreInc($uid,16);
                }else{
                    $this->weightScoreInc($uid,17);
                }
                break;
            case 'remarks':$this->weightScoreInc($uid,1);//相亲说明
                break;
            case 'expect_education':$this->weightScoreInc($uid,18);//择偶学历
                break;
            case 'ask_age':$this->weightScoreInc($uid,19);//择偶年龄
                break;
            case 'ask_height':$this->weightScoreInc($uid,20);//择偶身高
                break;
            default:break;
        }
    }
    /**
     * Notes:权重分自增
     * User: zy
     * Date: 2021/8/6
     * Time:16:58
     */
    public function weightScoreInc($uid,$type,$operator_uid='0')
    {
        $saveData = [];
        $typeInfo = WeightModel::scoreTypeFind(['id'=>$type]);
        $sign = 0;//默认是添加操作
        if ($typeInfo['score'] < 0) {
            $sign = 1;
        }
        if ($typeInfo['operation_type'] == 1) {
            //唯一操作
            $where = [];
            $where['uid'] = $uid;
            $where['type'] = $type;
            $isHave = WeightModel::scoreRecordFind(['uid' => $uid, 'type' => $type]);
            if ($isHave) {
                custom_log('WeightScore', 'uid=>' . $uid . ':' . '禁止通过同样操作获取推荐分数');
                return false;
            }
        }
        if ($typeInfo['operation_type'] == 2) {
            $score = ChildrenModel::getchildrenField(['uid' => $uid],'weight_score');
            //非唯一操作
            if ($score - $typeInfo['score'] < 0) {
                //是删除操作  并且 已经到了最低分数临界点
                custom_log('WeightScore', 'uid=>' . $uid . ':' . '已经到了最低分数临界点');
                return false;
            }
//            if (in_array($type, [11, 12, 13,30,31])) {
//                $count = Db::name('inlove_weight_score_record')->where(['uid' => $uid, 'type' => $type,'is_del'=>0])->count();
//                if (abs($count*$typeInfo['score']) >= $typeInfo['upper_limit']) {
//                    custom_log('WeightScore', 'uid=>' . $uid . ':' . '超过上限');
//                    return false;
//                }
//            }
        }
        ChildrenModel::getchildrenInt(['uid'=>$uid],'weight_score',$typeInfo['score']);
        $date = date('Y-m-d H:i:s');
        $saveData['uid'] = $uid;
        $saveData['score'] = $typeInfo['score'];
        $saveData['create_time'] = $date;
        $saveData['update_time'] = $date;
        $saveData['type'] = $type;
        $saveData['operator_uid'] = $operator_uid;
        WeightModel::scoreRecordAdd($saveData);
    }
}