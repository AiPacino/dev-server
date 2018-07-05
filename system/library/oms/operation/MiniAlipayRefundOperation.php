<?php
namespace oms\operation;

use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\RefundStatus;
use zuji\Business;
use zuji\order\ServiceStatus;
use zuji\Config;
use oms\state\State;
use zuji\order\OrderStatus;

/**
 * 小程序支付退款操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class MiniAlipayRefundOperation implements OrderOperation
{

    private $Order = null;
    private $data = '';

    public function __construct(\oms\Order $Order,array $data){
        $this->Order = $Order;
        $this->data = $data;
    }
    
    public function update(){  
        $load = \hd_load::getInstance();
        $service_table =$load->table('order2/order2_service');
        $order_service =$load->service('order2/order');
        $order_table = $load->table('order2/order2');
        //更新订单
        $order_data = [
            'status' =>State::OrderCanceled,
            'update_time'=> time(),//更新时间
            'refund_status'=> RefundStatus::RefundSuccessful,
            'order_status'=>OrderStatus::OrderCanceled,
            'refund_amount' => $this->Order->get_amount(),
            'refund_time' => time()
        ];
        $order_id =$this->Order->get_order_id();
        $goods_id =$this->Order->get_goods_id();
        $business_key =$this->Order->get_business_key();
        $user_id =$this->Order->get_user_id();
        
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        
        //修改服务单状态
        //退款成功时  如果有服务 取消服务
            $service_service=$load->service('order2/service');
            $service_info =$service_service->get_service_info(['order_id'=>$order_id]);
            $service_data =[
                'update_time'=>time(),
                'service_status'=>ServiceStatus::ServiceCancel,
            ];
            if($service_info){
                $b =$service_table->where(['order_id'=>$order_id])->save($service_data);
                if(!$b){
                    set_error("同步到服务单状态失败");
                    return false;
                } 
            }
        // 租机业务退款，则 恢复库存数量
        if($business_key == Business::BUSINESS_ZUJI){
            $goods_info =$order_service->get_goods_info($goods_id);
            if(!$goods_info){
                set_error("商品信息不存在");
                return false;
            }
            $sku_service =$load->service('goods2/goods_sku');
            $sku_info =$sku_service->api_get_info($goods_info['sku_id'],"");        
            //sku库存 +1
            $sku_table =$load->table('goods2/goods_sku');
            $spu_table=$load->table('goods2/goods_spu');
            
            $sku_data['sku_id'] =$goods_info['sku_id'];
            $sku_data['number'] = ['exp','number+1'];
            $add_sku =$sku_table->save($sku_data);
            if(!$add_sku){
                set_error("恢复商品库存失败");
                return false;
            }
            $spu_data['id'] =$sku_info['spu_id'];
            $spu_data['sku_total'] = ['exp','sku_total+1'];
            $add_spu =$spu_table->save($spu_data);
            if(!$add_spu){
                set_error("恢复总库存失败");
                return false;
            }
        }

        // 取消分期
        $instalment =$load->service('order2/instalment');
        $b = $instalment->cancel_instalment($order_id);
        if($b===false){
            set_error("取消分期失败");
            return false;
        }

        //查询 如果退款/已解冻 次数过多 封闭账号
        $status =State::OrderRefunded.",".State::FundsThawed;
        $where =[
            'user_id'=>$user_id,
            'new_status'=>$status,
            'begin_time'=>mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")),
            'end_time'=>mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")),
        ];
        $user_follow = $order_service->get_follow_user_list($where);

        if(count($user_follow) >= Config::Order_Refund_Num){
            $member_table = $load->table('member/member');
            $b =$member_table->where(array('id'=>$user_id))->save(['block'=>1]);
            if(!$b){
                set_error("封锁账户失败");
                return false;
            }
        }

       return true;      
    }
}