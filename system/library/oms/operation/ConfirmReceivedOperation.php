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
use zuji\order\EvaluationStatus;

/**
 * 平台收货操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class ConfirmReceivedOperation implements OrderOperation
{

    private $order_id=0;
    private $receive_id=0;
    private $admin_id =0;
    private $order_no ="";
    private $goods_id=0;
    private $business_key =0;

    public function __construct( $data ){

        $this->receive_id=$data['receive_id'];
        $this->order_id=$data['order_id'];
        $this->goods_id =$data['goods_id'];
        $this->admin_id =$data['admin_id'];
        $this->business_key =$data['business_key'];
        $this->order_no =$data['order_no'];
    }
    
    public function update(){  
        $data=[
            'receive_id'=>$this->receive_id,
            'order_id'=>$this->order_id,
            'goods_id'=>$this->goods_id,
            'admin_id' =>$this->admin_id,
            'business_key' =>$this->business_key,
            'order_no' =>$this->goods_id,
        ];
        
        $data = filter_array($data, [
            'receive_id'=>'required|is_id',
            'order_no'=>'required',
            'order_id'=>'required|is_id',
            'admin_id' =>'required|is_int',
            'business_key' =>'required|is_int',
            'goods_id' =>'required|is_id',
        ]);
        if( count($data)<6 ){
            set_error("参数错误");
            Debug::error(Location::L_Receive, get_error(), $data);
            return false;
        }
        
        $order_id =$this->order_id;
        $receive_id =$this->receive_id;
        $load = \hd_load::getInstance();

        $receive_data=[
            'admin_id'=>$this->admin_id,
            'order_id'=>$order_id,
            'update_time'=>time(),
            'receive_time'=>time(),
            'receive_status'=>ReceiveStatus::ReceiveFinished,
        ];
        $receive_table =$load->table('order2/order2_receive');
        $b =$receive_table->where(['receive_id'=>$receive_id])->save($receive_data);
        if(!$b){
            set_error("更新收货单状态失败");
            Debug::error(Location::L_Receive, get_error(), $receive_data);
            return false;
        }
        $evaluation_data =[
            'order_id'=>$order_id,
            'order_no'=>$this->order_no,
            'business_key'=>$this->business_key,
            'goods_id'=>$this->goods_id,
            'evaluation_status' => EvaluationStatus::EvaluationWaiting,
            'create_time' => time(),//检测单生成时间
            'update_time' => time(),//检测单更新时间
        ];
        
        $evaluation_table =$load->table('order2/order2_evaluation');
        $evaluation_id =$evaluation_table->add($evaluation_data);
        if(!$evaluation_id){
            set_error("生成检测单失败");
            Debug::error(Location::L_Receive, get_error(), $evaluation_data);
            return false;
        }          
        //更新订单
        $order_data = [
            'status' =>State::OrderReceived,
            'update_time'=> time(),//更新时间
            'evaluation_status'=> EvaluationStatus::EvaluationWaiting,
            'evaluation_id'=>$evaluation_id,
        ];
        $order_table = $load->table('order2/order2');
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            Debug::error(Location::L_Receive,get_error(), $order_data);
            return false;
        }
        return true;
    }
}