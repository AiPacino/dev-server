<?php
namespace oms\operation;


use zuji\debug\Debug;
use oms\state\State;
use zuji\debug\Location;

/**
 * 门店确认操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class StoreConfirmedOperation implements OrderOperation
{

    private $order_id=0;

    public function __construct( array $data ){
       
        $this->order_id=$data['order_id'];
    }
    
    public function update(){
        
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2');
        
        if(!isset($this->order_id)){
            Debug::error(Location::L_Order, "订单ID错误", $this->order_id);
            return false;
        }
        $data = [
            'status' =>State::StoreConfirmed,
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