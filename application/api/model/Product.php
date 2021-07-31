<?php


namespace app\api\model;

use think\Model;
use think\Db;
class Product extends Model
{
    protected $table = 'product';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function productAdd($data)
    {
        return Db::name('product')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function productSelect($where='',$field='',$order='')
    {
        return Db::name('product')->field($field)->where($where)->order($order)->select();
    }

    public static function productSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('product')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function productFind($where='',$field='',$order='')
    {
        return Db::name('product')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function productEdit($where,$data)
    {
        return Db::name('product')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function productCount($where)
    {
        return Db::name('product')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getproductField($where,$field)
    {
        return Db::name('product')->where($where)->value($field);
    }
    /**
     * 查询指定的列的和
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getproductSum($where,$field)
    {
        return Db::name('product')->where($where)->sum($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getproductColumn($where = [],$column = 'id'){
        return Db::name('product')->where($where)->column($column);
    }
}