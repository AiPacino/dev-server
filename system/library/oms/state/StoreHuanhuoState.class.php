<?php
namespace oms\state;
use oms\operation\StoreHuanhuoOperation;

/**
 * StoreHuanhuoState  门店换货
 *
 * @author maxiaoyu <maxioayu@huishoubao.com.cn>
 */
class StoreHuanhuoState extends State {

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


    //判断是否可以门店换货
    public function allow_to_store_huanhuo(){
        return true;
    }

    /**
     * 门店换货
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'imei'=>'',         // 【必须】IMEI号
     *      'serial_number'=>'',// 【必须】序列号
     * ]
     * @author maixaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function store_huanhuo($data = array()){

        // 操作前状态
        $huanhuo = new StoreHuanhuoOperation($data);
        $b = $huanhuo->update();
        if( $b == false ){
            return false;
        }
        return true;

    }


}
