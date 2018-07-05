<?php
namespace oms\observer;

/**
 * LogObserver  日志
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class LogObserver extends OrderObserver {
    
    /**
     * 操作员
     * @var \oms\operator\Operator 
     */
    private $Operator = null;
    private $action = "";
    private $msg = "";


    public function __construct(\oms\observer\OrderObservable $Observable, $action = '', $msg = '' ){
	    parent::__construct($Observable);
        $this->action = $action;
        $this->msg    = $msg;
    }
    
    public function set_operator( \oms\operator\Operator $Operator ){
	$this->Operator = $Operator;
    }
    
    public function get_id() {
	return 'order-log-observer';
    }
    /*
     * @array data 
    */
    public function update() {
        $status = $this->Observable->get_status();
        if($status === false){
            return false;
        }

        $Order = $this->Observable->get_order();

        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2_log');

        $order_no = $Order->get_order_no();
        $operator_type = $this->operator_type != "" ? $this->operator_type : $this->Operator->get_type();
        $log = [
            'order_no'      => $order_no,
            'action'        => $this->action,
            'operator_id'   => $this->Operator->get_id(),
            'operator_name' => $this->Operator->get_username(),
            'operator_type' => $this->Operator->get_type(),
            'msg'           => $this->msg,
            'system_time'   => time()
        ];
        $add_log = $order_table->add($log);

        if(!$add_log){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '写入日志失败', $log);
        }
        return true;
    }

    
}
