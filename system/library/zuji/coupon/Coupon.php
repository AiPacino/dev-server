<?php

/**
 * 优惠券
 * @access public 
 * @author wangjinlin
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\coupon;

/**
 * Coupon 优惠券
 *
 * @author wangjinlin
 */

use zuji\debug\Debug;
use zuji\debug\Location;

class Coupon {

    /*
     * 调用生成一个无门槛优惠券
     *  参数 $row[
     *          user_id,用户ID
     *          only_id,唯一标识,对外的
     *      ]
     *  返回参数
     *          返回一维数组 [code,data]
     *          code=0错误,1正确;data=提示 (如果成功会返回coupon_no)
     */
    public static function set_coupon_user(array $row):array {
        if(!$row['user_id']){return ['code'=>0,'data'=>'参数错误'];}
        //获取用户ID
        $user_id = $row['user_id'];
        //生成对应该用户优惠券
        $start_time = strtotime(CouponStatus::CouponStartDate);
        $end_time = strtotime(CouponStatus::CouponEndDate);
        $load = \hd_load::getInstance();
        $coupon_table= $load->table('coupon/coupon');
        $coupon_type_table = $load->table('coupon/coupon_type');

        $row_type = $coupon_type_table->field('id')->where(['only_id'=>$row['only_id']])->find();
        if(!$row_type){
            return ['code'=>0,'data'=>'无该优惠券类型'];
        }

        $where = ['coupon_type_id'=>$row_type['id'],'status'=>CouponStatus::CouponStatusNotUsed,'user_id'=>$user_id];
        if($coupon_table->field('id')->where($where)->find()){
            return ['code'=>0,'data'=>'已领取过该优惠券'];
        }

        $trans = $coupon_table->startTrans();
        if (!$trans) {
            Debug::error(Location::L_Member, '系统繁忙', '调用生成一个绑定用户的默认优惠码,开启事物失败');
            return ['code'=>0,'data'=>'系统繁忙'];
        }
        $row = [
            'coupon_type_id'    =>$row_type['id'],
            'coupon_no'       =>CouponFunction::md5_16(),
            'status'            =>0,
            'start_time'        => $start_time,
            'end_time'          => $end_time,
            'user_id'           =>$user_id
        ];
        try{
            if(!$coupon_table->add($row)){
                $coupon_table->rollback();
                Debug::error(Location::L_Member, '系统自动生成优惠券失败', '系统自动生成优惠券失败,coupon_no:'.$row['coupon_no']);
                return ['code'=>0,'data'=>'系统自动生成优惠券失败'];
                //exit('系统自动生成优惠券失败,coupon_no:'.$row['coupon_no']);
            }
            $coupon_table->commit();
        }catch (\Exception $exc){
            $coupon_table->rollback();
            Debug::error(Location::L_Member, '系统自动生成优惠券失败', '系统自动生成优惠券失败');
            return ['code'=>0,'data'=>'系统自动生成优惠券失败'];
        }
        return ['code'=>1,'data'=>'领取成功','coupon_no'=>$row['coupon_no']];
    }

