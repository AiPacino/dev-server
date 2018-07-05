<?php

namespace oms\operation;

use oms\Order;
use oms\state\State;
use zuji\Business;
use zuji\Config;
use zuji\coupon\Coupon;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\operator\Operator;
use zuji\order\OrderStatus;

/**
 * 取消订单操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn> 
 *
 */
class CancelOperation implements OrderOperation {

	private $order_id = 0;
	private $reason_id = 0;
	private $reason_text = "";

	public function __construct($data) {
		$this->order_id = $data['order_id'];
		$this->reason_id = isset($data['reason_id']) ? $data['reason_id'] : 0;
		$this->reason_text = isset($data['reason_text']) ? $data['reason_text'] : '';
	}

	public function update() {
		$load = \hd_load::getInstance();
		$order_table = $load->table('order2/order2');
        $order_service =$load->service('order2/order');


		if (!isset($this->order_id)) {
			set_error("订单ID错误");
			return false;
		}
        $order_info =$order_service->get_order_info(['order_id'=>$this->order_id]);
		$old_status =$order_info['status'];

		if($old_status == State::OrderCreated){
            //查询该订单是否有优惠券 有则返还
            $coupon_table = $load->table('order2/order2_coupon');
            $coupon_info =$coupon_table->where(['order_id'=>$this->order_id])->find();
            if($coupon_info){
                $cancel_coupon =Coupon::cancel_coupon($coupon_info['coupon_id']);
                if(!$cancel_coupon){
                    set_error("返还优惠券失败");
                    return false;
                }
            }
        }
		$data = [
			'status' => State::OrderCanceled,
			'reason_id' => $this->reason_id, //取消订单原因id
			'reason_text' => $this->reason_text, //取消订单备注
			'update_time' => time(), //更新时间
			'order_status' => OrderStatus::OrderCanceled,
		];

		$cancel = $order_table->where(['order_id' => $this->order_id])->save($data);
		if ($cancel===false) {
			set_error("订单取消失败");
			return false;
		}

        // 租机业务退款，则 恢复库存数量
        if($order_info['business_key'] == Business::BUSINESS_ZUJI){

            $goods_info =$order_service->get_goods_info($order_info['goods_id']);
            if(!$goods_info){
                set_error("商品信息不存在");
                return false;
            }
            $sku_service =$load->service('goods2/goods_sku');
            $sku_info =$sku_service->api_get_info($goods_info['sku_id'],"");
            //sku库存 +1
            $sku_table =$load->table('goods2/goods_sku');
            $spu_table=$load->table('goods2/goods_spu');

            $sku_data['sku_id'] =$goods_info['sku_id'];
            $sku_data['number'] = ['exp','number+1'];
            $add_sku =$sku_table->save($sku_data);
            if(!$add_sku){
                set_error("恢复商品库存失败");
                return false;
            }
            $spu_data['id'] =$sku_info['spu_id'];
            $spu_data['sku_total'] = ['exp','sku_total+1'];
            $add_spu =$spu_table->save($spu_data);
            if(!$add_spu){
                set_error("恢复总库存失败");
                return false;
            }
        }


        // 取消分期
		$instalment =$load->service('order2/instalment');
        $b = $instalment->cancel_instalment($this->order_id);
        if($b===false){
            set_error("取消分期失败");
            return false;
        }
		return true;
	}

}
