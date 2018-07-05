<?php
namespace oms\operation;

use oms\Order;
use zuji\Business;
use zuji\Config;
use zuji\order\EvaluationStatus;
use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
/**
 * 检测操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class EvaluationOperation implements OrderOperation
{

    private $order_id=0;
    private $evaluation_id=0;
    private $admin_id =0;
    private $remark ="";
    private $qualified=0;
    private $Order =null;

    public function __construct( $data,Order $order ){

        $this->evaluation_id=$data['evaluation_id'];
        $this->order_id=$data['order_id'];
        $this->qualified =$data['qualified'];
        $this->remark =isset($data['remark'])?$data['remark']:'';
        $this->admin_id =isset($data['admin_id'])?$data['admin_id']:0;
        $this->Order =$order;
    }
    
    public function update(){  
        $order_id =$this->order_id;
        $evaluation_id =$this->evaluation_id;
        $qualified =intval($this->qualified);
        $data=[
            'order_id'=>$order_id,
            'evaluation_id'=>$evaluation_id,
            'qualified'=>$qualified,
        ];
        
        $data = filter_array($data, [
            'order_id'=>'required',
            'evaluation_id'=>'required',
            'qualified'=>'required',
        ]);
        if( count($data)<3 ){
            set_error("参数错误");
            return false;
        }
        
        $load = \hd_load::getInstance();
        $evaluation_table =$load->table('order2/order2_evaluation');
        $order_table = $load->table('order2/order2');
        $evaluation_service =$load->service('order2/evaluation');
        $evaluation_info = $evaluation_service->get_info($evaluation_id);
        
        $evaluation_status = EvaluationStatus::EvaluationUnqualified;
        $status =State::OrderEvaluationUnqualified;
        if( $qualified==1 ){
            $evaluation_status = EvaluationStatus::EvaluationQualified;
            if($this->Order->get_payment_type_id() == Config::MiniAlipay && $evaluation_info['business_key'] == Business::BUSINESS_ZUJI){
                $status=State::OrderRefunding;
            }else{
                $status=State::OrderEvaluationQualified;
            }

        }
   
        // 设置检测结果
        $evaluation_data = [
            'evaluation_status' => $evaluation_status,
            'evaluation_admin_id' => $this->admin_id,
            'evaluation_remark' => $this->remark,
            'evaluation_time'=>time(),
        ];
        $b =$evaluation_table->where(['evaluation_id'=>$evaluation_id])->save($evaluation_data);
        if(!$b){
            set_error("更新检测单状态失败");
            return false;
        }

        //更新订单
        $order_data = [
            'status' =>$status,
            'update_time'=> time(),//更新时间
            'evaluation_status'=> $evaluation_status,
        ];
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;
    }
}