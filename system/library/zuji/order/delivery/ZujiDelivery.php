<?php
namespace zuji\order\delivery;

use zuji\order\DeliveryStatus;

/**
 * 租机业务 发货单
 * <p>创建发货单的 前置条件：</p>
 * <ul>
 * <li>[租机订单]开启状态</li>
 * <li>[租机支付单]已支付完成</li>
 * </ul>
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ZujiDelivery extends Delivery{
    
    
    /**
     * 是否允许 发货
     * @return bool true：允许；false：不允许
     */
    public function allow_to_deliver(){
	// 未发货时，允许发货
	if( $this->delivery_status < DeliveryStatus::DeliverySend ){
	    return true;
	}
	return false;
    }
    
    /**
     * 是否允许 取消发货
     * @return bool true：允许；false：不允许
     */
    public function allow_to_cancel_delivery() {
	// 未发货时，允许取消发货单
	if( $this->delivery_status < DeliveryStatus::DeliverySend ){
	    return true;
	}
	return false;
    }

    /**
     * 是否允许 确认收货
     * @return bool true：允许；false：不允许
     */
    public function allow_to_confirm_delivery() {
	if( $this->delivery_status == DeliveryStatus::DeliverySend ){
	    return true;
	}
	return false;
    }

    /**
     * 是否允许 客户拒签 操作
     * @return boolean
     */
    public function allow_to_refuse_sign(){
	return false;
    }

    /**
     * 是否允许 生成租机协议 操作
     * @return boolean
     */
    public function allow_to_create_protocol(){
        if( $this->delivery_status == DeliveryStatus::DeliveryWaiting || $this->delivery_status == DeliveryStatus::DeliveryProtocol ){
            return true;
        }
        return false;
    }
    
}
