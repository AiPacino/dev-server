<?php

namespace oms\state;

use oms\operation\CancelOperation;
use oms\operation\CreateRefundOperation;
use oms\operation\BuildDeliveryOperation;
use oms\operation\AbnormalOperation;
use oms\operation\FundsThawedOperation;
use zuji\Config;
use zuji\order\EvaluationStatus;

/**
 * OrderEvaluationUnqualifiedState  检测不合格
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class OrderEvaluationUnqualifiedState extends State {
    
    public function get_state(){
       return State::OrderEvaluationUnqualified;
    }
    
    public function get_name(){
       return '检测不合格';
    }
    
    public function get_client_name(){
       return '检测不合格';
    }
    
    //操作列表
    public function get_operation_list(){
        return [
            [
                'mca'=>'order2/evaluation/deal_result',
                'name'=>'异常处理',
                'params' => ['order_id' => $this->Order->get_order_id(),'evaluation_id'=>$this->Order->get_evaluation_id()],
                'iframe_width' => 350,
                'is_show' => false
            ]
        ];
    }

    //检测是否允许买断申请
    public function allow_to_apply_for_buyout(){
       return true;
    }

    //检测是否允许创建发货单  回寄 换货
    public function allow_to_create_delivery(){
        return true;
    }


    //检测是否允许申请退款
    public function allow_to_create_refund(){
       return true;
    }
   //是否允许异常处理
   public function allow_to_abnormal(){
       return true;
   }
    //是否允许解除资金预授权
    public function allow_to_remove_authorize(){
        $payment_type_id =$this->Order->get_payment_type_id();
        if($payment_type_id == Config::WithhodingPay){
            return true;
        }else{
            return false;
        }
    }
    //判断是否允许 取消
    public function allow_to_cancel_order(){
        $payment_type_id =$this->Order->get_payment_type_id();
        if($payment_type_id == Config::WithhodingPay){
            return true;
        }elseif($payment_type_id == Config::MiniAlipay){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 异常处理
     * @param array $data
     * [
     *      'order_id'=>'',             // 【必须】订单ID
     *      'unqualified_result'=>'',   // 【必须】状态
     *      'unqualified_result'=>'',
     *      'evaluation_id'=>'',
     *      'admin_id'=>''
     * ]
     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
   public function abnormal($data){
        
        // 操作前状态
        $old_state = $this->get_state();
        
        $cancel = new AbnormalOperation($data,$this->Order);
        $b = $cancel->update();
        if( $b == false ){
            return false;
        }

        // 更新 订单状态
        if($data['unqualified_result'] == EvaluationStatus::UnqualifiedGoUse){
            $State = new OrderHuijiingState($this->Order);
        }else if($data['unqualified_result'] == EvaluationStatus::UnqualifiedExchange){
            $State = new OrderHuanhuoingState($this->Order);
        }else if($data['unqualified_result'] == EvaluationStatus::UnqualifiedAccepted){
            $State = new OrderRefundingState($this->Order);
        }
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('异常处理', $old_state, $new_state));
        return true;

   }
   /**
     * 申请退款
     * @param array $data
     * [
     *      'order_id'=>'',     //【必须】订单ID
     *      'payment_id'=>'',   //【必须】支付单ID
     *      'should_amount'=>'',//【必须】应退金额
     *      'should_remark'=>'',//【必须】修改备注
     * ]
     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function create_refund($data){
        // 操作前状态
        $old_state = $this->get_state();
        $create_refund =new CreateRefundOperation($data);
        $b = $create_refund->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderRefundingState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('申请退款', $old_state, $new_state));
        return true;

    }
    /**
     * 创建发货单
     * @param array $data
     *     'order_id' =>'',          //【必须】int 订单id
     *     'business_key' =>"",      //【必须】business_key
     *     'goods_id'=>"",           //【必须】goods_id
     *     'admin_id'=>'',           //【可选】操作管理员
     *     'evaluation_id'=>'',      //【可选】evaluation_id     
     *     'name' =>'',              //【必须】//名称
     *     'mobile' =>"",            //【必须】//手机
     *     'address'=>"",            //【必须】//地址
     *     'remark'=>'',             //【可选】//备注
     *     'province_id'=>'',        //【可选】//省份id
     *     'city_id'=>'',            //【可选】//市
     *     'country_id' =>'',        //【必须】//国家
     * 
     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function create_delivery($data){
        // 操作前状态
        $old_state = $this->get_state();
        $cancel = new BuildDeliveryOperation($data);
        $b = $cancel->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderHuanhuoingState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('回寄中', $old_state, $new_state));
        return true;
    }
    /**
     * 解除资金预授权
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function remove_authorize($data){
        // 操作前状态
        $old_state = $this->get_state();
        $Operation =new FundsThawedOperation($this->Order,$data);
        $b = $Operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new FundsThawedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('解冻资金预授权', $old_state, $new_state));

        return true;

    }

    /**
     * 取消订单
     * @param array $data     【必须】取消保存数据
     * [
     *      'order_id' =>'',            【必须】int；订单ID
     *      'reason_id' => '',          【可选】int；取消原因ID
     *      'reason_text' => '',        【可选】string；附加原因描述，可以为空
     * ]
     */
    public function cancel_order( $data ) {
        // 操作前状态
        $old_state = $this->get_state();

        $cancel =new CancelOperation($data);
        $b = $cancel->update();
        if( $b == false ){
            // 取消失败
            return false;
        }
        // 更新 订单状态
        $State = new CanceledState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('取消订单', $old_state, $new_state));
        return true;
    }
    /**
     * 买断申请
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'receive_id'=>'',   // 【可选】收货单ID 审核完成后才能有收货单 审核之后取消要必选
     * ]
     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function apply_for_buyout($data){
        // 操作前状态
        echo "暂无买断申请";
    }

   
}

