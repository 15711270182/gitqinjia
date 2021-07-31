<?php


namespace app\api\model;

use think\Model;
use think\Db;
class User extends Model
{
    protected $table = 'userinfo';
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function userAdd($data)
    {
        return Db::name('userinfo')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function userSelect($where='',$field='',$order='')
    {
        return Db::name('userinfo')->field($field)->where($where)->order($order)->select();
    }

    public static function userSelectPage($where='',$field='',$order='',$page = '',$pageSize = '')
    {
        return Db::name('userinfo')->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function userFind($where='',$field='',$order='')
    {
        return Db::name('userinfo')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function userEdit($where,$data)
    {
        return Db::name('userinfo')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function userCount($where)
    {
        return Db::name('userinfo')->where($where)->count();
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getuserColumn($where = [],$column = 'id'){
        return Db::name('userinfo')->where($where)->column($column);
    }
    public static function userValue($where = [],$value = 'id'){
        return Db::name('userinfo')->where($where)->value($value);
    }
    public static function getuserInt($where = [],$field = '',$count = '1'){
        return Db::name('userinfo')->where($where)->setInc($field,$count);
    }
    public static function getuserDec($where = [],$field = '',$count = '1'){
        return Db::name('userinfo')->where($where)->setDec($field,$count);
    }

    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function wxFind($where='',$field='',$order='')
    {
        return Db::name('wechat_fans')->field($field)->where($where)->order($order)->find();
    }
    public static function wxEdit($where,$data)
    {
        return Db::name('wechat_fans')->strict(false)->where($where)->update($data);
    }
}