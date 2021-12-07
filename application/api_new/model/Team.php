<?php


namespace app\api_new\model;

use think\Model;
use think\Db;
class Team extends Model
{
    protected $table = 'team';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function teamAdd($data)
    {
        return Db::name('team')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function teamSelect($where='',$field='',$order='')
    {
        return Db::name('team')->field($field)->where($where)->order($order)->select();
    }

    public static function teamSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('team')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function teamFind($where='',$field='',$order='')
    {
        return Db::name('team')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function teamEdit($where,$data)
    {
        return Db::name('team')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function teamCount($where = [])
    {
        return Db::name('team')->where($where)->count();
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getteamColumn($where = [],$column = 'id'){
        return Db::name('team')->where($where)->column($column);
    }

}