    /*
     * 调用生成一个无门槛优惠券
     *  参数 $row[
     *          user_id,用户ID
     *          only_id,唯一标识,对外的
     *      ]
     *  返回参数
     *          返回一维数组 [code,data]
     *          code=0错误,1正确;data=提示
     */
    public static function set_coupon_user2(array $row):array {
        if(!$row['user_id']){return ['code'=>0,'data'=>'参数错误'];}
        //获取用户ID
        $user_id = $row['user_id'];
        //生成对应该用户优惠券
        $start_time = strtotime('2018-02-14');
        $end_time = strtotime('2018-02-24');
        $load = \hd_load::getInstance();
        $coupon_table= $load->table('coupon/coupon');
        $coupon_type_table = $load->table('coupon/coupon_type');

        $row_type = $coupon_type_table->field('id')->where(['only_id'=>$row['only_id']])->find();
        if(!$row_type){
            return ['code'=>0,'data'=>'无该优惠券类型'];
        }

        $where = ['coupon_type_id'=>$row_type['id'],'status'=>CouponStatus::CouponStatusNotUsed,'user_id'=>$user_id];
        if($coupon_table->field('id')->where($where)->find()){
            return ['code'=>0,'data'=>'已领取过该优惠券'];
        }

        $trans = $coupon_table->startTrans();
        if (!$trans) {
            Debug::error(Location::L_Member, '系统繁忙', '调用生成一个绑定用户的默认优惠码,开启事物失败');
            return ['code'=>0,'data'=>'系统繁忙'];
        }
        $row = [
            'coupon_type_id'    =>$row_type['id'],
            'coupon_no'       =>CouponFunction::md5_16(),
            'status'            =>0,
            'start_time'        => $start_time,
            'end_time'          => $end_time,
            'user_id'           =>$user_id
        ];
        try{
            if(!$coupon_table->add($row)){
                $coupon_table->rollback();
                Debug::error(Location::L_Member, '系统自动生成优惠券失败', '系统自动生成优惠券失败,coupon_no:'.$row['coupon_no']);
                return ['code'=>0,'data'=>'系统自动生成优惠券失败'];
                //exit('系统自动生成优惠券失败,coupon_no:'.$row['coupon_no']);
            }
            $coupon_table->commit();
        }catch (\Exception $exc){
            $coupon_table->rollback();
            Debug::error(Location::L_Member, '系统自动生成优惠券失败', '系统自动生成优惠券失败');
            return ['code'=>0,'data'=>'系统自动生成优惠券失败'];
        }
        return ['code'=>1,'data'=>'领取成功'];
    }

