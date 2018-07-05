<?php
namespace oms\operation;

use zuji\certificate\Creater;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\RefundStatus;
use zuji\Business;
use zuji\order\ServiceStatus;
use zuji\Config;
use oms\state\State;
use zuji\order\OrderStatus;

/**
 * 银联退款操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class UnionRefundOperation implements OrderOperation
{

    private $order_id=0;
    private $refund_id=0;
    private $refund_remark="";
    private $admin_id=0;
    private $business_key=0;
    private $goods_id=0;
    private $Order =null;
    private $order_refund_no ='';

    public function __construct( \oms\Order $Order,$data ){
        $this->refund_id =$data['refund_id'];
        $this->refund_remark=$data['refund_remark'];
        $this->order_id=$data['order_id'];
        $this->admin_id=$data['admin_id'];
        $this->business_key=$data['business_key'];
        $this->goods_id=$data['goods_id'];
        $this->Order =$Order;
        $this->refund_no =Business::create_business_no();
        //初始化appid和时间戳
        $this->curl_create['appid'] = $Order->get_appid();
        $this->curl_create['timestamp'] = date("Y-m-d H:i:s",time());
        $this->curl_create['method'] = "pay.payment.refund";
        $this->curl_create['sign_type'] = "MD5";
        $this->curl_create['sign'] = "unionrefund";
        $this->curl_create['version'] = "1.0";
        $this->curl_create['auth_token'] = "unionrefund";
        $this->curl_create['params'] = [
            'amount'=>$data['should_amount'],
            'payment_no'=>$data['payment_no'],
            'out_refund_no'=>$this->refund_no,
            'refund_back_url'=>\config('Alipay_Refund_Notify_Url'),
        ];
    }
    
    public function update(){  
        $load = \hd_load::getInstance();
        $refund_service = $load->service('order2/refund');
        $refund_table =$load->table('order2/order2_refund');
        $refund_id=$this->refund_id;
        $refund_remark=$this->refund_remark;
        $order_id=$this->order_id;
        $admin_id =$this->admin_id;

        //调用银联退款接口
        $response = $this->send_curl();
        if(!isset($response['data']['refund_no'])){
            set_error('退款接口返回错误');
            return false;
        }else{
            //查询退款单信息
            $refund_info =  $refund_service->get_info($refund_id);
            $refund_data=[
                'return_type' =>1,	                            // 【必须】int；0初始化 1.原路返回 2.其他
                'refund_remark'=>$refund_remark,                // 【可选】string：退款备注
                'refund_amount'=>$refund_info['should_amount']*100, // 【必选】退款金额（单位：分）
                'order_id'=>$order_id,   // 【必选】订单ID
                'admin_id'=>$admin_id,
                'refund_status'=>RefundStatus::RefundPaying,
                'refund_no'=>$this->refund_no,
                'out_refund_no'=>$response['data']['refund_no'],
            ];
            $b =$refund_table->where(['refund_id'=>$refund_id])->save($refund_data);
            if(!$b){
                set_error("更新退款单失败");
                return false;
            }
            return true;
        }

    }


    //发起curl请求
    public function send_curl(){
        Debug::error(Location::L_Refund,'退款失败1请求数据',$this->curl_create);
        $result = \zuji\Curl::post(config('Interior_Pay_Url'),json_encode($this->curl_create));
        Debug::error(Location::L_Refund,'退款失败2回执数据',$result);
        $data  = json_decode($result,true);
        if(!is_array($data)){
            set_error("返回数据错误");
            return false;
        }
        return $data;
    }


}