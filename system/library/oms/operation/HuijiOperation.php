<?php
namespace oms\operation;

use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\DeliveryStatus;
use zuji\order\OrderStatus;
use zuji\order\delivery\Delivery;
use oms\state\State;

/**
 * 回寄操作
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 *
 */
class HuijiOperation implements OrderOperation{

    private $order_id           = 0;
    private $logistics_id       = 0;
    private $delivery_id        = 0;
    private $goods_id           = 0;
    private $admin_id           = 0;
    private $logistics_sn           = "";
    private $delivery_remark    = "";

    public function __construct( $data ){
        $this->order_id         = $data['order_id'];        //订单id
        $this->logistics_id     = $data['logistics_id'];    //物流渠道ID
        $this->delivery_id      = $data['delivery_id'];     //物流ID
        $this->goods_id         = $data['goods_id'];        //产品ID

        $this->admin_id         = $data['admin_id'];    
        $this->logistics_sn     = $data['logistics_sn'];    
        $this->delivery_remark  = $data['delivery_remark'];  //发货备注

    }
    
     /**
     * 回寄发货
     * @param array $delivery_id    【必须】订单ID
     * @param array $order_id       【必须】地址ID
     * @param array $goods_id       【必须】订单ID
     * @param array $logistics_id   【必须】地址ID
     * @param array $admin_id       【必须】操作者
     * @param array $logistics_sn   【必须】物流单号
     * @param array $delivery_remark【必须】发货备注

     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function update(){  
        $data['order_id']       = $this->order_id;
        $data['logistics_id']   = $this->logistics_id;
        $data['delivery_id']    = $this->delivery_id;
        $data['goods_id']       = $this->goods_id;
        $data['admin_id']       = $this->admin_id;
        $data['logistics_sn']   = $this->logistics_sn;
        $data['delivery_remark']= $this->delivery_remark;

        $data = filter_array($data, [
            'order_id'=>'required|is_id',
            'logistics_id'=>'required|is_id',
            'delivery_id' =>'required|is_id',
            'goods_id' =>'required|is_id',
            'admin_id'=>'required|is_id',
            'logistics_sn' =>'required',
            'delivery_remark' =>'required',
        ]);
        
        if( count($data)<7 ){
            set_error("参数错误");
            return false;
        }

        $load = \hd_load::getInstance();
        $delivery_service   = $load->service('order2/delivery');
        $order_service      = $load->service('order2/order');
        $delivery_table =$load->table('order2/order2_delivery');

        $Delivery_data = array(
            'delivery_id'       => $this->delivery_id,
            'order_id'          => $this->order_id,
            'wuliu_channel_id'  => $this->logistics_id,
            'wuliu_no'          => $this->logistics_sn,
            'delivery_remark'   => $this->delivery_remark,
            'admin_id'          => $this->admin_id,
            'update_time'=>time(),
            'delivery_time'=>time(),
            'delivery_status'=>DeliveryStatus::DeliverySend,
        );
        $result = $delivery_table->save($Delivery_data);

        if(!$result){
            set_error("更新发货单失败");
            return false;
        }
        
        //更新订单
        $order_data = [
            'status' =>State::OrderDeliveryed,
            'update_time'=> time(),//更新时间
            'delivery_status'=>DeliveryStatus::DeliverySend,
        ];
        $order_table = $load->table('order2/order2');
        
        $b =$order_table->where(['order_id'=>$this->order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;

    }
}