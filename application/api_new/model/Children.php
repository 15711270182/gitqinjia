<?php


namespace app\api_new\model;

use think\Model;
use think\Db;
class Children extends Model
{
    protected $table = 'children';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function childrenAdd($data)
    {
        return Db::name('children')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function childrenSelect($where='',$field='',$order='')
    {
        return Db::name('children')->field($field)->where($where)->order($order)->select();
    }

    public static function childrenSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('children')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function childrenFind($where='',$field='',$order='')
    {
        return Db::name('children')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function childrenEdit($where,$data)
    {
        return Db::name('children')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function childrenCount($where)
    {
        return Db::name('children')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getchildrenField($where,$field)
    {
        return Db::name('children')->where($where)->value($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getchildrenColumn($where = [],$column = 'id'){
        return Db::name('children')->where($where)->column($column);
    }

    public static function getchildrenInt($where = [],$field = '',$count = '1'){
        return Db::name('children')->where($where)->setInc($field,$count);
    }
    public static function getchildrenDec($where = [],$field = '',$count = '1'){
        return Db::name('children')->where($where)->setDec($field,$count);
    }
    //处理数据
    public static function getSelect($condition = [],$where_match = '',$field = '',$order='',$page = '',$pageSize = '',$residence = 0,$is_match = 0){
        $query = Db::name('children');
        if($where_match){
            $query->where($where_match);
        }
        if($residence == 0){ //无需匹配城市
           $residence = $condition['residence'];
           unset($condition['residence']);
           $query->where('residence', 'notin', $residence);
        }
        if(!empty($page) && !empty($pageSize)){
            $list = $query->where($condition)->field($field)->order($order)->limit($page,$pageSize)->select();
        }else{
            $list = $query->where($condition)->field($field)->order($order)->select();
        }
        // var_dump(Db::name('children')->getLastsql());die;
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $list[$key]['is_match'] = $is_match;
            }
        }
        return $list;
    }
}