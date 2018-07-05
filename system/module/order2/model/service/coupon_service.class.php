<?php
use zuji\order\Order;
/**
 * 	优惠券服务层
 */
class coupon_service extends service {


    public function _initialize() {
        /* 实例化数据层 */
        $this->coupon_table = $this->load->table('order2/order2_coupon');
    }

    /**
     * 订单详情 -- 优惠券信息
     * @params int    $order_id	订单ID
     * @params array  $additional	条件
     * @return array  $coupon_result
     */
    public function get_info($order_id, $additional=[]){
        // 校验
        if($order_id < 1){
            set_error('订单ID参数错误');
            return false;
        }

        $result = $this->coupon_table->get_info( $order_id,$additional);
        if($result){
            $result['discount_amount'] = Order::priceFormat($result['discount_amount']/100);
        }

        return $result;
    }



}
