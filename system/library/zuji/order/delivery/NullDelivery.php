<?php
namespace zuji\order\delivery;

/**
 * 空 发货单
 * <p>订单中不存在发货单时使用：</p>
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NullDelivery extends Delivery{
    
    /**
     * 是否允许 发货
     * @return bool true：允许；false：不允许
     */
    public function allow_to_deliver(){
	return false;
    }
    
    /**
     * 是否允许 取消发货
     * @return bool true：允许；false：不允许
     */
    public function allow_to_cancel_delivery() {
	return false;
    }

    /**
     * 是否允许 确认收货
     * @return bool true：允许；false：不允许
     */
    public function allow_to_confirm_delivery() {
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
