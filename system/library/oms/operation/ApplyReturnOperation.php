<?php
namespace oms\operation;

use zuji\order\ReturnStatus;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\state\State;
use zuji\order\OrderStatus;

/**
 * 退货申请
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 *
 */
class ApplyReturnOperation implements OrderOperation
{

    private $order_id       = 0;
    private $reason_id      = 0;
    private $reason_text    = "";
    private $loss_type      = 0;
    private $address_id     = 0;
    private $business_key    = 0;


    public function __construct( $data ){

        $this->order_id      = $data['order_id'];
        $this->reason_id     = $data['reason_id'];
        $this->reason_text   = $data['reason_text'];
        $this->loss_type     = $data['loss_type'];
        $this->address_id    = $data['address_id'];
        $this->business_key  = $data['business_key'];
    }
    
    /**
     * 生成退货申请单
     * @param array $data   【必选】退货申请单信息
     * array(
     *     'order_id' => '',        //【必须】int;订单ID
     *     'reason_id' => '',       //【必须】int;退货原因ID
     *     'reason_text' => '',     //【必须】int;退货附加原因描述
     *     'loss_type'=>'',         //【必须】int;损耗类型
     *     'address_id' =>'',       //【必须】int;退货地址    
     * )
     * @return mixed    false：失败；int：退货单ID
     * 当创建失败时返回false；当创建成功时返回退货单ID
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function update(){  
        $load = \hd_load::getInstance();
        $return_table   = $load->table('order2/order2_return');
        $order_table    = $load->table('order2/order2');
        $order_service  = $load->service('order2/order');
        //订单信息
        $order_info     = $order_table->get_info(['order_id'=>$this->order_id]);
        if(!$order_info){
            set_error("更新退货单状态失败");
            return false;
        }

        // 保存退货申请单
        $data['order_id']       = $this->order_id;
        $data['order_no']       = $order_info['order_no'];
        $data['user_id']        = $order_info['user_id'];
        $data['goods_id']       = $order_info['goods_id'];
        $data['reason_id']      = $this->reason_id;
        $data['reason_text']    = $this->reason_text;
        $data['business_key']   = $this->business_key;
        $data['loss_type']      = $this->loss_type;
        $data['address_id']     = $this->address_id;
        $data['return_status']  = ReturnStatus::ReturnWaiting;
        $data['create_time']    = time();
        $data['update_time']    = time();

        $return_id = $return_table->add($data);
        if( !$return_id ){
            set_error("更新退货单状态失败");
            return false;
        }
        
    //更新订单
            $order_data = [
                'status' =>State::OrderReturnChecking,
                'update_time'=> time(),//更新时间
                'return_id'=>$return_id,
                'return_status'=>ReturnStatus::ReturnWaiting,
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