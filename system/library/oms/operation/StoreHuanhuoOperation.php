<?php
namespace oms\operation;

use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\operator\Operator;
use zuji\order\OrderStatus;
use zuji\order\ServiceStatus;

/**
 * 上传图片IMEI
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 *
 */
class StoreHuanhuoOperation implements OrderOperation
{

    private $order_id         = 0;
    private $imei1            = "";
    private $serial_number    = "";


    public function __construct( $data ){

        $this->order_id         = $data['order_id'];
        $this->imei1            = $data['imei1'];
        $this->serial_number    = $data['serial_number'];
    }

    /**
     * 上传图片 IMEI号
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'imei1'=>'',        // 【必须】IMEI号
     *      'serial_number'=>'',// 【必须】序列号
     * ]
     * @author maixaoyu <maxiaoyu@huishoubao.com.cn>
     */

    public function update(){
        $data['order_id']       = $this->order_id;
        $data['imei1']          = $this->imei1;
        $data['serial_number']  = $this->serial_number;

        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'imei1' => 'required',
            'serial_number' => 'required'
        ]);

        if(count($data) < 3 ){
            set_error("参数错误");
            return false;
        }

        $order_id       = $data['order_id'];

        $load = \hd_load::getInstance();

        $order_service    = $load->service('order2/order');
        $goods_table      = $load->table('order2/order2_goods');

        //查询订单信息
        $where = array(
            'order_id'=>$order_id
        );
        $order_info  =  $order_service->get_order_info($where);
        if(!$order_info){
            set_error("生成服务单失败");
            return false;
        }
        // 商品信息
        $goods_info  =  $order_service->get_goods_info($order_info['goods_id']);

        // 修改imei
        $goods_data['imei1']          = $data['imei1'];
        $goods_data['serial_number']  = $data['serial_number'];

        $goods_result = $goods_table->where(['goods_id'=>$order_info['goods_id']])->save($goods_data);
        if(!$goods_result){
            set_error("换货失败");
            return false;
        }
        return true;

    }
}