    /*
     * 获取优惠券列表
     *      通过优惠券列表
     *      参数
     *          $user_id 用户ID
     *          $type 1全部,2失效和已使用,3未使用
     *      返回 array 类型 未查到返回空数组 []
     *          [
     *              id,优惠券ID
     *              coupon_type_id,优惠券类型ID
     *              coupon_no,优惠卷码
     *              status,状态
     *              start_time,开始时间
     *              end_time,结束时间
     *              user_id,用户ID
     *              coupon_name,优惠券名称
     *              coupon_type,优惠券类型
     *              coupon_value,优惠券金额;根据优惠类型填写的值。如果是固定金额单位是分
     *              range,优惠范围
     *              range_value,优惠范围值
     *              mode,优惠方式
     *              describe,优惠券描述
     *              use_restrictions,使用限制
     *              range_text,优惠券使用范围场景
     *          ]
     */
    public static function coupon_list(int $user_id,int $type):array
    {
        if (!$user_id || !$type) {
            return [];
        }
        $load = \hd_load::getInstance();
        $coupon_table = $load->table('coupon/coupon');
        $coupon_type_table = $load->table('coupon/coupon_type');
        //通过用户ID获取优惠券列表
        if ($type == 1) {
            $sql = "SELECT c.id,c.coupon_type_id,c.coupon_no,c.status,c.start_time,c.end_time,c.user_id,ct.coupon_name,ct.coupon_type,ct.coupon_value,ct.range,ct.range_value,ct.mode,ct.describe,ct.use_restrictions FROM " . $coupon_table->getTableName() . " AS c left join " . $coupon_type_table->getTableName() . " AS ct ON c.coupon_type_id=ct.id where c.user_id=" . $user_id;
        }elseif($type == 2){
            $sql = "SELECT c.id,c.coupon_type_id,c.coupon_no,c.status,c.start_time,c.end_time,c.user_id,ct.coupon_name,ct.coupon_type,ct.coupon_value,ct.range,ct.range_value,ct.mode,ct.describe,ct.use_restrictions FROM " . $coupon_table->getTableName() . " AS c left join " . $coupon_type_table->getTableName() . " AS ct ON c.coupon_type_id=ct.id where c.user_id=" . $user_id." AND (c.status<>0 or c.end_time<".time().")";
        }elseif($type == 3){
            $t = time();
            $sql = "SELECT c.id,c.coupon_type_id,c.coupon_no,c.status,c.start_time,c.end_time,c.user_id,ct.coupon_name,ct.coupon_type,ct.coupon_value,ct.range,ct.range_value,ct.mode,ct.describe,ct.use_restrictions FROM " . $coupon_table->getTableName() . " AS c left join " . $coupon_type_table->getTableName() . " AS ct ON c.coupon_type_id=ct.id where c.user_id=" . $user_id." AND c.status=0 AND c.end_time>".$t." AND c.start_time<".$t;
        }else{
            return [];
        }
        $list = $coupon_table->query($sql);
        if(!$list){return [];}
        foreach ($list as $key=>$value){
            $list[$key]['start_time'] = date('Y-m-d',$value['start_time']);
            $list[$key]['end_time'] = date('Y-m-d',$value['end_time']);
            $list[$key]['coupon_value'] /= 100;
            $list[$key]['range_text'] = CouponFunction::get_range_text($value);
            if($value['status']==0){
                if($value['end_time']>time()){
                    $list[$key]['status_text'] = '去使用';
                }
                else{
                    $list[$key]['status_text'] = '已过期';
                    $list[$key]['status'] = 2;
                }
            }elseif($value['status']==1){
                $list[$key]['status_text'] = '已使用';
            }else{
                $list[$key]['status_text'] = '已过期';
                $list[$key]['status'] = 2;
            }

        }
        return $list;
    }
    /*
     * 用户绑定优惠券
     *      参数
     *          $user_id 用户ID
     *          $coupon_no 优惠码
     *      返回参数
     *          返回一维数组 [code,data]
     *          code=0错误,1正确;data=提示
     */
    public static function bingding(int $user_id,string $coupon_no):array {
        if(!$user_id || !$coupon_no){return ['code'=>0,'data'=>'参数错误'];}
        $load = \hd_load::getInstance();
        $coupon_table = $load->table('coupon/coupon');
        //获取用户ID
//        $user_id = 7;//$_GET['user_id'];
        //获取优惠码
//        $coupon_no = 'a45a571a0d73cd0b';
        //验证优惠码格式
        if(!CouponFunction::validation_code($coupon_no)){
            return ['code'=>0,'data'=>'优惠码格式错误'];
        }
        $coupon_row = $coupon_table->field('id')->where("coupon_no='".$coupon_no."' AND status=0")->find();
        if(!$coupon_row){
            return ['code'=>0,'data'=>'兑换码不存在'];
        }
        if($coupon_row['user_id']>0){
            return ['code'=>0,'data'=>'兑换码已失效'];
        }
        $trans = $coupon_table->startTrans();
        if (!$trans) {
            Debug::error(Location::L_Member, '事物开启失败', '用户绑定优惠券事物开启失败');
            return ['code'=>0,'data'=>'服务繁忙'];
        }
        try{
            if(!$coupon_table->where(['id'=>$coupon_row['id']])->save(['user_id'=>$user_id])){
                $coupon_table->rollback();
                Debug::error(Location::L_Member, '用户绑定优惠券失败', '用户绑定优惠券失败,coupon_no:'.$coupon_row['coupon_no']);
                return ['code'=>0,'data'=>'用户绑定优惠券失败'];
                //exit('系统自动生成优惠券失败,coupon_no:'.$row['coupon_no']);
            }
            $coupon_table->commit();
        }catch (\Exception $exc){
            $coupon_table->rollback();
            Debug::error(Location::L_Member, '用户绑定优惠券失败', '用户绑定优惠券失败');
            return ['code'=>0,'data'=>'用户绑定优惠券失败'];
        }
        return ['code'=>1,'data'=>'执行成功'];
    }

