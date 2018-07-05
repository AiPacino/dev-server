<?php
namespace zuji\order\delivery;

use zuji\order\DeliveryStatus;

/**
 * 换货业务 发货单
 * @access public
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class HuanhuoDelivery extends Delivery{
    
    
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
	// 换货业务的发货单，禁止取消
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
        return false;
    }
    
}
