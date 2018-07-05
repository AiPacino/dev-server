<?php
namespace oms\operation;

use zuji\Business;
use zuji\Config;
use zuji\order\OrderStatus;
use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\refund\Refund;
use zuji\order\RefundStatus;
use zuji\order\ServiceStatus;

/**
 *银联退款回调操作类
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class UnionRefundNotifyOperation implements OrderOperation
{

    private $Order = null;
    private $data = '';
    
    public function __construct(\oms\Order $Order,array $data){
		$this->Order = $Order;
		$this->data = $data;
    }
    
	/**
	 * 银联退款异步通知操作
	 * <p>1) 记录银联退款异步通知</p>
	 * @return boolean
	 */
    public function update(){
		
		//-+--------------------------------------------------------------------
		// | 系统类加载器
		//-+--------------------------------------------------------------------
		$load = \hd_load::getInstance();
		$payment_table = $load->table('order2/order2_payment');
        $refund_service = $load->service('order2/refund');
        $payment_service = $load->service('order2/payment');
        $refund_table =$load->table('order2/order2_refund');
        $service_table =$load->table('order2/order2_service');
        $order_service =$load->service('order2/order');
        $order_table = $load->table('order2/order2');

        $refund_id=$this->Order->get_refund_id();
        $order_id=$this->Order->get_order_id();
        $business_key =$this->Order->get_business_key();

        //查询退款单信息
        $refund_info =  $refund_service->get_info($refund_id);

        $refund_data=[
            'update_time'=>time(),
            'refund_time'=>time(),
            'refund_status'=>RefundStatus::RefundSuccessful
        ];
        $b =$refund_table->where(['refund_id'=>$refund_id])->save($refund_data);
        if(!$b){
            set_error("更新退款单失败");
            return false;
        }
        //更新订单
        $order_data = [
            'status' =>State::OrderRefunded,
            'update_time'=> time(),//更新时间
            'order_status'=>OrderStatus::OrderCanceled,
        ];
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
            $goods_info =$order_service->get_goods_info($this->Order->get_goods_id());
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
            'user_id'=>$refund_info['user_id'],
            'new_status'=>$status,
            'begin_time'=>mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")),
            'end_time'=>mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")),
        ];
        $user_follow = $order_service->get_follow_user_list($where);

        if(count($user_follow) >= Config::Order_Refund_Num){
            $member_table = $load->table('member/member');
            $b =$member_table->where(array('id'=>$refund_info['user_id']))->save(['block'=>1]);
            if(!$b){
                Debug::error(Location::L_Refund,"退款次数".count($user_follow),'');
                set_error("封锁账户失败：".count($user_follow));
                return false;
            }
        }
        return true;
    }
}