    /*
     * 默认选择最高优惠码
     *      $order_row[
     *          'spu_id',
     *          'user_id',
     *          'payment',//单位分
     *          'sku_id',//
     *      ]
     *      返回
     *          错误情况 [code=>0,data=>错误信息]
     *          正确情况 [code=>1,data=>[
     *                      [
     *                          youhui,//优惠金额单位分
     *                          id,//优惠券ID
     *                          coupon_type_id,//优惠券类型ID
     *                          coupon_no,//优惠券码
     *                          range_text,//使用范围
     *                          coupon_name,//优惠券名称
     *                          coupon_type,//类型名称
     *                      ]
     *                  ]]
     */
    public static function get_coupon(array $order_row=[]):array {
        if(!$order_row){return ['code'=>0,'data'=>'参数不能为空'];}
        $load = \hd_load::getInstance();
        $coupon_table= $load->table('coupon/coupon');
        $coupon_type_table = $load->table('coupon/coupon_type');
        $goods_spu_table = $load->table('goods/goods_spu');

        //获取商品ID
        $spu_id = $order_row['spu_id'];//11;
        //获取用户信息
        $user_id = $order_row['user_id'];//7;

        //当前时间戳
        $t = time();
        //品牌id,渠道id,分类id
        $goods_row = $goods_spu_table->field('id as spu_id,brand_id,channel_id,catid')->find($spu_id);
        if(!$goods_row){return ['code'=>0,'data'=>'spu_id 对应信息未找到'];}
        //获取订单金额(需要实际支付金额)
        $goods_row['payment'] = $order_row['payment'];
        //设置SKUID
        $goods_row['sku_id'] = $order_row['sku_id'];
        //Debug::error(Location::L_Member, '默认选择最高优惠券', $spu_id.'-'.$user_id.'-'.$goods_row['payment'].'-'.$goods_row['sku_id']);
        //查询用户下优惠码
        $sql = "SELECT id,coupon_type_id,coupon_no FROM ".$coupon_table->getTableName()." WHERE  user_id=".$user_id." AND status=0 AND start_time<".$t." AND end_time>".$t." group by coupon_type_id ";
        $list = $coupon_table->query($sql);
        if(!$list){
            return ['code'=>0,'data'=>'该用户没有优惠券'];
        }
        $data=$n=$d=[];
        foreach ($list as $k=>$val){
            $row = $coupon_type_table->where(['id'=>$val['coupon_type_id']])->find();
            if(!$row){
                $value = '优惠券类型错误,跳过本次循环.coupon_type_id:'.$val['coupon_type_id'];
                Debug::error(Location::L_Member, '优惠券类型错误', $value);
                continue;
            }
            $coupon_data = CouponFunction::get_range_validate($row,$goods_row);
            if($coupon_data['youhui']){
                $youhui = $coupon_data['youhui']/100;//单位分
                $n[$youhui] = $youhui;
                $d[$youhui] = $val;
                $d[$youhui]['youhui']=$youhui;
                $d[$youhui]['range_text']=$coupon_data['range_text'];//使用范围
                $d[$youhui]['coupon_name'] = $row['coupon_name'];
                $d[$youhui]['coupon_type'] = CouponStatus::get_coupon_type_name($row['coupon_type']);
            }
        }
        rsort($n);
        foreach ($n as $v){
            $data[] = $d[$v];
        }
        return ['code'=>1,'data'=>$data];
    }
    /*
     * 取消回退优惠券
     *      传惨
     *          $coupon_id 优惠券ID
     *      返回参数
     *          bool 成功true,错误false
     */
    public static function cancel_coupon(int $coupon_id):bool {
        if(!$coupon_id){return false;}
        $load = \hd_load::getInstance();
        $coupon_table= $load->table('coupon/coupon');
        if($coupon_table->where(['id'=>$coupon_id])->save(['status'=>CouponStatus::CouponStatusNotUsed])){
            return true;
        }else{
            return false;
        }
    }
    /*
     * 通过优惠码获取优惠金额和信息
     *      传参 $order_row[
     *          'coupon_no',//优惠码
     *          'user_id',//用户ID
     *          'spu_id',//商品ID
     *          'sku_id',//子商品ID
     *          'payment',//商品价格
     *      ]
     *      返回
     *          错误情况 [code=0,data=错误信息]
     *          正确情况 [code=1,data[
     *                      coupon_no,//优惠码
     *                      coupon_id,//优惠券ID
     *                      discount_amount,
     *                      coupon_type,//类型
     *                      coupon_name,//优惠券名称
     *                  ]]
     */
    public static function get_coupon_row(array $order_row):array {
        if(!$order_row){return ['code'=>0,'data'=>'参数不能为空'];}
        $load = \hd_load::getInstance();
        $coupon_table = $load->table('coupon/coupon');
        $coupon_type_table = $load->table('coupon/coupon_type');
        $goods_spu_table = $load->table('goods/goods_spu');
        //优惠码
        $coupon_no = $order_row['coupon_no'];
        //用户ID
        $user_id = $order_row['user_id'];//7;
        //spu_id
        $spu_id = $order_row['spu_id'];
        //当前时间戳
        $t = time();
        //品牌id,渠道id,分类id
        $goods_row = $goods_spu_table->field('id as spu_id,brand_id,channel_id,catid')->find($spu_id);
        if(!$goods_row){return ['code'=>0,'data'=>'spu_id 对应信息未找到'];}
        //获取订单金额(需要实际支付金额)
        $goods_row['payment'] = $order_row['payment'];//70000;
        //设置SKUID
        $goods_row['sku_id'] = $order_row['sku_id'];
        //通过优惠码获取优惠券
        $where = "coupon_no='".$coupon_no."' AND user_id=".$user_id." AND status=0 AND start_time<".$t." AND end_time>".$t;
        $coupon_row = $coupon_table->where($where)->find();
        if(!$coupon_row){return ['code'=>0,'data'=>'优惠券已过期或已使用'];}

        $row = $coupon_type_table->where(['id'=>$coupon_row['coupon_type_id']])->find();
        if(!$row){
            $value = '优惠券类型错误,跳过本次循环.coupon_type_id:'.$coupon_row['coupon_type_id'];
            Debug::error(Location::L_Member, '优惠券类型错误', $value);
            return ['code'=>0,'data'=>'优惠券类型错误'];
        }
        $coupon_data = CouponFunction::get_range_validate($row,$goods_row);

        $data['coupon_no']=$coupon_row['coupon_no'];//优惠卷码
        $data['coupon_id']=$coupon_row['id'];
        $data['discount_amount']=$coupon_data['youhui'];//单位分
//        $data['range_text']=$coupon_data['range_text'];//使用范围
        $data['coupon_type']=$row['coupon_type'];
        $data['coupon_name']=$row['coupon_name'];

        return ['code'=>1,'data'=>$data];
    }

