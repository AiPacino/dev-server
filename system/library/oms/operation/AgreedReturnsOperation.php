<?php
namespace oms\operation;
use oms\state\State;
use zuji\order\ReceiveStatus;
use zuji\order\OrderStatus;

/**
 * 退货通过操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class AgreedReturnsOperation implements OrderOperation
{

    private $order_id=0;
    private $return_id=0;
    private $admin_id =0;
    private $return_check_remark ="";
    private $return_status=0;
    private $business_key =0;

    public function __construct( $data ){

        $this->return_id=$data['return_id'];
        $this->order_id=$data['order_id'];
        $this->return_status =$data['return_status'];
        $this->return_check_remark =$data['return_check_remark'];
        $this->admin_id =$data['admin_id'];
        $this->business_key =$data['business_key'];
    }
    
    public function update(){  
        $order_id =$this->order_id;
        $return_id =$this->return_id;
        
        if($order_id <1 || $return_id <1){
            set_error("参数错误");
            return false;
        }
        
        $load = \hd_load::getInstance();
        $return_table =$load->table('order2/order2_return');
        $order_table = $load->table('order2/order2');   
        $return_service =$load->service('order2/return');
        $return_info = $return_service->get_info($return_id);
   
        $data =[
            'update_time'=>time(),
            'return_status'=>$this->return_status,
            'return_check_time'=>time(),
            'return_check_remark'=>$this->return_check_remark,
            'admin_id'=>$this->admin_id,
        ];

        $b =$return_table->where(['return_id'=>$return_id])->save($data);
        if(!$b){
            set_error("更新退货单状态失败");
            return false;
        }
        $receive_data = [
            'order_id' => $order_id,
            'goods_id' => intval($return_info['goods_id']),
            'address_id ' => intval($return_info['address_id']),
            'business_key' => $this->business_key,
            'order_no' => $return_info['order_no'],
            'update_time'=>time(),
            'create_time'=>time(),
            'receive_status'=>ReceiveStatus::ReceiveWaiting,
            'wuliu_channel_id'=>1,
        ];
        $receive_table =$load->table('order2/order2_receive');
        $receive_id =$receive_table->add($receive_data);
        if(!$receive_id){
            set_error("创建收货单失败");
            return false;
        }
    
        //更新订单
        $order_data = [
            'status' =>State::OrderReturning,
            'update_time'=> time(),//更新时间
            'receive_id'=>$receive_id,
            'receive_status'=> ReceiveStatus::ReceiveWaiting,
        ];
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;
    }
}