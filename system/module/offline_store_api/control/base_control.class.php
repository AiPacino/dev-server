<?php
/**
 * 线下渠道基本类
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/3 0003-下午 5:04
 * @copyright (c) 2017, Huishoubao
 */

class base_control extends control
{

    protected $auth_token = '';
    protected $member = array();
    protected $params = [];

    public function _initialize() {
        parent::_initialize();
        if( IS_API == false ){
            return ;
        }

        $authToken = api_auth_token();
        if( $this->session_valid_id($authToken) ){
            session_id( $authToken );
        }
        session_start();
        $this->auth_token = session_id();
        $this->params = api_params();
        session('__last_time__',time());
        session('__auth_token__',$this->auth_token);

    }

    final protected function session_valid_id($session_id){
        return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
    }


    /**
     * 记录订单操作日志
     * @param string $order_no	订单编号
     * @param string $action	操作名称
     * @param string $msg	操作说明
     */
    final protected function add_order_log( $operator_id,$operator_name,$order_no, $action, $msg ){
        $order_log = $this->load->service('order2/order_log');
        $r = $order_log->add([
            'order_no' => $order_no,
            'action' => $action,
            'msg' => $msg,
            'operator_id' => $operator_id,
            'operator_name' => $operator_name,
            'operator_type' => 2,
        ]);
    }

    private function _get_debug_service(){
        if( $this->debug_service==null){
            $this->debug_service = $this->load->service('debug/debug');
        }
        return $this->debug_service;
    }

    final protected function debug_error($location_id,$subject,$data){
        $this->_get_debug_service()->create([
            'location_id' => $location_id,
            'subject' => $subject,
            'data' => $data,
        ]);
    }
}