    /*
     * 更改优惠券已使用状态
     *      传参
     *          coupon_id,//优惠券ID
     *      返回
     *          bool
     */
    public static function set_coupon_status(int $coupon_id):bool {
        if(!$coupon_id){return false;}
        $load = \hd_load::getInstance();
        $coupon_table = $load->table('coupon/coupon');
        if($coupon_table->where(['id'=>$coupon_id])->save(['status'=>CouponStatus::CouponStatusAlreadyUsed])){
            return true;
        }else{
            Debug::error(Location::L_Member, '优惠券', '修改优惠券已使用状态失败');
            return false;
        }
    }

    /*
     * 验证优惠券
     *      参数 $order_row[
     *          'coupon_no',//优惠码
     *          'user_id',//用户ID
     *      ]
     *      返回 bool true验证通过,false验证失败
     */
    public static function validate_coupon(array $row):bool {
        if(!$row){return false;}
        if(!CouponFunction::validation_code($row['coupon_no'])){return false;}
        $load = \hd_load::getInstance();
        $coupon_table = $load->table('coupon/coupon');
        //优惠码
        $coupon_no = $row['coupon_no'];
        //用户ID
        $user_id = $row['user_id'];//7;
        //当前时间戳
        $t = time();
        //通过优惠码获取优惠券
        $where = "coupon_no='".$coupon_no."' AND user_id=".$user_id." AND status=0 AND start_time<".$t." AND end_time>".$t;
        $coupon_row = $coupon_table->field("id,coupon_type_id,coupon_no")->where($where)->find();
        if($coupon_row){ return true;}
        else{ return false;}
    }
}
