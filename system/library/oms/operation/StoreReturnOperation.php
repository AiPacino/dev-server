<?php
namespace oms\operation;

use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
        
/**
 * 换货
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn> 
 *
 */
class StoreReturnOperation implements OrderOperation
{

    private $order_id     = 0;
    private $imei         = "";
    
    
    public function __construct( $data ){
       
        $this->order_id     = $data['order_id'];
        $this->imei         = $data['imei'];
    }
    
     /**
     * 换货
     * @param array $data
     * [
     *      'order_no'=>'',     // 【必须】订单ID
     *      'imei'=>'',         // 【必须】IMEI号
     * ]
     * @author maixaoyu <maxiaoyu@huishoubao.com.cn>
     */

    public function update(){
        if($this->order_id  ==  0 ){
            set_error("参数错误");
            return false;
        }
        if($this->imei  ==  "" ){
            set_error("参数错误");
            return false;
        }
      

        $load = \hd_load::getInstance();
       
       
        $order_service    = $load->service('order2/order');
        $goods_table      = $load->table('order2/order2_goods');
       
        //查询订单信息
        $where = array(
            'order_id'=>$this->order_id
        );
        $order_info  =  $order_service->get_order_info($where);
        $goods_id    =  $order_info['goods_id'];
        // 商品信息
        $goods_info = $order_service->get_goods_info($goods_id);

        // 修改imei
        $goods_table->where(['goods_id'=>$goods_id])->save(['imei1'=>$this->imei]);

       return true;
        
    }
}