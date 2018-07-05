<?php
namespace oms\operation;

use oms\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\RefundStatus;
use zuji\Business;
use zuji\order\ServiceStatus;
use zuji\Config;
use oms\state\State;
use zuji\order\DeliveryStatus;
use zuji\order\OrderStatus;

/**
 * 解冻操作类
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class FundsThawedOperation implements OrderOperation
{
    private $Order = null;
    private $amount =0;

    public function __construct( Order $order,$data ){
        $this->Order =$order;
        $this->amount =$data['should_amount'];
    }
    
    public function update(){  

        $load = \hd_load::getInstance();
        $delivery_table =$load->table('order2/order2_delivery');
        $order_table = $load->table('order2/order2');
        $instalment =$load->service('order2/instalment');
        $fundauth_service = $load->service('payment/fund_auth');
        $order_service =$load->service('order2/order');
        $business_key =$this->Order->get_business_key();

        //更新订单
        $order_data = [
            'status' =>State::FundsThawed,
            'update_time'=> time(),//更新时间
        ];

        $b =$order_table->where(['order_id'=>$this->Order->get_order_id()])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }

        //修改服务单状态
        //退款成功时  如果有服务 取消服务
        $service_service=$load->service('order2/service');
        $service_table =$load->table('order2/order2_service');
        $service_info =$service_service->get_service_info(['order_id'=>$this->Order->get_order_id()]);
        $service_data =[
            'update_time'=>time(),
            'service_status'=>ServiceStatus::ServiceCancel,
        ];
        if($service_info){
            $b =$service_table->where(['order_id'=>$this->Order->get_order_id()])->save($service_data);
            if(!$b){
                set_error("同步到服务单状态失败");
                return false;
            }
        }
        // 租机业务退款，则 恢复库存数量
//        if($business_key == Business::BUSINESS_ZUJI){
//            $goods_id =$this->Order->get_goods_id();
//
//            $goods_info =$order_service->get_goods_info($goods_id);
//            if(!$goods_info){
//                set_error("商品信息不存在");
//                return false;
//            }
//            //sku库存 +1
//            $sku_table =$load->table('goods2/goods_sku');
//            $spu_table=$load->table('goods2/goods_spu');
//
//            $sku_data['sku_id'] =$goods_info['sku_id'];
//            $sku_data['number'] = ['exp','number+1'];
//            $add_sku =$sku_table->save($sku_data);
//            if(!$add_sku){
//                set_error("恢复商品库存失败");
//                return false;
//            }
//            $spu_data['id'] =$goods_info['spu_id'];
//            $spu_data['sku_total'] = ['exp','sku_total+1'];
//            $add_spu =$spu_table->save($spu_data);
//            if(!$add_spu){
//                set_error("恢复总库存失败");
//                return false;
//            }
//        }


        //查询退款单信息
//        $user_refund=[
//            'user_id' =>intval($this->Order->get_user_id()),
//            'begin_time'=>mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")),
//            'end_time'=>mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")),
//            'refund_status'=>RefundStatus::RefundSuccessful,
//        ];
//
//        $refund_service = $load->service('order2/refund');
//        $refund_count =$refund_service->get_count($user_refund);
        //查询 如果退款/已解冻 次数过多 封闭账号
        $status =State::OrderRefunded.",".State::FundsThawed;
        $where =[
            'user_id'=>$this->Order->get_user_id(),
            'new_status'=>$status,
            'begin_time'=>mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")),
            'end_time'=>mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")),
        ];
        $user_follow = $order_service->get_follow_user_list($where);

        if(count($user_follow) >= Config::Order_Refund_Num){
            $member_table = $load->table('member/member');
            $b =$member_table->where(array('id'=>$this->Order->get_user_id()))->save(['block'=>1]);
            if($b===false){
                set_error("封锁账户失败");
                return false;
            }
        }
        // 解除授权
        $fundauth_service = $load->service('payment/fund_auth');
        $b = $fundauth_service->order_unfreeze_fundauth( $this->Order->get_order_id(),$this->amount );
        if(!$b){
            set_error("解除授权失败");
            return false;
        }

       return true;      
    }
}