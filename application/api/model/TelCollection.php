<?php


namespace app\api\model;
use app\api\model\User as UserModel;
use think\Model;
use think\Db;
class TelCollection extends Model
{
    protected $table = 'tel_collection';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function telAdd($data)
    {
        return Db::name('tel_collection')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function telSelect($where='',$field='',$order='')
    {
        return Db::name('tel_collection')->field($field)->where($where)->order($order)->select();
    }

    public static function telSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('tel_collection')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function telFind($where='',$field='',$order='')
    {
        return Db::name('tel_collection')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function telEdit($where,$data)
    {
        return Db::name('tel_collection')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function telCount($where)
    {
        return Db::name('tel_collection')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function gettelField($where,$field)
    {
        return Db::name('tel_collection')->where($where)->value($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function gettelColumn($where = [],$column = 'id'){
        return Db::name('tel_collection')->where($where)->column($column);
    }

    public static function tcountAdd($data)
    {
        return Db::name('tel_count')->strict(false)->insertGetId($data);
    }

    public static function shiwuData($bid,$uid){
        try {
            $biduser = UserModel::userFind(['id'=>$bid]);
            $res1 = UserModel::getuserDec(['id'=>$uid],'count',1);
            //记录次数
            $params = [
                'uid' => $uid,
                'type' => 2,
                'count' => 1,
                'remarks' => '查看'.$biduser['nickname'].'手机号消耗一次次数',
                'create_at' => time()
            ];
            $res2 = self::tcountAdd($params);
            //添加记录
            $TelCollection = self::telFind(['uid'=>$uid,'bid'=>$bid]);
            //如果没有就添加记录
            if(empty($TelCollection)){
                $add['uid'] = $uid;
                $add['bid'] = $bid;
                $add['create_at'] = time();
                $res3 = self::telAdd($add);
                // 提交事务
                Db::commit();
                if(!empty($res1) && !empty($res2) && !empty($res3)){

                }
                return true;
            }
            // 提交事务
            Db::commit();
            if(!empty($res1) && !empty($res2)){

            }
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }
    public static function shiwuDataNew($bid,$uid){
        try {
            $biduser = UserModel::userFind(['id'=>$bid]);
            $res1 = UserModel::getuserDec(['id'=>$uid],'count',1);
            //记录次数
            $params = [
                'uid' => $uid,
                'type' => 2,
                'count' => 1,
                'remarks' => '查看'.$biduser['nickname'].'手机号消耗一次次数',
                'create_at' => time()
            ];
            $res2 = self::tcountAdd($params);
            //查看者
            $add = [];
            $add['uid'] = $uid;
            $add['bid'] = $bid;
            $add['create_at'] = time();
            $res3 = self::telAdd($add);

            //被查看者
            $add = [];
            $add['uid'] = $bid;
            $add['bid'] = $uid;
            $add['type'] = 2;  //被查看者
            $add['is_read'] = 0; //未读
            $add['create_at'] = time();
            $res4 = self::telAdd($add);
            // 提交事务
            Db::commit();
            if(!empty($res1) && !empty($res2) && !empty($res3) && !empty($res4)){

            }
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }
}