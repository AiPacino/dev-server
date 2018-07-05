<?php

/**
 * 优惠券方法类
 * @access public 
 * @author wangjinlin
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\coupon;

/**
 * CouponFunction 优惠券方法类
 *
 * @author wangjinlin
 */
class CouponFunction {

    /*
     * 优惠范围转换
     */
    public static function get_range_text(array $row):string {
        $range_text = '';
        if($row['range']==CouponStatus::RangeAll){
            return '全场通用';
        }
        //指定商品spu ID
        elseif($row['range']==CouponStatus::RangeSpu){
            $load = \hd_load::getInstance();
            $goods_spu_table = $load->table('goods/goods_spu');
            $goods_list = $goods_spu_table->field('name')->where("id in (".substr($row['range_value'],0,-1).")")->select();
            foreach ($goods_list as $k=>$val){
                $range_text .= $val['name'].',';
            }
            if(!$range_text){
                return '指定商品未查到';
            }else{
                return substr($range_text,0,-1);
            }
        }
        //新机范围
        elseif ($row['range']==CouponStatus::RangeNew){
            return '新机可用';
        }
        //二手手机
        elseif ($row['range']==CouponStatus::RangeOld){
            return '二手手机可用';
        }
        //手机类别
        elseif ($row['range']==CouponStatus::RangeType){
            $load = \hd_load::getInstance();
            $brand_table = $load->table('goods/brand');
            $brand_list = $brand_table->field('name')->where("id in (".substr($row['range_value'],0,-1).")")->select();
            foreach ($brand_list as $k=>$val){
                $range_text .= $val['name'].',';
            }
            if(!$range_text){
                return '指定手机类别未查到';
            }else{
                return substr($range_text,0,-1);
            }
        }
        //渠道
        elseif ($row['range']==CouponStatus::RangeChannel){
            $load = \hd_load::getInstance();
            $channel_table = $load->table('channel/channel');
            $channel_list = $channel_table->field('name')->where("id in (".substr($row['range_value'],0,-1).")")->select();
            foreach ($channel_list as $k=>$val){
                $range_text .= $val['name'].',';
            }
            if(!$range_text){
                return '指定渠道未查到';
            }else{
                return substr($range_text,0,-1);
            }
        }
        //指定商品sku ID
        elseif($row['range']==CouponStatus::RangeSku){
            $load = \hd_load::getInstance();
            $goods_sku_table = $load->table('goods/goods_sku');
            $goods_list = $goods_sku_table->field('sku_name')->where("sku_id in (".substr($row['range_value'],0,-1).")")->select();
            foreach ($goods_list as $k=>$val){
                $range_text .= $val['sku_name'].',';
            }
            if(!$range_text){
                return '指定商品规格未查到';
            }else{
                return substr($range_text,0,-1);
            }
        }
        return '优惠范围类型未找到';
    }

    /*
     * 优惠范围验证
     *  参数
     *      $row 优惠卷信息
     *      $goods[
     *          spu_id,
     *          channel_id,渠道ID
     *          brand_id,类别ID
     *          新机,二手机
     *          payment,商品价格单位分
     *      ]
     *
     */
    public static function get_range_validate(array $row,array $goods):array {
        if($row['range']==CouponStatus::RangeAll){
            $n = self::x_coupon($row,$goods);
            return ['range_text'=>'全场通用','youhui'=>$n];
        }
        //指定商品spu ID
        elseif($row['range']==CouponStatus::RangeSpu){
            return self::validate_spu($row,$goods);
        }
        //新机范围
        elseif ($row['range']==CouponStatus::RangeNew){
            return ['range_text'=>'目前无新机范围','youhui'=>0];
        }
        //二手手机
        elseif ($row['range']==CouponStatus::RangeOld){
            return ['range_text'=>'目前无二手机范围','youhui'=>0];
        }
        //手机类别
        elseif ($row['range']==CouponStatus::RangeType){
            return self::validate_brand($row,$goods);
        }
        //渠道
        elseif ($row['range']==CouponStatus::RangeChannel){
            return self::validate_channel($row,$goods);
        }
        //指定商品sku ID
        elseif($row['range']==CouponStatus::RangeSku){
            return self::validate_sku($row,$goods);
        }
        return ['range_text'=>'优惠范围类型未找到','youhui'=>0];
    }

    /*
     * 判断是否指定商品(SPU)
     */
    private function validate_spu(array $row,array $goods):array {
        if(strstr($row['range_value'],$goods['spu_id'].',')){
//            $range_text='';
            $load = \hd_load::getInstance();
            $goods_spu_table = $load->table('goods/goods_spu');
            $goods_list = $goods_spu_table->field('name')->where("id in (".substr($row['range_value'],0,-1).")")->select();
//            foreach ($goods_list as $k=>$val){
//                $range_text .= $val['name'].',';
//            }
            if($goods_list){
//                return substr($range_text,0,-1);
                $n = self::x_coupon($row,$goods);
                return ['range_text'=>'指定商品','youhui'=>$n];
            }else{
                return ['range_text'=>'指定商品未查到','youhui'=>0];
            }
        }else{
            return ['range_text'=>'指定商品未查到','youhui'=>0];
        }
    }

