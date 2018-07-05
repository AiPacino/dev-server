<?php
namespace oms\operation;

use oms\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\state\State;
use zuji\order\OrderStatus;
use zuji\order\ServiceStatus;

/**
 * 关闭服务操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class CloseServiceOperation implements OrderOperation
{

    private $order_id=0;
    private $service_id=0;
    private $remark ='';
    private $Order =null;
    public function __construct($data,Order $order){

        $this->service_id=$data['service_id'];
        $this->order_id=$data['order_id'];
        $this->remark =isset($data['remark'])?$data['remark']:'';
    }

    public function update(){
        $order_id =$this->order_id;
        $service_id =$this->service_id;
        
        if($order_id <1 || $service_id <1){
            set_error("参数错误");
            return false;
        }
        $load = \hd_load::getInstance();
        $service_table =$load->table('order2/order2_service');
        
        $data =[
            'update_time'=>time,
            'service_status'=>ServiceStatus::ServiceClose,
            'remark'=>$this->remark,
        ];
        $b =$service_table->where(['service_id'=>$service_id])->save($data);
        if(!$b){
            set_error("更新服务单状态失败");
            Debug::error(Location::L_Service, get_error(), $data);
            return false;
        }

        $order_table = $load->table('order2/order2');
        //更新订单
        $order_data = [
            'status' =>State::OrderClosed,
            'update_time'=> time(),//更新时间
            'order_status'=>OrderStatus::OrderFinished,
            'service_status'=> ServiceStatus::ServiceClose,
        ];

        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            Debug::error(Location::L_Service,get_error(), $order_data);
            return false;
        }
        return true;
    }
}