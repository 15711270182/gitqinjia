<?php


namespace app\api_new\model;

use think\Model;
use think\Db;
class Relation extends Model
{
    protected $table = 'relation';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function relationAdd($data)
    {
        return Db::name('relation')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function relationSelect($where='',$field='',$order='')
    {
        return Db::name('relation')->field($field)->where($where)->order($order)->select();
    }

    public static function relationSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('relation')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function relationFind($where='',$field='',$order='')
    {
        return Db::name('relation')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function relationEdit($where,$data)
    {
        return Db::name('relation')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function relCount($where)
    {
        return Db::name('relation')->where($where)->count();
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getrelationColumn($where = [],$column = 'id'){
        return Db::name('relation')->where($where)->column($column);
    }

}