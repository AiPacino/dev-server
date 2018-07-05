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
 * 退款操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class RefundOperation implements OrderOperation
{

    private $order_id=0;
    private $refund_id=0;
    private $refund_remark="";
    private $admin_id=0;
    private $business_key=0;
    private $goods_id=0;

    public function __construct( $data ){
       
        $this->refund_id =$data['refund_id'];
        $this->refund_remark=$data['refund_remark'];
        $this->order_id=$data['order_id'];
        $this->admin_id=$data['admin_id'];
        $this->business_key=$data['business_key'];
        $this->goods_id=$data['goods_id'];
    }
    
    public function update(){  
        $load = \hd_load::getInstance();
        $refund_service = $load->service('order2/refund');
        $payment_service = $load->service('order2/payment');
        $trade_service = $load->service('payment/payment_trade');
        $trade_table = $load->table('payment/payment_trade');
        $refund_table =$load->table('order2/order2_refund');
        $service_table =$load->table('order2/order2_service');
        $order_service =$load->service('order2/order');
        $order_table = $load->table('order2/order2');
        
        $data=[
            'refund_id'=>$this->refund_id,
            'refund_remark'=>$this->refund_remark,
            'order_id'=>$this->order_id,
            'admin_id' =>$this->admin_id,
            'business_key' =>$this->business_key,
            'goods_id' =>$this->goods_id,
        ];
        
        $data = filter_array($data, [
            'refund_id'=>'required|is_id',
            'refund_remark'=>'required',
            'order_id'=>'required|is_id',
            'admin_id' =>'required',
            'business_key' =>'required',
            'goods_id' =>'required|is_id',
        ]);
        if( count($data)<6 ){
            set_error("参数错误");
            Debug::error(Location::L_Refund, get_error(), $data);
            return false;
        }
        $refund_id=$this->refund_id;
        $refund_remark=$this->refund_remark;
        $order_id=$this->order_id;
        $admin_id =$this->admin_id;
        $business_key =$this->business_key;
        $goods_id =$this->goods_id;
        
        //查询退款单信息
        $refund_info =  $refund_service->get_info($refund_id);
        // 查询支付单信息
        $payment_info = $payment_service->get_info($refund_info['payment_id']);
        // 查询交易信息
        $trade_info = $trade_service->get_info_by_trade_no( $payment_info['trade_no'] );
        // 支付宝退款操作
        $appid = config('ALIPAY_APP_ID');
        $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
        $Refund = new \alipay\Refund($appid);
        $params = [
            'trade_no' => $trade_info['trade_no'],
            'out_trade_no' => $trade_info['out_trade_no'],
            'refund_amount' => $refund_info['should_amount'],
            'refund_reason' => '正常退货退款',
            'request_no' => \zuji\Business::create_business_no(),
        ];
        
        $b = $Refund->refund( $params );
        // 处理退款操作
        if( !$b ){
            set_error("支付宝退款失败");
            Debug::error(Location::L_Refund, '退款失败'.get_error(),$params);
            return false;
        }
        $data=[
            'refund_amount'=>$refund_info['should_amount'],
            'update_time'=>time(),
            'refund_time'=>time(),
        ];
        
        $b = $trade_table->where(['trade_no'=>$trade_info['trade_no']])->save($data); 
        if(!$b){
            set_error("更新支付单状态失败");
            Debug::error(Location::L_Refund, '退款[更新状态]失败'.get_error(),$data);
            return false;
        };
         
        $refund_data=[
            'return_type' =>1,	                            // 【必须】int；0初始化 1.原路返回 2.其他
            'refund_remark'=>$refund_remark,                // 【可选】string：退款备注
            'refund_amount'=>$refund_info['should_amount']*100, // 【必选】退款金额（单位：分）
            'order_id'=>$order_id,   // 【必选】订单ID
            'admin_id'=>$admin_id,
            'update_time'=>time(),
            'refund_time'=>time(),
            'refund_status'=>RefundStatus::RefundSuccessful
        ];
        
        $b =$refund_table->where(['refund_id'=>$refund_id])->save($refund_data);   
 
        if(!$b){
            set_error("更新退款单失败");
            Debug::error(Location::L_Refund, '退款[状态同步]失败'.get_error(), $refund_data);
            return false;
        }
        
        //更新订单
        $order_data = [
            'status' =>State::OrderRefunded,
            'update_time'=> time(),//更新时间
            'refund_status'=> RefundStatus::RefundSuccessful,
            'order_status'=>OrderStatus::OrderCanceled,
            'refund_amount' => $refund_info['should_amount']*100,
            'refund_time' => time()
        ];
        
        $b =$order_table->where(['order_id'=>$this->order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        
        //修改服务单状态
        //退款成功时  如果有服务 取消服务
            $service_service=$load->service('order2/service');
            $service_info =$service_service->get_service_info(['order_id'=>$this->order_id]);
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
        
        //查询 如果退款次数过多 封闭账号
//        $user_refund=[
//            'user_id' =>intval($refund_info['user_id']),
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
                set_error("封锁账户失败");
                return false;
            }
        }

       return true;      
    }
}