<?php
namespace oms\operation;

use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\RefundStatus;
use zuji\Business;
use zuji\order\ServiceStatus;
use zuji\Config;
use oms\state\State;

/**
 * 退款操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class EditRefundOperation implements OrderOperation
{

    private $order_id=0;
    private $should_admin_id=0;
    private $should_amount=0;
    private $should_remark="";

    public function __construct( $data ){
       
        $this->refund_id =$data['refund_id'];
        $this->should_admin_id=$data['should_admin_id'];
        $this->should_amount=$data['should_amount'];
        $this->should_remark=$data['should_remark'];
    }
    
    public function update(){  
        $load = \hd_load::getInstance();
        $refund_table =$load->table('order2/order2_refund');
        
        $refund_id = $this->refund_id;
        $should_admin_id =$this->should_admin_id;
        $should_amount =$this->should_amount;
        $should_remark =$this->should_remark;
        $data=[
            'should_remark' =>$should_remark,
            'should_amount'=>$should_amount,
            'should_admin_id'=>$should_admin_id,
            'update_time'=>time(),
        ];
        
        $data = filter_array($data, [
            'should_remark'=>'required',
            'should_amount'=>'required',
            'should_admin_id'=>'required',
            'update_time' =>'required',
        ]);
        if( count($data)<4 ){
            set_error("参数错误");
            Debug::error(Location::L_Refund, get_error(), $data);
            return false;
        }
        
        $b =$refund_table->where(['refund_id'=>$refund_id])->save($data);
        if(!$b){
            set_error("更新退款单失败");
            Debug::error(Location::L_Refund, "更新应退金额失败".get_error(), $data);
            return false;
        }

        return true;
    }
}