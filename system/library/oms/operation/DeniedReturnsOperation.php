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
 * 拒绝退货操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class DeniedReturnsOperation implements OrderOperation
{

    private $order_id=0;
    private $return_id=0;
    private $admin_id =0;
    private $return_check_remark="";

    public function __construct( $data ){

        $this->return_id=$data['return_id'];
        $this->order_id=$data['order_id'];
        $this->return_check_remark =$data['return_check_remark'];
        $this->admin_id =$data['admin_id'];
    }
    
    public function update(){  
        $load = \hd_load::getInstance();
        $return_table =$load->table('order2/order2_return');
        $order_table = $load->table('order2/order2');   
        
        $order_id =$this->order_id;
        $return_id =$this->return_id;
        if($order_id <1 || $return_id <1){
            set_error("参数错误");
            return false;
        }
        
        $data =[
            'update_time'=>time(),
            'return_status'=>ReturnStatus::ReturnDenied,
            'return_check_remark'=>$this->return_check_remark,
            'admin_id'=>$this->admin_id,
        ];
        $b =$return_table->where(['return_id'=>$return_id])->save($data);
        if(!$b){
            set_error("更新退货单状态失败");
            return false;
        }
        //更新订单
        $order_data = [
            'status' =>State::OrderInService,
            'update_time'=> time(),//更新时间
            'return_status'=> ReturnStatus::ReturnDenied,//拒绝退货
        ];
        
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;
    }
}