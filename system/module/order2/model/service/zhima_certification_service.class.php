<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/3/24 0024-下午 5:30
 * @copyright (c) 2017, Huishoubao
 */

class zhima_certification_service extends model
{

    /*
     * 获取芝麻订单信息
     * params[
     *      'order_no'=>'',//芝麻订单号
     *      'out_order_no'=>'',//租机订单号
     * ]
     */
    public function get_zhima_order_one( $where ,$lock = true){
        // 都没有通过过滤器（都被过滤掉了）
        if( count($where)==0 ){
            return false;
        }
        $order_info = $this->field('*')->where($where)->find(['lock'=>$lock]);
        if( !$order_info ){
            return false;
        }
        return $order_info;
    }

}