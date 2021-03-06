<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\api\model\Children;
use app\index\service\UsersService;
use library\Controller;
use library\tools\Data;
use think\Db;

/**
 * 代理管理
 * Class Invite
 * @package app\admin\controller
 */
class Invite extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table1 = 'relation';
    public $table2 = 'invite_awards';
    public $table3 = 'children';

    /**
     * 代理用户列表
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '用户列表';
        $query = $this->_query($this->table1)->alias('r')
            ->leftJoin('children p','r.bid= p.uid')
            ->join('userinfo m','r.bid=m.id');
        $field = 'count(*) as info_count,r.create_at,r.bid as uid,m.nickname,m.headimgurl,m.add_time,p.sex,p.year,p.phone,p.province,p.residence,p.status,p.balance,p.withdrawn_amount';
        $query->field($field)
            ->timeBetween('m.add_time#add_time')
            ->equal("r.bid#bid,p.phone#phone,m.nickname#nickname")
            ->where(['r.type'=>0])
            ->group('r.bid')
            ->order('r.create_at desc')
            ->page();
//        var_dump(DB::name('relation')->getLastSql());die;
    }
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
           $vo['addtime'] = date('Y-m-d H:i:s',$vo['create_at']);
            $vo['nickname'] = emojiDecode($vo['nickname']);
            //判断有没有完善孩子资料
            $vo['sex'] = '女';
            if ($vo['sex'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$vo['year'];
            $vo['address'] = $vo['province']. '-' .$vo['residence'];
            $vo['yq_count'] = Db::name('relation')->where(['bid'=>$vo['uid']])->count();
            //邀请的人支付订单数
            $vo['order_count'] = Db::name('invite_awards')->where(['bid'=>$vo['uid'],'status'=>1])->count();
            $vo['balance'] = !empty($vo['balance'])?$vo['balance']/100:0;
            $vo['withdrawn_amount'] = !empty($vo['withdrawn_amount'])?$vo['withdrawn_amount']/100:0;
            $vo['total_money'] = $vo['balance']+$vo['withdrawn_amount'];
        }
    }

    /**
     * @Notes:填写资料列表
     * @Interface info_list
     * @author: zy
     * @Time: 2021/07/29
     */
    public function info_list(){
        $id = input("id", '', 'htmlspecialchars_decode');
        $query = $this->_query($this->table3)->alias('p')
            ->leftJoin('relation r','p.uid=r.uid')
            ->join('userinfo m','r.uid=m.id');
        $field = 'r.uid,m.count,m.nickname,m.headimgurl,m.add_time,p.sex,p.year,p.phone,p.province,p.residence,p.status,p.balance,p.withdrawn_amount';
        $query->field($field)
            ->timeBetween('m.add_time#add_time')
            ->where(['r.bid'=>$id])
            ->order('p.id desc')
            ->page();
    }
    protected function _info_list_page_filter(&$data)
    {
        foreach ($data as &$vo) {
           $vo['addtime'] = date('Y-m-d H:i:s',$vo['add_time']);
            $vo['nickname'] = emojiDecode($vo['nickname']);
            $vo['sex'] = '女';
            if ($vo['sex'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$vo['year'];
            $vo['address'] = $vo['province']. '-' .$vo['residence'];
        }
    }
     /**
     * @Notes:支付订单列表
     * @Interface order_list
     * @author: zy
     * @Time: 2021/07/29
     */
    public function awards_list(){
        $id = input("id", '', 'htmlspecialchars_decode');
        $query = $this->_query($this->table2)->alias('i')
            ->join('children p','i.uid=p.uid')
            ->join('userinfo m','p.uid=m.id');
        $field = 'i.*,m.count,m.nickname,m.headimgurl,m.add_time,p.sex,p.year,p.phone,p.province,p.residence';
        $query->field($field)
            ->timeBetween('m.add_time#add_time')
            ->where(['i.bid'=>$id])
            ->order('i.id desc')
            ->page();
//         var_dump(DB::name('invite_awards')->getLastSql());die;
    }
    protected function _awards_list_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['addtime'] = date('Y-m-d H:i:s',$vo['add_time']);
            $vo['nickname'] = emojiDecode($vo['nickname']);
            $vo['sex'] = '女';
            if ($vo['sex'] == 1){
                $vo['sex'] = '男';
            }
            $vo['age'] = (int)date('Y') - (int)$vo['year'];
            $vo['address'] = $vo['province']. '-' .$vo['residence'];
            $type = $vo['order_type'] == 1?'月卡':'次卡';
            if($vo['status'] == 2){
                $vo['desc'] = '用户提现';
            }else{
                $vo['desc'] = '支付'.$type.($vo['total_money']/100).'元';
            }
        }
    }

    /**
     * @Notes:提现
     * @Interface withdrawal
     * @author: zy
     * @Time: ${DATE}   ${TIME}
     */
    public function withdrawal(){
        $id = input('id');
//        var_dump($id);die;
        $field = 'uid,balance,withdrawn_amount';
        $uinfo = Db::name('children')->where(['uid'=>$id])->field($field)->find();
        $m = Db::name('userinfo')->where(['id'=>$id])->field('nickname,headimgurl')->find();
        $uinfo['nickname'] = $m['nickname'];
        $uinfo['headimgurl'] = $m['headimgurl'];
        $this->vo = $uinfo;
        $this->fetch();
    }

    public function width_save(){
        $id = input('uid');
        $balance = input('balance');
        $tx_money = input('tx_money');
        $remark = input('remark');
        if($tx_money >($balance/100)){
            $this->error('提现金额不能大于余额');
        }
        if($tx_money < 0.3){
            $this->error('提现金额不能小于0.3');
        }  
     
        // 微信打款
        $trade_no = md5(uniqid(mt_rand(), true));
        $openid = DB::name('userinfo')->where(['id'=>$id])->value('openid');
        $WePay = \We::WePayTransfers(config('wechat.auth'));
        // var_dump($openid);die;
        $result_pay = $WePay->create([
            'partner_trade_no' => $trade_no,
            'openid'           => $openid,
            'check_name'       => 'NO_CHECK',
            'amount'           => $tx_money * 100,
            'desc'             => $remark,
            'spbill_create_ip' => request()->ip(),
        ]);
        $result_code = $result_pay['result_code'];
        // var_dump($result_pay);die;
        // $result_code = 'SUCCESS';
        // 添加打款记录
        $insert_map = [];
        $insert_map['uid'] = $id;
        $insert_map['openid'] = $openid;
        $insert_map['trade_no'] = $trade_no;
        $insert_map['amount'] = $tx_money;
        $insert_map['desc'] = $remark;
        $insert_map['create_time'] = date('Y-m-d H:i:m', time());
        $insert_map['status'] = $result_code;
        $insert_map['source'] = 1; //打款类型  邀请奖励
        if ($result_code == 'SUCCESS'){
            $insert_map['mchid'] = $result_pay['mchid'];
            $insert_map['payment_no'] = $result_pay['payment_no'];
            $insert_map['payment_time'] = $result_pay['payment_time'];
            //添加记录
            $res = DB::name('orders_payment')->insertGetId($insert_map);
            // var_dump($res);die;
            $add_map = [];
            $add_map['uid'] = $id;
            $add_map['bid'] = $id;
            $add_map['pay_time'] = date('Y-m-d H:i:s');
            $add_map['total_money'] = $balance;
            $add_map['awards_money'] = $tx_money*100;
            $add_map['status'] = 2; //提现
            $add_map['remark'] = $remark;
            $add_map['create_time'] = date('Y-m-d H:i:s');
            $res1 = Db::name('invite_awards')->insertGetId($add_map);
            $res2 = Db::name('children')->where(['uid'=>$id])->setDec('balance',$add_map['awards_money']);
            $res3 = Db::name('children')->where(['uid'=>$id])->setInc('withdrawn_amount',$add_map['awards_money']);
            if($res && $res1 && $res2 && $res3){

                $this->success('提现成功','javascript:history.back()');
            }   
        }
        $insert_map['err_code'] = $result_pay['err_code'];
        $insert_map['err_code_des'] = $result_pay['err_code_des'];
        DB::name('orders_payment')->insertGetId($insert_map);
        $this->error('提现失败');
       
    }
}
