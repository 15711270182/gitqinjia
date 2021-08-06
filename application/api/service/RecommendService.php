<?php
/**
 * 推荐类
 * User: tao
 * Date: 2021/7/15 16:06
 */


namespace app\api\service;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class RecommendService
{
    private static $aes_key;
    public function __construct()
    {
        $this::$aes_key = '5b9c2ed3e19c40e5';
    }

    /**
     * 获取推荐列表
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getRecommend($uid, $date, $re_num)
    {
        // 检查明日推荐是否有注销用户
        $this->tomorrowRecommend($uid, $date);
        $re_list = array();
        $user_info = Db::table('children')->where('uid', $uid)->find();

        if (empty($user_info)) return array();
        // 查询今日推荐记录
        $condition['uid'] = $uid;
        $condition['date'] = $date;
        $today_recommend = Db::table('recommend_record')->where($condition)->order('is_match desc')->select();
        if ($today_recommend){
            $had_id = array(); // 已经记录的id 将不再匹配
            $had_cancellation_id = array(); // 记录已经注销的recommendid, 用于更新今日推荐列表
            foreach($today_recommend as $key => $value){
                $children_info = Db::table('children')->where('uid', $value['recommendid'])->find();
                if ($children_info['status'] == 0){
                    // 删除已经注销用户
                    $had_key_arr = [];
                    $had_key_arr['recommendid'] = $value['recommendid'];
                    $had_key_arr['uid'] = $value['uid'];
                    $had_key_arr['date'] = $value['date'];
                    array_push($had_cancellation_id, $had_key_arr);
                    unset($today_recommend[$key]);
                }else{
                    array_push($had_id, $value['recommendid']);
                    $re_list[$key] = $children_info;
                    $re_list[$key]['is_match'] = $value['is_match'];
                }
            }
            $had_cancellation_count = count($had_cancellation_id); // 已注销的用户数
            if ($had_cancellation_count > 0) {
                $update_recommendid = [];
                // 获取高配
                $height_match_list = $this->getHeightMatch($uid, $had_cancellation_count, $had_id);
                if (!empty($height_match_list)){
                    foreach ($height_match_list as $key => $value){
                        array_push($re_list, $value);
                        array_push($had_id, $value['uid']);
                        array_push($update_recommendid, $value['uid']); // 将新获取的uid存更新数组
                    }
                }
                // 获取剩下推荐
                $last_match_list = array();
                $last_match_count = $had_cancellation_count - count($height_match_list);
                if ($last_match_count){
                    $last_match_list = $this->getLastMatch($uid, $last_match_count, $had_id);
                    foreach ($last_match_list as $key => $value){
                        array_push($re_list, $value);
                        array_push($update_recommendid, $value['uid']); // 将新获取的uid存更新数组
                    }
                }

                foreach ($update_recommendid as $key => $value){
                    $had_cancellation_id[$key]['up_id'] = $value;
                }
                // 更新已经注销的推荐
                foreach ($had_cancellation_id as $key => $value){
                    // 更新条件
                    $condition['uid'] = $value['uid'];
                    $condition['date'] = $value['date'];
                    $condition['recommendid'] = $value['recommendid'];

                    $update['recommendid'] = $value['up_id'];
                    Db::table('recommend_record')->where($condition)->update($update);
                }
            }
            return $re_list;
        }else{
            $num = 7; // 完全匹配数
            $total = $re_num; // 总推荐数
            $height_match_list = $this->getHeightMatch($uid, $num);
            $height_match_count = count($height_match_list);
            $had_id_list = array(); // 记录已经匹配到的uid
            if ($height_match_count > 0){
                foreach ($height_match_list as $key => $value){
                    array_push($had_id_list, $value['uid']);
                }
            }
            // 获取剩下推荐
            $last_match_list = array();
            $last_match_count = $total - $height_match_count;
            if ($last_match_count){
                $last_match_list = $this->getLastMatch($uid, $last_match_count, $had_id_list);
            }
            $re_list = array_merge($height_match_list, $last_match_list);
            // 打乱顺序
            shuffle($re_list);
            foreach ($re_list as $key => $value){
                $map = array();
                $map['uid'] = $value['uid'];
                db::name('children')->where($map)->setDec('today_score',5);
                $data = array();
                $data['uid'] = $uid;
                $data['recommendid'] = $value['uid'];
                $data['date'] = $date;
                $data['addtime'] = strtotime($date);
                $data['is_match'] = $value['is_match'];
                db::name('recommend_record')->insert($data);
            }
            return $re_list;
        }
    }

    /**
     * 补充剩余推荐
     * @param $uid
     * @param $total
     * @param array $had_id
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getLastMatch($uid, $total, $had_id = array())
    {
        $re_list = array();
        $user_info = $this->getChildrenInfoByUid($uid);
        // 最小年龄
        if ($user_info['min_age'] == 0 || $user_info['min_age'] == 999) {
            $min_age = 22;
        }else{
            $min_age = $user_info['min_age'];
        }
        // 最高年龄
        if ($user_info['max_age'] == 0 || $user_info['max_age'] === 999){
            $max_age = 80;
        }else{
            $max_age = $user_info['max_age'];
        }
        // 身高条件
        // 最小身高要求
        if ($user_info['min_height'] == 0 || $user_info['min_height'] == 999){
            $min_height = 140;
        }else{
            $min_height = $user_info['min_height'];
        }
        // 最高身高要求
        if ($user_info['max_height'] == 0 || $user_info['max_height'] == 999){
            $max_height = 220;
        }else{
            $max_height = $user_info['max_height'];
        }

        $eduction = $user_info['expect_education'];
        $age_condition = [
            'min_age' => $min_age,
            'max_age' => $max_age
        ];
        $height_condition = [
            'min_height' => $min_height,
            'max_height' => $max_height
        ];
        // 要求条件
        $request_condition = $this->getRequire($age_condition, $height_condition, $eduction);

        // 根据居住的，并放宽要求匹配
        $residence_match_list = $this->replenishRecommend($uid, $total, $request_condition, $had_id, $residence=1);
        if ($residence_match_list){
            foreach ($residence_match_list as $key => $value){
                array_push($re_list, $value);
                array_push($had_id, $value['uid']);
            }
        }
        // 最后判断推荐列表是否匹配完成,否则继续匹配
        $replenish_count = $total - count($re_list);
        if ($replenish_count){
            $replenish_recommend_list = $this->replenishRecommend($uid, $replenish_count, $request_condition, $had_id);
            foreach ($replenish_recommend_list as $key => $value){
                array_push($re_list, $value);
            }
        }
        return $re_list;
    }

    /**
     * 根据用户要求匹配推荐
     * @param $uid      children表中 uid
     * @param $num      推荐数目
     * @param array     $not_id_arr
     * @return array    返回匹配列表
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getHeightMatch($uid, $num, $not_id_arr = array())
    {
        $user_info = Db::table('children')->where('uid', $uid)->find();
        if (empty($user_info)) return array();

        // 获取最近记录(近三天推荐,收藏,联系)
        $had_recommend_id = $this->hadRecommend($uid);

        $condition['status'] = 1;
        $condition['residence'] = $user_info['residence'];
        $condition['sex'] = $user_info['sex'] == 1 ? 2 : 1;

        // 教育要求
        if ($user_info['expect_education']){
            $condition['education'] = $user_info['expect_education'];
        }

        $matchDb = Db::table('children')->where($condition);

        // 年龄要求
        // 最小年龄
        $max_year = $this->getYearByAge($user_info['min_age']);
        if ($user_info['min_age'] == 999 || $user_info['min_age'] == 0){
            $min_age = 18;
            $max_year = $this->getYearByAge($min_age);
        }

        // 最大年龄
        $min_year = $this->getYearByAge($user_info['max_age']);
        if($user_info['max_age'] == 999 || $user_info['max_age'] == 0){
            $max_age = 80;
            $min_year = $this->getYearByAge($max_age);
        }
        $matchDb->where('year', 'between', "$min_year, $max_year");

        // 身高要求CM
        // 最小身高
        $min_height = $user_info['min_height'];
        if ($user_info['min_height'] == 999 || $user_info['min_height'] == 0){
            $condition['min_height'] = 150;
        }

        // 最高身高
        $max_height = $user_info['max_height'];
        if ($user_info['max_height'] == 999 || $user_info['max_height'] == 0){
            $condition['max_height'] = 220;
        }
        $matchDb->where('height', 'between', "$min_height, $max_height");

        $new_not_id_arr = array_merge($had_recommend_id, $not_id_arr);
        array_unique($new_not_id_arr);

        $matchDb->where('uid', 'notin', $new_not_id_arr);
        $match_list = $matchDb->limit($num)->order('today_score desc')->select();
        if (!empty($match_list)){
            foreach ($match_list as $key => $value){
                $match_list[$key]['is_match']  = 2;
            }
        }
        return $match_list;
    }

    /**
     * @param int $uid
     * @param int $num
     * @param $request_condition
     * @param array $had_id
     * @param int $residence  0:居住地不限;1:限制居住地
     * @return array|\PDOStatement|string|\think\Collection|\think\model\Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getOtherMatch($uid, $num, $request_condition, $had_id = array(), $residence=0)
    {
        $user_info = Db::table('children')->where('uid', $uid)->find();
        // 获取近期三天推荐过,收藏,联系的记用户uid,将不再推荐
        $notIn_id = $this->hadRecommend($uid);

        $MatchDb = Db::table('children');
        $condition['is_del'] = 1;
        $condition['status'] = 1;
        $condition['sex'] = $user_info['sex'] == 1 ? 2 : 1;

        if ($residence) $condition['residence'] = $user_info['residence'];
        $MatchDb = $MatchDb->where($condition);

        // 学历
        if ($request_condition['education']) $MatchDb->where('education', '>=', $request_condition['education']);

        // 年龄
        if ($request_condition['year']) $MatchDb->where('year', 'between',[$request_condition['year']['min_year'], $request_condition['year']['max_year']]);

        // 身高
        if ($request_condition['height']) $MatchDb->where('height', 'between',[$request_condition['height']['min_height'], $request_condition['height']['max_height']]);

        $MatchDb = $MatchDb->where('uid', 'notin', array_unique(array_merge($notIn_id, $had_id)));

        // 根据匹配条件获取推荐
        $other_list_match_list = $MatchDb->limit($num)->order('today_score desc')->select();

        foreach ($other_list_match_list as $key => $value){
            $other_list_match_list[$key]['is_match'] = 1;
        }
        return $other_list_match_list;
    }

    /**
     * 检查明日推荐
     * @param $uid
     * @param $date
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function tomorrowRecommend($uid, $date)
    {
        $had_id = array(); // 记录未注销,并在明日推荐中id
        $update_info = array(); // 更新明日推荐中已注销记录
        $condition['uid'] = $uid;
        $condition['date'] = $date;
        // 判断昨天的明日推荐是否存在
        if (!$this->existTomorrowRecommend($condition)) return;

        // 获取明日推荐
        $tomorrow_recommend = Db::table('tomorrow_recommend')->where($condition)->select();

        foreach ($tomorrow_recommend as $key => $value){
            $children_info = $this->getChildrenInfoByUid($value['recommendid']);
            // 判断明日推荐用户是否有注销
            if ($children_info['status'] == 0){
                $up_info = array();
                $up_info['uid'] = $value['uid'];
                $up_info['date'] = $value['date'];
                $up_info['recommendid'] = $value['recommendid'];
                array_push($update_info, $up_info);
            }else{
                array_push($had_id, $value['recommendid']);
            }
        }
        if ($update_info){
            $update_list = array();  // 记录匹配到uid
            $replenish_count = count($update_info);
            // 根据要求完全匹配
            $height_match_list = $this->getHeightMatch($uid, $replenish_count);
            $height_match_count = count($height_match_list);
            if ($height_match_list){
                foreach ($height_match_list as $key => $value){
                    array_push($had_id, $value['uid']);
                    array_push($update_list, $value['uid']);
                }
            }
            $last_count = $replenish_count - $height_match_count;
            if ($last_count){ // 判断是否匹配完成,否则补充明日推荐
                $user_info = $this->getChildrenInfoByUid($uid);
                $eduction = $user_info['expect_education'];
                $age_condition = [
                    'min_age' => $user_info['min_age'],
                    'max_age' => $user_info['max_age']
                ];
                $height_condition = [
                    'min_height' => $user_info['min_height'],
                    'max_height' => $user_info['max_height']
                ];
                // 获取匹配条件
                $request_condition = $this->getRequire($age_condition, $height_condition, $eduction);
                // 限制居住地进行匹配
                $residence_match_list = $this->replenishRecommend($uid, $last_count, $request_condition, $had_id, $residence = 1);
                if ($residence_match_list){
                    foreach ($residence_match_list as $key => $value){
                        array_push($had_id, $value['uid']);
                        array_push($update_list, $value['uid']);
                    }
                }

                $replenish_count = $last_count - count($update_list);
                // 不限居住地和要求
                if ($replenish_count){
                    $replenish_recommend_list = $this->replenishRecommend($uid, $replenish_count, $request_condition, $had_id);
                    foreach ($replenish_recommend_list as $key => $value){
                        array_push($update_list, $value['uid']);
                    }
                }
                // 整合更新明日推荐记录
                foreach ($update_list as $key => $value){
                    $update_info[$key]['up_id'] = $value;
                }

                // 更新已经注销的明日推荐
                foreach ($update_info as $key => $value){
                    // 更新条件
                    $condition['uid'] = $value['uid'];
                    $condition['date'] = $value['date'];
                    $condition['recommendid'] = $value['recommendid'];

                    $update['recommendid'] = $value['up_id'];
                    $this->updateTomorrowRecommend($condition, $update);
                }
            }
        }
    }

    /**
     * 继续匹配推荐,不限居住地
     * @param int   $uid              用户id
     * @param int   $total            查询数
     * @param array $request          要求
     * @param array $had_id           已存在的id
     * @param int $residence          居住的
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function replenishRecommend($uid, $total, $request, $had_id=array(), $residence=0)
    {
        $residence_count = 0;
        $replenish_list = array();
        while ($residence_count < $total){
            $other_list = $this->getOtherMatch($uid, $total, $request, $had_id, $residence);
            if ($other_list){
                foreach ($other_list as $key => $value){
                    array_push($had_id, $value['uid']);
                    array_push($replenish_list, $value);
                }
            }
            if ($total){
                if ($request['age']['min_age'] < 18) break;
            }
            $age_condition['min_age'] = $request['age']['min_age'];
            $age_condition['max_age'] = $request['age']['max_age'];
            $height_condition['min_height']= $request['height']['min_height'];
            $height_condition['max_height'] = $request['height']['max_height'];
            $request = $this->getRequire($age_condition, $height_condition, $request['education']);
            $residence_count = count($other_list);
            $total = $total - $residence_count;
        }
        return $replenish_list;
    }

    /**
     * 根据要求,生成匹配条件
     * @param array $age_condition          年龄条件范围
     * @param array $height_condition       身高条件范围
     * @param int $eduction_condition       学历
     * @return array
     */
    protected function getRequire($age_condition, $height_condition, $eduction_condition)
    {
        $require = array();
        $age = $this->getAge($age_condition['min_age'], $age_condition['max_age']);
        $year['min_year'] = $this->getYearByAge($age['max_age']);
        $year['max_year'] = $this->getYearByAge($age['min_age']);
        $height = $this->getHeight($height_condition['min_height'], $height_condition['max_height']);
        $require['age'] = $age;
        $require['year'] = $year;
        $require['height'] = $height;
        $require['education'] = $this->getEducation($eduction_condition);
        return $require;
    }

