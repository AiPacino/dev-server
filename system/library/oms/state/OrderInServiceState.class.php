<?php
namespace oms\state;
use oms\operation\ApplyReturnOperation;
use oms\operation\CloseServiceOperation;
use oms\operation\MiniAlipayCloseOperation;
use zuji\Config;

/**
 * OrderInServiceState  租用中
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class OrderInServiceState extends State {
    
    public function get_state(){
       return State::OrderInService;
    }
    
    public function get_name(){
       return '租用中';
    }
    
    public function get_client_name(){
       return '租用中';
    }
    //后端操作列表
    public function get_operation_list(){
        return [];
    }

    //判断是否允许 买断
    public function allow_to_apply_for_buyout(){
        return true;
    }

    //判断是否允许申请退货 
    public function allow_to_apply_for_return() {
        return true;
    }
    //买断
    public function apply_for_buyout($data){
       echo '买断';
    }
    
    //是否允许关闭 订单/服务
    public function allow_to_close_service(){
        return true;
    }
    //是否可以退款
    public function allow_to_refund(){
        if($this->Order->get_payment_type_id() == Config::MiniAlipay){
            return true;
        }
        return false;
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
     *     'business_key' =>'',     //【必须】int;business_key
     * )
     * @return mixed    false：失败；int：退货单ID
     * 当创建失败时返回false；当创建成功时返回退货单ID
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function apply_for_return($data){
        // 操作前状态
        $old_state = $this->get_state();
        
        $operation =new ApplyReturnOperation($data);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderReturnCheckingState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('申请退货', $old_state, $new_state));
        return true;
    }
    
    /**
     * 关闭服务
     * @param array $data
     * [
     *      'remark'=>'',//【必须】 修改备注
     * ]
     */
    public function close_service($data){      
        // 操作前状态
        $new_data =[
            'remark'=>$data['remark'],
            'order_id'=>$this->Order->get_order_id(),
            'service_id'=>$this->Order->get_service_id(),
        ]; 
        $old_state = $this->get_state(); 
        $operation =new CloseServiceOperation($new_data,$this->Order);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new ClosedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('关闭服务', $old_state, $new_state));
        return true; 
    }

    /**
     * 小程序退款操作
     * @param array $data
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function refund($data){
        // 操作前状态
        if($this->Order->get_payment_type_id() != Config::MiniAlipay){
            return false;
        }
        $old_state = $this->get_state();

        // 更新 订单状态
        $refund = new MiniAlipayCloseOperation($this->Order,$data);

        $b = $refund->update();
        if( $b == false ){
            return false;
        }
        // 操作后状态值
        $State = new ClosedState($this->Order);
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('关闭订单', $old_state, $new_state));
        return true;
    }
}
