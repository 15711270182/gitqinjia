<?php


namespace app\api\model;

use think\Model;
use think\Db;
class Video extends Model
{
    protected $table = 'video';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function videoAdd($data)
    {
        return Db::name('video')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function videoSelect($where='',$field='',$order='')
    {
        return Db::name('video')->field($field)->where($where)->order($order)->select();
    }

    public static function videoSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('video')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function videoFind($where='',$field='',$order='')
    {
        return Db::name('video')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function videoEdit($where,$data)
    {
        return Db::name('video')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function videoCount($where)
    {
        return Db::name('video')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getvideoField($where,$field)
    {
        return Db::name('video')->where($where)->value($field);
    }
    /**
     * 查询指定的列的和
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getvideoSum($where,$field)
    {
        return Db::name('video')->where($where)->sum($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getvideoColumn($where = [],$column = 'id'){
        return Db::name('video')->where($where)->column($column);
    }
    public static function getvideoInt($where = [],$field = '',$count = '1'){
        return Db::name('video')->where($where)->setInc($field,$count);
    }
}