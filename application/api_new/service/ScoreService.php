<?php
/**
 * Created by PhpStorm.
 * User: LiDaShanRen
 * Date: 2021/7/1
 * Time: 15:01
 */

namespace app\api_new\service;
use app\api_new\model\Children as ChildrenModel;
use app\api_new\model\WeightScore as WeightModel;
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
    public function editScoreInc($uid,$field,$value=''){

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


     /**
     * @Notes:处理用户资料的完善分
     * @Interface editScoreInc
     * @author: zy
     * @Time: 2021/12/04
     */
    public function editFullInc($uid,$field,$value=''){
        switch($field){
            case 'realname':$this->fullScoreInc($uid,1);//真实姓名
                break;
            case 'sex':$this->fullScoreInc($uid,2);//性别
                break;
            case 'year':$this->fullScoreInc($uid,3);//年份
                break;
            case 'height':$this->fullScoreInc($uid,4);//身高
                break;
            case 'residence':$this->fullScoreInc($uid,5);//现居地
                break;
            case 'education':$this->fullScoreInc($uid,6);//学历
                break;
            case 'income':$this->fullScoreInc($uid,7);//收入
                break;
            case 'bro':$this->fullScoreInc($uid,8);//家中排行
                break;
            case 'parents':$this->fullScoreInc($uid,9);//父母情况
                break;
            case 'native_place':$this->fullScoreInc($uid,10);//户籍地
                break;
            case 'hometown':$this->fullScoreInc($uid,11);//家乡
                break;
            case 'school':$this->fullScoreInc($uid,12);//学校
                break;
            case 'work':$this->fullScoreInc($uid,13);//工作
                break;
            case 'house':$this->fullScoreInc($uid,14);
                break;
            case 'cart':$this->fullScoreInc($uid,15);
                break;
            case 'remarks':$this->fullScoreInc($uid,16);//相亲说明
                break;
            case 'expect_education':$this->fullScoreInc($uid,17);//择偶学历
                break;
            case 'ask_age':$this->fullScoreInc($uid,18);//择偶年龄
                break;
            case 'ask_height':$this->fullScoreInc($uid,19);//择偶身高
                break;
            default:break;
        }
    }
    /**
     * Notes:完善分自增
     * User: zy
     * Date: 2021/12/04
     * Time:16:58
     */
    public function fullScoreInc($uid,$type)
    {
        $saveData = [];
        $typeInfo = DB::name('full_score_type')->where(['id'=>$type])->find();
        $sign = 0;//默认是添加操作
        if ($typeInfo['score'] < 0) {
            $sign = 1;
        }
       
        //唯一操作
        $where = [];
        $where['uid'] = $uid;
        $where['type'] = $type;
        $isHave = DB::name('full_score_record')->where(['uid' => $uid, 'type' => $type])->find();
        if ($isHave) {
            custom_log('WeightScore', 'uid=>' . $uid . ':' . '禁止通过同样操作获取分数');
            return false;
        }
        
        ChildrenModel::getchildrenInt(['uid'=>$uid],'full_info',$typeInfo['score']);
        $date = date('Y-m-d H:i:s');
        $saveData['uid'] = $uid;
        $saveData['score'] = $typeInfo['score'];
        $saveData['create_time'] = $date;
        $saveData['update_time'] = $date;
        $saveData['type'] = $type;
        DB::name('full_score_record')->strict(false)->insertGetId($saveData);
    }
}