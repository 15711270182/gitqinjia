<?php
/**
 * 推荐类
 * User: tao
 * Date: 2021/7/15 16:06
 */


namespace app\api_new\service;

use app\api_new\service\UsersService;
use app\api\model\User as UserModel;
use app\api_new\model\Children as ChildrenModel;
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
     * 获取首页数据列表  
     * 基础条件：已认证 未注销 未禁用 性别相反 
     * 潜在条件: 城市匹配  择偶要求匹配
     * 排序规则：登陆时间最新 已认证视频  资料完善度高
     * 1.城市与择偶高度匹配 2.城市与其他匹配  3.非城市精准  4.非城市非精准
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getRecommend($uid,$page,$pageSize)
    {
        $user_info = Db::name('children')->where('uid', $uid)->find();
        if (empty($user_info)) return [];

        $count_pageSize = $pageSize;
        $limit = ($page - 1) * $pageSize;

        $field = "";
        $order = "login_last_time desc,video_url desc,full_info desc";
        $condition['sex'] = 1;
        if($user_info['sex'] == 1){
            $condition['sex'] = 2;
        }
        $condition['auth_status'] = 1;
        $condition['status'] = 1;
        $condition['is_ban'] = 1;
        //获取条件  1精准  0非精准
        $where_match = $this->getWhereMatch($uid,0); 
        // var_dump($where_match);die;
        $totalCountAll = Db::name('children')->where($condition)->where($where_match)->count(); //已认证总条数
        $totalPageAll = ceil($totalCountAll / $pageSize); //已认证总页数

        $data = [];
        $data['totalCountAll'] = $totalCountAll;
        $data['totalPageAll'] = $totalPageAll;

        //区域条件
        $condition['residence'] = $user_info['residence'];
        $totalCount = Db::name('children')->where($condition)->where($where_match)->count(); //区域匹配总条数
        $totalPage = ceil($totalCount / $pageSize); //区域匹配总页数
        // var_dump($totalCount); 

        $where_match = $this->getWhereMatch($uid,1); 


        /**
        *   区域无数据 不匹配区域的情况下   (1.非区域精准查询  2.非区域非精准查询 )
        */
        if(empty($totalCount)){
            $totalMatchList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0);
            $matchCount = count($totalMatchList);
            $count_match = $count_pageSize - $matchCount;
            // var_dump($count_match);
            if($count_match == 0){ //非区域精准条件 无需补全 直接返回
                $re_list = $this->getDataList($totalMatchList);
                $data['list'] = $re_list;
                return $data;
            }
            //非区域非精准
            $where_match = $this->getWhereMatch($uid,0);
            if($count_match != $pageSize){
                $limit = 0;
                $pageSize = $count_match;
            }
            $totalOtherList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0);
            $list = array_merge($totalMatchList, $totalOtherList);
            // var_dump(count($list));

            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;

        }
        /**
        *   区域有数据 匹配区域的情况下   1.区域精准    2.区域非精准  3.非区域精准   4.非区域非精准
        */
        $totalMatchList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1);
        // var_dump($totalMatchList);die;
        $matchCount = count($totalMatchList);
        $count_match = $count_pageSize - $matchCount;
        if($count_match == 0){ //有区域精准条件 无需补全 直接返回
            $re_list = $this->getDataList($totalMatchList);
            $data['list'] = $re_list;
            return $data;
        }
        //精准匹配数据不全/无精准数据 查询区域下其他数据
        $where_match = $this->getWhereMatch($uid,0);
        if($count_match != $pageSize){
            $limit = 0;
            $pageSize = $count_match;
        }
        $totalOtherList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1);
        $otherCount = count($totalOtherList);
        // var_dump($otherCount);
        $count_other = $count_pageSize - $matchCount - $otherCount;
        // var_dump($count_other);
        if($count_other == 0){
            $list = array_merge($totalMatchList, $totalOtherList);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;
        }

        //列表中 其他数据不全 补全非区域精准
        if($count_other != $pageSize){
            $limit = 0;
            $pageSize = $count_other;
        }
        $where_match = $this->getWhereMatch($uid,1);
        $totalNoCitymList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0);
        $noCitymCount = count($totalNoCitymList);
        $count_city = $count_pageSize - $matchCount - $otherCount - $noCitymCount;
        // var_dump($count_city);
        if($count_city == 0){
            $list = array_merge($totalMatchList, $totalOtherList, $totalNoCitymList);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;
        }
        //非区域 非精准
        if($count_city != $pageSize){
            $limit = 0;
            $pageSize = $count_city;
        }
        // var_dump($limit);
        // var_dump($pageSize);
        $where_match = $this->getWhereMatch($uid,0);
        $totalNoCityList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0);
        $noCityCount = count($totalNoCityList);
        // var_dump($noCityCount);
        $list = array_merge($totalMatchList, $totalOtherList, $totalNoCitymList,$totalNoCityList);
        // var_dump(count($list));
        $re_list = $this->getDataList($list);
        $data['list'] = $re_list;
        return $data;
    }


     /**
     * 获取首页数据列表  
     * 基础条件：已认证 未注销 未禁用 性别相反 
     * 潜在条件: 城市匹配  择偶要求匹配
     * 排序规则：登陆时间最新 已认证视频  资料完善度高
     * 1.城市与择偶高度匹配 2.城市与其他匹配  3.非城市精准  4.非城市非精准
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getRecommendNew($uid,$page,$pageSize)
    {
        $user_info = Db::name('children')->where('uid', $uid)->find();
        if (empty($user_info)) return [];

        $count_pageSize = $pageSize;
        $limit = ($page - 1) * $pageSize;

        $field = "";
        $order = "login_last_time desc,full_info desc,id desc";
        $condition['sex'] = 1;
        if($user_info['sex'] == 1){
            $condition['sex'] = 2;
        }
        $condition['auth_status'] = 1;
        $condition['status'] = 1;
        $condition['is_ban'] = 1;

        
        $residence = str_replace(['省','市'],'',$user_info['residence']);
        $switch = DB::name('city_switch')->where(['city'=>$residence])->value('is_show');
        if($switch == 1){ //只展示该区域数据
            $condition['residence'] = $user_info['residence'];
            $where_match = $this->getWhereMatch($uid,0);
            // var_dump($where_match);die;
            $totalCount = Db::name('children')->where($condition)->where($where_match)->count(); //区域匹配总条数
            $totalPage = ceil($totalCount / $pageSize); //区域匹配总页数
            // var_dump($totalCount);die;
            $data = [];
            $data['totalCountAll'] = $totalCount;
            $data['totalPageAll'] = $totalPage;

            //精准
            $where_match = $this->getWhereMatch($uid,1); 
            // var_dump($where_match);
            $totalMatchList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,2);
            // var_dump(count($totalMatchList));
            $matchCount = count($totalMatchList);
            $count_match = $count_pageSize - $matchCount;
            if($count_match == 0){ //有区域精准条件 无需补全
                $re_list = $this->getDataList($totalMatchList);
                $data['list'] = $re_list;
                return $data;
            }
            //所有精准的数据 uid 排除
            $jzDataList = ChildrenModel::getSelectJz($condition,$where_match,'uid',$order,1,2);
            //非精准
            $not_id_arr = array_column($jzDataList, 'uid');
            // var_dump($not_id_arr);
            $where_match = $this->getWhereMatch($uid,0,$not_id_arr);
            if($count_match != $pageSize){
                $limit = 0;
                $pageSize = $count_match;
            }

            $totalOtherList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,0);
            $list = array_merge($totalMatchList, $totalOtherList);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;

        }
        //不完全展示该区域
        $where_match = $this->getWhereMatch($uid,0); //获取条件  1精准  2非精准 0全部
        $totalCountAll = Db::name('children')->where($condition)->where($where_match)->count(); //已认证总条数
        $totalPageAll = ceil($totalCountAll / $pageSize); //已认证总页数

        $data = [];
        $data['totalCountAll'] = $totalCountAll;
        $data['totalPageAll'] = $totalPageAll;
        //区域条件
        $condition['residence'] = $user_info['residence'];
        $totalCount = Db::name('children')->where($condition)->where($where_match)->count(); //区域匹配总条数
        $totalPage = ceil($totalCount / $pageSize); //区域匹配总页数
        $where_match = $this->getWhereMatch($uid,1); 
        //区域无数据 不匹配区域的情况下 直接查询
        if(empty($totalCount)){
            $where_match = $this->getWhereMatch($uid,0);
            $list = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0,0);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;

        }
        // 区域有数据 匹配区域的情况下   1.区域精准    2.区域非精准  3.非区域查询
        $totalMatchList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,2);
        // var_dump($totalMatchList);die;
        $matchCount = count($totalMatchList);
        $count_match = $count_pageSize - $matchCount;
        if($count_match == 0){ //有区域精准条件 无需补全 直接返回
            $re_list = $this->getDataList($totalMatchList);
            $data['list'] = $re_list;
            return $data;
        }
        //所有精准的数据 uid 排除
        $jzDataList = ChildrenModel::getSelectJz($condition,$where_match,'uid',$order,1,2);
        //精准匹配数据不全/无精准数据 查询区域下其他数据
        $not_id_arr = array_column($jzDataList, 'uid');
        // var_dump($not_id_arr);
        $where_match = $this->getWhereMatch($uid,0,$not_id_arr);
        if($count_match != $pageSize){
            $limit = 0;
            $pageSize = $count_match;
        }

        $totalOtherList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,0);
        $otherCount = count($totalOtherList);
        // var_dump($otherCount);
        $count_other = $count_pageSize - $matchCount - $otherCount;
        // var_dump($count_other);
        if($count_other == 0){
            $list = array_merge($totalMatchList, $totalOtherList);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;
        }

        //列表中 其他数据不全 补全非区域
        if($count_other != $pageSize){
            $limit = 0;
            $pageSize = $count_other;
        }
        $totalNoCitymList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0,0);
        $noCitymCount = count($totalNoCitymList);
        $count_city = $count_pageSize - $matchCount - $otherCount - $noCitymCount;
        $list = array_merge($totalMatchList, $totalOtherList, $totalNoCitymList);
        // var_dump($list);die;
        $re_list = $this->getDataList($list);
        $data['list'] = $re_list;
        return $data;
    }
    /**
     * 获取首页数据列表 - 未认证  
     * 基础条件：已认证 未注销 未禁用 性别相反 
     * 潜在条件: 城市匹配  择偶要求匹配
     * 排序规则：登陆时间最新 已认证视频  资料完善度高
     * 1.城市与择偶高度匹配 2.城市与其他匹配  3.非城市精准  4.非城市非精准
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getRecommendNewUnAuth($uid,$page,$pageSize)
    {
        $user_info = Db::name('children')->where('uid', $uid)->find();
        if (empty($user_info)) return [];

        $count_pageSize = $pageSize;
        $limit = ($page - 1) * $pageSize;

        $field = "";
        $order = "login_last_time desc,full_info desc,id desc";
        $condition['sex'] = 1;
        if($user_info['sex'] == 1){
            $condition['sex'] = 2;
        }
        $condition['auth_status'] = 0; //未认证
        $condition['status'] = 1;
        $condition['is_ban'] = 1;

        
        $residence = str_replace(['省','市'],'',$user_info['residence']);
        $switch = DB::name('city_switch')->where(['city'=>$residence])->value('is_show');
        if($switch == 1){ //只展示该区域数据
            $condition['residence'] = $user_info['residence'];
            $where_match = $this->getWhereMatch($uid,0);
            // var_dump($where_match);die;
            $totalCount = Db::name('children')->where($condition)->where($where_match)->count(); //区域匹配总条数
            $totalPage = ceil($totalCount / $pageSize); //区域匹配总页数
            // var_dump($totalCount);die;
            $data = [];
            $data['totalCountAll'] = $totalCount;
            $data['totalPageAll'] = $totalPage;

            //精准
            $where_match = $this->getWhereMatch($uid,1); 
            // var_dump($where_match);
            $totalMatchList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,2);
            // var_dump(count($totalMatchList));
            $matchCount = count($totalMatchList);
            $count_match = $count_pageSize - $matchCount;
            if($count_match == 0){ //有区域精准条件 无需补全
                $re_list = $this->getDataList($totalMatchList);
                $data['list'] = $re_list;
                return $data;
            }
            //所有精准的数据 uid 排除
            $jzDataList = ChildrenModel::getSelectJz($condition,$where_match,'uid',$order,1,2);
            //非精准
            $not_id_arr = array_column($jzDataList, 'uid');
            // var_dump($not_id_arr);
            $where_match = $this->getWhereMatch($uid,0,$not_id_arr);
            if($count_match != $pageSize){
                $limit = 0;
                $pageSize = $count_match;
            }

            $totalOtherList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,0);
            $list = array_merge($totalMatchList, $totalOtherList);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;

        }
        //不完全展示该区域
        $where_match = $this->getWhereMatch($uid,0); //获取条件  1精准  2非精准 0全部
        $totalCountAll = Db::name('children')->where($condition)->where($where_match)->count(); //已认证总条数
        $totalPageAll = ceil($totalCountAll / $pageSize); //已认证总页数

        $data = [];
        $data['totalCountAll'] = $totalCountAll;
        $data['totalPageAll'] = $totalPageAll;
        //区域条件
        $condition['residence'] = $user_info['residence'];
        $totalCount = Db::name('children')->where($condition)->where($where_match)->count(); //区域匹配总条数
        $totalPage = ceil($totalCount / $pageSize); //区域匹配总页数
        $where_match = $this->getWhereMatch($uid,1); 
        //区域无数据 不匹配区域的情况下 直接查询
        if(empty($totalCount)){
            $where_match = $this->getWhereMatch($uid,0);
            $list = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0,0);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;

        }
        // 区域有数据 匹配区域的情况下   1.区域精准    2.区域非精准  3.非区域查询
        $totalMatchList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,2);
        // var_dump($totalMatchList);die;
        $matchCount = count($totalMatchList);
        $count_match = $count_pageSize - $matchCount;
        if($count_match == 0){ //有区域精准条件 无需补全 直接返回
            $re_list = $this->getDataList($totalMatchList);
            $data['list'] = $re_list;
            return $data;
        }
        //所有精准的数据 uid 排除
        $jzDataList = ChildrenModel::getSelectJz($condition,$where_match,'uid',$order,1,2);
        //精准匹配数据不全/无精准数据 查询区域下其他数据
        $not_id_arr = array_column($jzDataList, 'uid');
        // var_dump($not_id_arr);
        $where_match = $this->getWhereMatch($uid,0,$not_id_arr);
        if($count_match != $pageSize){
            $limit = 0;
            $pageSize = $count_match;
        }

        $totalOtherList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,1,0);
        $otherCount = count($totalOtherList);
        // var_dump($otherCount);
        $count_other = $count_pageSize - $matchCount - $otherCount;
        // var_dump($count_other);
        if($count_other == 0){
            $list = array_merge($totalMatchList, $totalOtherList);
            $re_list = $this->getDataList($list);
            $data['list'] = $re_list;
            return $data;
        }

        //列表中 其他数据不全 补全非区域
        if($count_other != $pageSize){
            $limit = 0;
            $pageSize = $count_other;
        }
        $totalNoCitymList = ChildrenModel::getSelect($condition,$where_match,$field,$order,$limit,$pageSize,0,0);
        $noCitymCount = count($totalNoCitymList);
        $count_city = $count_pageSize - $matchCount - $otherCount - $noCitymCount;
        $list = array_merge($totalMatchList, $totalOtherList, $totalNoCitymList);
        // var_dump($list);die;
        $re_list = $this->getDataList($list);
        $data['list'] = $re_list;
        return $data;
    }
    //数据处理
    public function getDataList($list)
    {
        $re_list = [];
        foreach ($list as $key => $value) {

            $re_list[$key] = $this->userchange($value);
        }
        return $re_list;
    }

    /**
     * 获取,精准匹配条件
     * @param $uid      用户id
     * @return string   用户id字符串
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getWhereMatch($uid,$is_match = 0,$not_id_arr = array())
    {
        if($is_match == 1){  //高配
            $where_match = $this->getHeightMatch($uid);
        }elseif($is_match == 2){ //低配 条件放宽
            $where_match = $this->getLowMatch($uid,$not_id_arr);
        }else{
            $where_match = "is_del = 1 and video_url = ''";
            $notIn_id = $this->hadRecommend($uid); //去除 已收藏已联系用户
            $new_not_id_arr = array_merge($notIn_id, $not_id_arr);
            array_unique($new_not_id_arr);
            if($notIn_id){
                $notIn_id = implode(',', $new_not_id_arr);
                $where_match .= " and uid not in({$notIn_id})";
            }
        }
        return $where_match;
    }

     /**
     * 根据用户要求匹配条件
     * @param $uid      children表中 uid
     * @param $num      推荐数目
     * @return array    返回匹配列表
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getHeightMatch($uid)
    {
        $where_match = "is_del = 1 and video_url = ''";
        $notIn_id = $this->hadRecommend($uid); //去除 已收藏已联系用户
        if($notIn_id){
            $notIn_id = implode(',', $notIn_id);
            $where_match .= " and uid not in({$notIn_id})";
        }
        $field = "year,height,education,expect_education,min_age,max_age,min_height,max_height";
        $user_info = Db::name('children')->field($field)->where(['uid'=>$uid])->find();
        //学历要求
        $education = $user_info['education'];
        if ($user_info['expect_education']){
            $education = $user_info['expect_education'];
        }
        $where_match .= " and education = {$education}";
        //年龄要求 选择择偶年龄 不限 18岁起  未选择 按子女的年龄相符
        if ($user_info['min_age'] == 999){ //年龄最小不限
            $min_age = 18;
            $max_year = $this->getYearByAge($min_age);
        }else{
            if($user_info['min_age'] > 0 ){
                $max_year = $this->getYearByAge($user_info['min_age']);
            }else{
                $max_year = $user_info['year']+2;
            }
        }
        // 最大年龄
        if($user_info['max_age'] == 999){ //年龄最大不限
            $max_age = 45;
            $min_year = $this->getYearByAge($max_age);
        }else{
            if($user_info['max_age'] > 0){
                 $min_year = $this->getYearByAge($user_info['max_age']);
            }else{
                 $min_year =  $user_info['year']-2;
            }
        }
        $where_match .= " and year between '{$min_year}' and '{$max_year}'";
        // 身高条件
        if ($user_info['min_height'] == 0 || $user_info['min_height'] == 999){
            $min_height = 150;
        }else{
            $min_height = $user_info['min_height'];
        }
        if ($user_info['max_height'] == 0 || $user_info['max_height'] == 999){
            $max_height = 190;
        }else{
            $max_height = $user_info['max_height'];
        }
        $where_match .= " and height between '{$min_height}' and '{$max_height}'";

        return $where_match;
    }

    /**
     * 根据用户要求放宽条件
     * @param $uid      children表中 uid
     * @param $num      推荐数目
     * @return array    返回匹配列表
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function getLowMatch($uid,$not_id_arr)
    {
        $where_match = "is_del = 1";
        $notIn_id = $this->hadRecommend($uid); //去除 已收藏已联系用户
        $new_not_id_arr = array_merge($notIn_id, $not_id_arr);
        array_unique($new_not_id_arr);
        if($notIn_id){
            $notIn_id = implode(',', $new_not_id_arr);
            $where_match .= " and uid not in({$notIn_id})";
        }
        $field = "year,height,education,expect_education,min_age,max_age,min_height,max_height";
        $user_info = Db::name('children')->field($field)->where(['uid'=>$uid])->find();
        //年龄要求
        if ($user_info['min_age'] == 999) {
            $min_age = 18;
        }else{
            if($user_info['min_age'] > 0){
                $min_age = $user_info['min_age'];
            }else{
                $getAgeMin = $this->getYearAge($user_info['year']);
                $min_age = $getAgeMin - 10;
            }
        }
        if ($user_info['max_age'] === 999){
            $max_age = 45;
        }else{
            if($user_info['max_age'] > 0){
                $max_age = $user_info['max_age'];
            }else{
                $getAgeMax = $this->getYearAge($user_info['year']);
                $max_age = $getAgeMax + 10;
            }
        }
        // 身高条件
        if ($user_info['min_height'] == 0 || $user_info['min_height'] == 999){
            $min_height = 150;
        }else{
            $min_height = $user_info['min_height'];
        }
        if ($user_info['max_height'] == 0 || $user_info['max_height'] == 999){
            $max_height = 190;
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

        // 学历
        if ($request_condition['education']){
            $where_match .= " and education >= '{$request_condition['education']}'";
        }
        // 年龄
        if ($request_condition['year']){
            $where_match .= " and year between '{$request_condition['year']['min_year']}' and '{$request_condition['year']['max_year']}'";
        }
        // 身高
        if ($request_condition['height']){
            $where_match .= " and height between '{$request_condition['height']['min_height']}' and '{$request_condition['height']['max_height']}'";
        }
        // var_dump($where_match);
        return $where_match;
    }
    /**
     * 获取,已收藏,已联系的 记录,将不予推荐
     * @param $uid      用户id
     * @return string   用户id字符串
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function hadRecommend($uid)
    {
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

        $not_id_list = array_merge($had_collection_id_list, $had_tel_id_list);
        return array_unique($not_id_list);
    }

    /**
     * 用户数据转化成前端需要的样式
     * @author zy
    */
    public function userchange($value)
    {
        $education = UsersService::education($value['education']);//学历
        $income = UsersService::income($value['income']);//收入
        if($income){
            $income = '月收入'.$income;
        }
        $cart = UsersService::cart($value['cart']);//车
        $parents = UsersService::parents($value['parents']);//父母状况
        $bro = UsersService::bro($value['bro']);//子女情况
        if(isset($value['id'])){
            $user['id'] = $value['id'];
        }
        $user['uid']  = $value['uid'];
        $user['first']  = $value['sex']==1?'男':'女';
        if ($value['year']){
            $user['first'] = $user['first'].'·'.substr($value['year'],-2).'年('.getShuXiang($value['year']).')' ;
        }
        $work = $value['work'];
        if($value['work'] && $value['income']){
            $work = $value['work'].'·';
        }
        $four = '';
        if($value['hometown']){
            $four = '老家'.$value['hometown'].'·';
        }
        if($value['native_place']){
            $four = $four.$value['native_place'].'户口·';
        }
        if($value['residence']){
            $four = $four.'现居'.$value['residence'];
        }
        switch($value['house']){
            case 0:
                $five = '暂未填写';break;
            case 1:
                $five = '有房·';break;
            case 2:
                $five = '和父母住·';break;
            default:$five = '租房·';break;
        }
        $six = '';
        if ($value['bro']){
            $six = $bro;
            if(!empty($parents)){
                $six = $parents.'·'.$bro;
            }
        }
        $user['first'] = $user['first'].'·'.$education;
        $user['second']= !empty($value['school'])?$value['school']:'';
        $user['three'] = $work.$income;
        $user['four']  = $four;
        $user['five']  = $five.$cart;
        $user['six']   = $six;
        $user['remark'] = $value['remarks'];
        //根据资料完善程度  排序
        $type_five = $user['five'];
        if($user['five'] == '暂未填写'){
            $type_five = '';
        }
        $user['video_url'] = $value['video_url'];
        $user['login_last_time'] = date('Y-m-d',strtotime($value['login_last_time']));

        //查询用户父母的名称
        $pare = UserModel::userFind(['id'=>$value['uid']]);
        $user['realname'] = $pare['realname']?$pare['realname'].'家长':'家长';
        $user['headimgurl'] = $pare['headimgurl'];
        $user['user_sex'] = $pare['sex'];
        $user['user_status'] = $pare['status'];
        $user['sex'] = $value['sex'];
        $user['is_match'] = isset($value['is_match'])?$value['is_match']:0;
        return $user;
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

    //将生日转化为年龄
    public function getYearAge($birthday){
         $age = strtotime($birthday);
         if($age === false){
            return false;
         }
         list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
         $now = strtotime("now");
         list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
         $age = $y2 - $y1;
         if((int)($m2.$d2) < (int)($m1.$d1))
          $age -= 1;
         return $age;
    }

}