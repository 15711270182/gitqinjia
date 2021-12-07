<?php


namespace app\api_new\model;

use think\Model;
use think\Db;
class WeightScore extends Model
{
     /**
     * 添加
     * @param $data
     * @return int|string
     */
    public static function scoreRecordAdd($data)
    {
        return Db::name('weight_score_record')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function scoreRecordSelect($where='',$field='',$order='')
    {
        return Db::name('weight_score_record')->field($field)->where($where)->order($order)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function scoreRecordFind($where='',$field='',$order='')
    {
        return Db::name('weight_score_record')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function scoreRecordEdit($where,$data)
    {
        return Db::name('weight_score_record')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function scoreRecordCount($where)
    {
        return Db::name('weight_score_record')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getScoreRecordField($where,$field)
    {
        return Db::name('weight_score_record')->where($where)->value($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getScoreRecordColumn($where = [],$column = 'id'){
        return Db::name('weight_score_record')->where($where)->column($column);
    }

     /** 添加
     * @param $data
     * @return int|string
     */
    public static function scoreTypeAdd($data)
    {
        return Db::name('weight_score_type')->strict(false)->insertGetId($data);
    }
    /**
     * 查询关系列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function scoreTypeSelect($where='',$field='',$order='')
    {
        return Db::name('weight_score_type')->field($field)->where($where)->order($order)->select();
    }
    /**
     * 查询单条
     * @param string $where
     * @param string $field
     * @param string $order
     * @return int|string
     */
    public static function scoreTypeFind($where='',$field='',$order='')
    {
        return Db::name('weight_score_type')->field($field)->where($where)->order($order)->find();
    }
    /**
     * 修改
     * @param $where
     * @param $data
     * @return int|string
     */
    public static function scoreTypeEdit($where,$data)
    {
        return Db::name('weight_score_type')->strict(false)->where($where)->update($data);
    }
    /**
     * 数量
     * @param $where
     * @return int|string
     */
    public static function scoreTypeCount($where)
    {
        return Db::name('weight_score_type')->where($where)->count();
    }
    /**
     * 查询指定的列的值
     * @param $where
     * @param $field
     * @return mixed
     */
    public static function getScoreTypeField($where,$field)
    {
        return Db::name('weight_score_type')->where($where)->value($field);
    }
    /**
     * 根据条件获取关联数组
     * @param array $where
     * @param string $column
     * @return array\
     */
    public static function getScoreTypeColumn($where = [],$column = 'id'){
        return Db::name('weight_score_type')->where($where)->column($column);
    }
}