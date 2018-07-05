<?php
use zuji\order\DeliveryStatus;
use zuji\Business;
use zuji\debug\Location;
use zuji\order\OrderStatus;
use zuji\Config;
/**
 * 发货单控制器
 * 1）
 */

hd_core::load_class('base','order2');
class wuliu_control extends base_control {


    /**
     * 构造方法
     */
    public function _initialize() {
        parent::_initialize();


    }

    /**
     *发货单详情
     */
    public function detail(){

        $this->wuliu_service = $this->load->service('order2/wuliu');
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        $type = null;
        $code = $this->__get_wuliu_no($type);
        if( $type=="delivery" ) {
            $type = '发货';
        }elseif($type=="receive"){
            $type = '收货';
        }

        $request = json_encode(['mailno'=>$code]);
        $url = config("Api_Logistics_Url");

        $wuliu_info = array();
        if( $code ) {
            //发起post请求
            $result    = zuji\Curl::post($url,$request);
            $wuliu_info = json_decode($result,true);
            if( $wuliu_info ){
                foreach($wuliu_info['data'] as &$v){
                    $v['barTm'] = date("Y-m-d H:i:s",strtotime($v['barTm']));
                }
            }
        }

        $this->load->librarys('View')
            ->assign('inner', $inner)
            ->assign('wuliu_no',$code)
            ->assign('wuliu_name', $type)
            ->assign('wuliu_info', $wuliu_info)
            ->display('wuliu_detail');
    }

    private function __get_wuliu_no(&$type=null){
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->receive_service = $this->load->service('order2/receive');

        //发货单物流信息
        $delivery_id = intval(trim($_GET['delivery_id']));
        //收货单物流信息
        $receive_id = intval(trim($_GET['receive_id']));
        if ($delivery_id < 1 && $receive_id < 1){
            showmessage(lang('_error_action_'), "", 0);
        }
        if( $delivery_id ) {
            $delivery_info = $this->delivery_service->get_info($delivery_id);
            $type = 'delivery';
            return $delivery_info['wuliu_no'];
        }
        if( $receive_id ){
            $receive_info = $this->receive_service->get_info($receive_id);
            $type = 'receive';
            return $receive_info['wuliu_no'];
        }
    }
}