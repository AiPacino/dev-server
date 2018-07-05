<?php

/**
 * 问题原因列表服务层
 * @access public （访问修饰符）
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class reason_service extends service {

    public function get_order_cancel(){
        $data = zuji\order\Reason::$_ORDER_QUESTION[zuji\order\Reason::ORDER_CANCEL];
        $list = array();
        foreach($data as $key=>$val){
            $list[] = array('id'=>$key,'title'=>$val);
        }
        return $list;
    }
    public function get_order_return(){
        $data = zuji\order\Reason::$_ORDER_QUESTION[zuji\order\Reason::ORDER_RETURN];
        $list = array();
        foreach($data as $key=>$val){
            $list[] = array('id'=>$key,'title'=>$val);
        }
        return $list;
    }
}
