<?php
namespace oms\operation;

use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;


/**
 * 门店审核操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class StoreCheckOperation implements OrderOperation
{

    private $order_id=0;
    
    public function __construct( $order_id ){
        
        $this->order_id=$order_id;
    }
    
    public function update(){
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2');
        
        if(!isset($this->order_id)){
            Debug::error(Location::L_Order, "订单ID错误", $this->order_id);
            return false;
        }
        $data = [
            'status' =>State::StorePassed,
            'update_time'=> time(),//更新时间
        ];
        
        $result =$order_table->where(['order_id'=>$this->order_id])->save($data);
        if(!$result){
            set_error("订单审核失败");
            Debug::error(Location::L_Order, "取消审核失败", $data);
            return false;
        }
        return true;
        
    }
}