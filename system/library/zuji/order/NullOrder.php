<?php
namespace zuji\order;


/**
 * 空 订单
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NullOrder extends Order {
    
    public function __construct(array $data=[]) {
	parent::__construct($data);
    }
    
    public function allow_to_cancel() {
	return false;
    }
    public function allow_create_delivery(){
    return false;
    }
    
}
