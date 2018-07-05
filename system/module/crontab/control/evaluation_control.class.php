<?php
/**
 * 自动审核
 * User: wangjinlin
 * Date: 2018/1/8
 * Time: 下午4:46
 */
//hd_core::load_class('base', 'order2');
use zuji;
use oms\state;
use oms\operator;
use zuji\debug\Debug;
use zuji\debug\Location;
class evaluation_control extends control
{
    public function _initialize()
    {
        parent::_initialize();
        /*
         * 白名单验证
         */
        //get_client_ip();
        //实例化 table
        $this->order_table = $this->load->table('order2/order2');
    }

    /*
     * 半分钟更新线下审核状态
     */
    public function index(){

        $where='status='.oms\state\State::OrderCreated.' AND business_key='.zuji\Business::BUSINESS_STORE;
        $order_list = $this->order_table
            ->field($this->order_table->fields)
            ->where($where)
            ->select();

        $Operator = new oms\operator\System(operator\System::Type_System,'system');
        foreach ($order_list as $key=>$value){
            try {
                $trans = $this->order_table->startTrans();
                if (!$trans) {
                    Debug::error(Location::L_Order, '系统自动审核失败', $value);
                    exit;
                }
                $Orders = new \oms\Order($value);
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver($OrderObservable);
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver($OrderObservable, "审核订单", "系统自动审核订单");
                $LogObserver->set_operator($Operator);

                if ($Orders->allow_to_store_check_order()) {
                    $b = $Orders->store_check_order([]);
                    if (!$b) {
                        $this->order_table->rollback();
                        Debug::error(Location::L_Order, '系统自动审核失败', $value);
                    }
                    $this->order_table->commit();
                }
            }catch (\Exception $exc){
                $this->order_table->rollback();
                Debug::error(Location::L_Order, '系统自动审核失败', $value);
            }
        }
        echo '操作成功';exit;
    }
}