<?php


namespace app\api\model;

use think\Model;
use think\Db;
class Order extends Model
{
    protected $table = 'order';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function orderAdd($data)
    {
        return Db::name('order')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function orderSelect($where='',$field='',$order='')
    {
        return Db::name('order')->field($field)->where($where)->order($order)->select();
    }

    public static function orderSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('order')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function orderFind($where='',$field='',$order='')
    {
        return Db::name('order')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function orderEdit($where,$data)
    {
        return Db::name('order')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function orderCount($where)
    {
        return Db::name('order')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getorderField($where,$field)
    {
        return Db::name('order')->where($where)->value($field);
    }
    /**
     * 查询指定的列的和
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getorderSum($where,$field)
    {
        return Db::name('order')->where($where)->sum($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getorderColumn($where = [],$column = 'id'){
        return Db::name('order')->where($where)->column($column);
    }
}