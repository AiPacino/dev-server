<?php
namespace oms\operator;

/**
 * Operator 订单操作员
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
interface Operator {
    
    const Type_User = 2;
    const Type_Admin = 1;
    const Type_System = 3; // 系统自动化任务
    const Type_Store =4;//线下门店
    
    
    /**
     * 操作员类型
     * @return int 
     */
    public function get_type();
    
    /**
     * ID
     * @return int 
     */
    public function get_id();
    
    /**
     * Username
     * @return int 
     */
    public function get_username();


}
