<?php
/**
 * 生成发货单
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 */
namespace oms\operation;

use oms\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\state\State;
use zuji\order\DeliveryStatus;
use zuji\order\PaymentStatus;
use zuji\order\OrderStatus;
use zuji\Business;

class BuildDeliveryOperation implements OrderOperation
{

    private $order_id           = 0;
    private $business_key       = "";
    private $goods_id           = 0;
    private $evaluation_id      = 0;
    private $name               = "";
    private $mobile             = "";
    private $address            = "";
    private $remark             = "";
    private $province_id        = 0;
    private $city_id            = 0;
    private $country_id         = 0;


    public function __construct( $data ){
        $this->order_id         = $data['order_id'];        //订单id
        $this->business_key     = $data['business_key'];    //business_key
        $this->goods_id         = $data['goods_id'];        //产品ID
        $this->evaluation_id    = $data['evaluation_id'];    

        $this->name             = $data['name'];  //名称
        $this->mobile           = $data['mobile'];  //手机
        $this->address          = $data['address'];  //地址
        $this->remark           = $data['remark'];  //备注
        $this->province_id      = $data['province_id'];  //省份id
        $this->city_id          = $data['city_id'];  //市
        $this->country_id       = $data['country_id'];  //国家

    }
    
     /**
     * 创建发货单
     * @param array $delivery_id    【必须】订单ID
     * @param array $order_id       【必须】地址ID
     * @param array $goods_id       【必须】订单ID
     * @param array $logistics_id   【必须】地址ID
     * @param array $logistics_sn   【必须】物流单号
     * @param array $delivery_remark【必须】发货备注

     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function update(){  

        $load = \hd_load::getInstance();
        $delivery_service   = $load->service('order2/delivery');
        $order_service      = $load->service('order2/order');
        // 订单表
        $order2_table       = $load->table('order2/order2');
        $order_info = $order_service->get_order_info(['order_id' => $this->order_id]);

        //生成收货地址
        $address_data = [
            'order_id' => $this->order_id,
            'user_id' => $order_info['user_id'],
            'name' => $this->name,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'remark' => $this->remark,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'country_id' => $this->country_id,
        ];

        $address_id = $order_service->create_address($address_data);
        if (!$address_id) {
            set_error("创建收货地址失败");
            return false;

        }
         
        $delivery_data['order_id']      = $this->order_id;
        $delivery_data['order_no']      = $order_info['order_no'];
        $delivery_data['goods_id']      = $this->goods_id;
        $delivery_data['address_id']    = $address_id;
        $delivery_data['business_key']  = $this->business_key;
        $delivery_data['evaluation_id'] = $this->evaluation_id;
        $delivery_data['delivery_status'] = DeliveryStatus::DeliveryWaiting;
        $delivery_data['create_time'] = time();
        $delivery_data['update_time'] = time();

        $delivery_table = $load->table("order2/order2_delivery");

        $delivery_id = $delivery_table->add($delivery_data);
        if(!$delivery_id){
            set_error('创建发货单错误');
            return false;
        }

        // 更新订单 status, delivery_id, delivery_status
        if($this->business_key == Business::BUSINESS_HUANHUO){
            $status = State::OrderHuanhuoing;
        }else if($this->business_key == Business::BUSINESS_HUIJI){
            $status = State::OrderHuijiing;
        }

        $order_data = array(
            'delivery_id'=> $delivery_id,//发货单id
            'status' => $status,
            'delivery_status'=> DeliveryStatus::DeliveryWaiting,//订单待发货
        );
        //更新订单
        $order_result = $order2_table->where(['order_id'=>$this->order_id])->save($order_data);
        //验证订单更新是否成功
        if( !$order_result ) {//业务处理不成功
            set_error('更新订单状态失败');
            return false;
        }

        return true;

    }
}