<?php


namespace app\api\model;

use think\Model;
use think\Db;
class Collection extends Model
{
    protected $table = 'collection';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function collectionAdd($data)
    {
        return Db::name('collection')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function collectionSelect($where='',$field='',$order='')
    {
        return Db::name('collection')->field($field)->where($where)->order($order)->select();
    }

    public static function collectionSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('collection')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function collectionFind($where='',$field='',$order='')
    {
        return Db::name('collection')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function collectionEdit($where,$data)
    {
        return Db::name('collection')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function collectionCount($where)
    {
        return Db::name('collection')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getcollectionField($where,$field)
    {
        return Db::name('collection')->where($where)->value($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getcollectionColumn($where = [],$column = 'id'){
        return Db::name('collection')->where($where)->column($column);
    }

}