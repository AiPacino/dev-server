<?php
namespace zuji\order\refund;

/**
 * 空 退款单
 * <p>订单中不存在退款单时使用：</p>
 * @access public
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
class NullRefund extends Refund{
    
    public function __construct(array $data=[]) {
        parent::__construct($data);
    }
    /**
     * 是否允许 退款
     * @return bool true：允许；false：不允许
     */
    public function allow_to_refund(){
        return false;
    }
    /**
     * 是否允许修改退款额
     */
    public function allow_should_amount(){
        return false;
    }
  
}