    /**
     * 放宽学历要求
     * @param $education
     * @return int
     */
    protected function getEducation($education)
    {
        return $education - 1;
    }

    /**
     * 放宽年龄要求
     * @param $min_age 最低年龄要求
     * @param $max_age 最高年龄要求
     * @param int $age 默认放宽年龄为 1 岁
     * @return array
     */
    protected function getAge($min_age, $max_age, $age=1)
    {
        $age_arr = [];
        $age_arr['min_age'] = $min_age - $age;
        $age_arr['max_age'] = $max_age + $age;
        return $age_arr;
    }

    /**
     * 放宽身高要求
     * @param $min_height   最低身高要求
     * @param $max_height   最高身高要求
     * @param int $height   默认放宽身高为 5 cm
     * @return array
     */
    protected function getHeight($min_height, $max_height, $height=5)
    {
        $height_arr = [];
        $height_arr['min_height'] = $min_height - $height;
        $height_arr['max_height'] = $max_height + $height;
        return $height_arr;
    }

    /**
     * 根据年龄获取出生年份
     * @param $age
     * @return false|string
     */
    public function getYearByAge($age)
    {
        return date('Y') - $age;
    }


    /**
     * 获取最近三天,已收藏,已联系的 记录,将不予推荐
     * @param $uid      用户id
     * @return string   用户id字符串
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function hadRecommend($uid)
    {
        // 获取近三天推荐记录
        $near_three_id_list = array();
        $near_three_date = date('Ymd', strtotime('-3 days'));
        $near_three_list = Db::table('recommend_record')->where('uid', $uid)
            ->where('date', '>=', $near_three_date)->select();

        foreach ($near_three_list as $key => $value){
            array_push($near_three_id_list, $value['recommendid']);
        }

        // 获取我已收藏
        $had_collection_id_list = array();
        $had_collection['uid'] = $uid;
        $had_collection['is_del'] = 1;
        $had_collection_list = Db::table('collection')->where($had_collection)->field('bid')->select();
        foreach ($had_collection_list as $key => $value){
            array_push($had_collection_id_list, $value['bid']);
        }

        // 获取已联系
        $had_tel_id_list = array();
        $had_tel['uid'] = $uid;
        $had_tel['is_del'] = 1;
        $had_tel_list = Db::table('tel_collection')->where($had_tel)->field('bid')->select();
        foreach ($had_tel_list as $key => $value){
            array_push($had_tel_id_list, $value['bid']);
        }

        $not_id_list = array_merge($near_three_id_list, $had_tel_id_list, $had_tel_id_list);
        return array_unique($not_id_list);
    }

    /**
     * 根据 uid 获取 children 信息
     * @param $uid
     * @return array|\PDOStatement|string|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getChildrenInfoByUid($uid)
    {
        return Db::table('children')->where('uid', $uid)->find();
    }

    /**
     * 更新明日推荐记录
     * @param $condition
     * @param $update
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateTomorrowRecommend($condition, $update)
    {
        Db::table('tomorrow_recommend')->where($condition)->update($update);
    }

    /**
     * 判断明日推荐是否存在
     * @param $condition
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function existTomorrowRecommend($condition)
    {
        $tomorrow = Db::table('tomorrow_recommend')->where($condition)->select();
        if ($tomorrow){
            return true;
        }else{
            return false;
        }
    }


}