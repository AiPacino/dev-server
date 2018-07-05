<?php
namespace zuji\order\returns;

/**
 * 空 退货单
 * <p>订单中不存在退货单时使用：</p>
 * @access public
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
class NullReturns extends Returns{
    
    public function __construct(array $data=[]) {
        parent::__construct($data);
    }
    /**
     * 是否允许 审核
     * @return bool true：允许；false：不允许
     */
    public function allow_to_return(){
        return false;
    }
    /**
     * 是否允许退货
     * return bool true：允许；false：不允许
     */
    public function allow_return(){
        return false;
    }
    /**
     * 是否可以创建收货单
     */
    public function allow_create_receive(){
        return false;
    }

  
}
