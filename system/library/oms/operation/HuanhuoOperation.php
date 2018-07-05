<?php
namespace oms\operation;

use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\DeliveryStatus;
use oms\state\State;
use zuji\order\OrderStatus;

/**
 * 发货操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class HuanhuoOperation implements OrderOperation
{

    private $order_id=0;
    private $delivery_id=0;
    private $wuliu_channel_id=0;
    private $wuliu_no="";
    private $delivery_remark="";
    private $admin_id=0;
    private $goods_id=0;
    private $imei1="";
    private $imei2="";
    private $imei3="";
    private $serial_number="";

    public function __construct( $data ){
       
        $this->delivery_id =$data['delivery_id'];
        $this->wuliu_channel_id=$data['wuliu_channel_id'];
        $this->wuliu_no=$data['wuliu_no'];
        $this->delivery_remark=$data['delivery_remark'];
        $this->order_id=$data['order_id'];
        $this->admin_id=$data['admin_id'];
        $this->goods_id=$data['goods_id'];
        $this->imei1=$data['imei1'];
        $this->imei2=isset($data['imei2'])?$data['imei2']:"";
        $this->imei3=isset($data['imei3'])?$data['imei3']:"";
        $this->serial_number=isset($data['serial_number'])?$data['serial_number']:"";
    }
    
    public function update(){
        
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2');
        $delivery_table =$load->table('order2/order2_delivery');
        
        $delivery_data = array(
            'wuliu_channel_id' => $this->wuliu_channel_id,
            'wuliu_no' => $this->wuliu_no,
            'delivery_remark' => $this->delivery_remark,
            'admin_id' => $this->admin_id,
            'update_time'=>time(),
            'delivery_time'=>time(),
            'delivery_status'=>DeliveryStatus::DeliverySend
        );

        $delivery_data = filter_array($delivery_data, [
            'wuliu_channel_id'=>'required',
            'wuliu_no'=>'required',
            'delivery_remark'=>'required',
            'admin_id' =>'required',
            'update_time'=>'required',
            'delivery_time' =>'required',
            'delivery_status' =>'required',
        ]);
        if( count($delivery_data)<7 ){
            set_error("更新发货单参数错误");
            return false;
        }
        
        $b =$delivery_table->where(['delivery_id'=>$this->delivery_id])->save($delivery_data);
        if(!$b){
            set_error("更新发货单失败");
            Debug::error(Location::L_Delivery, get_error(), $delivery_data);
            return false;
        }
        $goods_table =$load->table('order2/order2_goods');
        
        $goods_data = array(
            'imei1' => $this->imei1,
            'imei2' => $this->imei2,
            'imei3' => $this->imei3,
            'serial_number' => $this->serial_number,
            'update_time'=>time(),
        );
        
        $goods_data = filter_array($goods_data, [
            'imei1'=>'required',
            'imei2'=>'required',
            'imei3'=>'required',
            'serial_number' =>'required',
            'update_time'=>'required',
        ]);
        if( count($goods_data)<5){
            set_error("更新发货单参数错误");
            return false;
        }
        $b =$goods_table->where(['goods_id'=>$this->goods_id])->save($goods_data);
        if(!$b){
            set_error("更新商品信息失败");
            return false;
        }
        
        //更新订单
        $order_data = [
            'status' =>State::OrderDeliveryed,
            'update_time'=> time(),//更新时间
            'delivery_status'=>DeliveryStatus::DeliverySend,
        ];
        
        $b =$order_table->where(['order_id'=>$this->order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            Debug::error(Location::L_Delivery,get_error(), $order_data);
            return false;
        }

        return true;     
    }
}