<?php
namespace oms\operation;

use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\RefundStatus;
use zuji\Business;
use zuji\order\ServiceStatus;
use zuji\Config;
use oms\state\State;
use zuji\order\ReturnStatus;
use zuji\order\ReceiveStatus;
use zuji\order\OrderStatus;

/**
 * 取消退货操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class CancelReturnsOperation implements OrderOperation
{

    private $order_id=0;
    private $return_id=0;
    private $receive_id=0;

    public function __construct( $data ){

        $this->order_id=$data['order_id'];
        $this->return_id=$data['return_id'];
        $this->receive_id=isset($data['receive_id'])?$data['receive_id']:0;
    }
    
    public function update(){  
        $load = \hd_load::getInstance();
        $return_table =$load->table('order2/order2_return');
        $order_table = $load->table('order2/order2');
        
        $order_id =$this->order_id;
        $return_id =$this->return_id;
        $receive_id=$this->receive_id;
        $data =[
            'update_time'=>time(),
            'return_status'=>ReturnStatus::ReturnCanceled,
        ];
        $b =$return_table->where(['return_id'=>$return_id])->save($data);
        if(!$b){
            set_error("更新退货单状态失败");
            Debug::error(Location::L_Return, get_error(), $data);
            return false;
        }
        //更新订单
        $order_data = [
            'status' =>State::OrderInService,
            'update_time'=> time(),//更新时间
            'return_status'=> ReturnStatus::ReturnCanceled,//取消退货
        ];
        
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            Debug::error(Location::L_Return,get_error(), $order_data);
            return false;
        }
         
        if($receive_id >0){        
            $receive_data=[
                'update_time'=>time(),
                'receive_status'=>ReceiveStatus::ReceiveCanceled,
            ];
            $receive_table=$load->table('order2/order2_receive');
            $b =$receive_table->where(['receive_id'=>$receive_id])->save($receive_data);
            if(!$b){
                set_error("修改收货单状态失败");
                Debug::error(Location::L_Return, get_error(), $receive_data);
                return false;
            }
        }

        return true;
    }
}