    /*
     * 判断是否指定子商品(SKU)
     */
    private function validate_sku(array $row,array $goods):array {
        if(strstr($row['range_value'],$goods['sku_id'].',')){
            $load = \hd_load::getInstance();
            $goods_sku_table = $load->table('goods/goods_sku');
            $goods_list = $goods_sku_table->field('sku_name')->where("sku_id in (".substr($row['range_value'],0,-1).")")->select();
            if($goods_list){
                $n = self::x_coupon($row,$goods);
                return ['range_text'=>'指定商品规格','youhui'=>$n];
            }else{
                return ['range_text'=>'指定商品规格未查到','youhui'=>0];
            }
        }else{
            return ['range_text'=>'指定商品规格未查到','youhui'=>0];
        }
    }

    /*
     * 判断是否指定渠道
     */
    private function validate_channel(array $row,array $goods):array {
        if(strstr($row['range_value'],$goods['channel_id'].',')){
            $load = \hd_load::getInstance();
            $channel_table = $load->table('channel/channel');
            $channel_list = $channel_table->field('name')->where("id in (".substr($row['range_value'],0,-1).")")->select();
            if($channel_list){
                $n = self::x_coupon($row,$goods);
                return ['range_text'=>'指定渠道','youhui'=>$n];
            }else{
                return ['range_text'=>'指定渠道未查到','youhui'=>0];
            }
        }else{
            return ['range_text'=>'指定渠道未查到','youhui'=>0];
        }
    }

    /*
     * 判断是否指定手机品牌类别
     */
    private function validate_brand(array $row,array $goods):array {
        if(strstr($row['range_value'],$goods['brand_id'].',')){
            $load = \hd_load::getInstance();
            $brand_table = $load->table('goods/brand');
            $brand_list = $brand_table->field('name')->where("id in (".substr($row['range_value'],0,-1).")")->select();
            if($brand_list){
                $n = self::x_coupon($row,$goods);
                return ['range_text'=>'指定手机类别','youhui'=>$n];
            }else{
                return ['range_text'=>'指定手机类别未查到','youhui'=>0];
            }
        }else{
            return ['range_text'=>'指定手机类别未查到','youhui'=>0];
        }
    }

    /*
     * 判断限额
     *      返回优惠额 单位分
     */
    public static function x_coupon($row,$goods){
        if($row['use_restrictions']){
            if($row['use_restrictions']<$goods['payment']){
                return self::y_coupon($row,$goods);
            }else{
                return 0;
            }
        }else{
            return self::y_coupon($row,$goods);
        }
    }

    /*
     * 获取优惠额
     */
    private function y_coupon($row,$goods){
        if($row['coupon_type']==CouponStatus::CouponTypeFixed){
            return self::g_coupon($row);
        }elseif ($row['coupon_type']==CouponStatus::CouponTypePercentage){
            return self::z_coupon($row,$goods);
        }elseif ($row['coupon_type']==CouponStatus::CouponTypeFirstMonthRentFree){
            return self::f_coupon($goods);
        }else{
            return 0;
        }
    }
    /*
     * 获取固定优惠额
     */
    private function g_coupon($row){
        return $row['coupon_value'];
    }
    /*
     * 获取折扣优惠额
     */
    private function z_coupon($row,$goods){
        return ($row['coupon_value']*$goods['payment'])/100;
    }
    /*
     * 获取首月0租金优惠额度 单位分
     */
    private function f_coupon($goods){
        $load = \hd_load::getInstance();
        $goods_sku_table = $load->table('goods/goods_sku');
        $sku_row = $goods_sku_table->field('shop_price')->find($goods['sku_id']);
        if($sku_row){
            return $sku_row['shop_price']*100;
        }else{
            return 0;
        }
    }

    /*
     * 使用限制
     *
     * $m   订单金额(分)需要实际支付金额
     * $n   限制金额(分)0不限制
     */
    private function xianzhi($m,$n=CouponStatus::UseRestrictionsNo){
        if($n==CouponStatus::UseRestrictionsNo){
            return true;
        }else{
            if($m>$n){
                return true;
            }else{
                return false;
            }
        }
    }

    /*
     * 获取16位 md5
     */
    public static function md5_16(){
        return substr(md5(self::uuid()),8,16);
    }
    /*
     * 获取uuid
     */
    public static function get_uuid(){
        return self::uuid();
    }
    /*
     * 验证优惠码
     */
    public static function validation_code($code){
        return preg_match("/^[a-zA-Z0-9]+$/",$code);;
    }

    /**
     * Generates an UUID
     *
     * @param      string  an optional prefix
     * @return     string  the formatted uuid
     */
    private function uuid($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,8);
        $uuid .= substr($chars,8,4);
        $uuid .= substr($chars,12,4);
        $uuid .= substr($chars,16,4);
        $uuid .= substr($chars,20,12);
        return $prefix . $uuid;
    }

}
