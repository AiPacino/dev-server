<?php
namespace zuji\order\receive;

/**
 * 空 退款单
 * <p>订单中不存在退款单时使用：</p>
 * @access public
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
class NullReceive extends Receive{
    
    public function __construct(array $data=[]) {
        parent::__construct($data);
    }
    /**
     * 是否可以取消收货单
     */
    public function allow_cancel_receive(){
        return false;
    }
    /**
     * 是否可以收货
     */
    public function allow_receive_confirmed(){
        return false;
    }
    
    /**
     * 是否可以生成检测单
     */
    public function allow_create_evaluation(){
        return false;
    }
  
}
