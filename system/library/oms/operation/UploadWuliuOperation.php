<?php
namespace oms\operation;

use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\operator\Operator;
use zuji\order\OrderStatus;

/**
 * 更新收货单物流
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn> 
 *
 */
class UploadWuliuOperation implements OrderOperation
{

    private $receive_id=0;
    private $wuliu_channel_id =0;
    private $wuliu_no="";
    
    
    public function __construct( $data ){
        $this->receive_id=$data['receive_id'];
        $this->wuliu_channel_id=$data['wuliu_channel_id'];
        $this->wuliu_no=$data['wuliu_no'];
    }
    
    public function update(){
       $load = \hd_load::getInstance();
       $order_table = $load->table('order2/order2');
       $order_service =$load->service('order2/order');
       $receive_table =$load->table('order2/order2_receive');
    
       if(!isset($this->receive_id) || !isset($this->wuliu_channel_id) || !isset($this->wuliu_no)){
           set_error("参数错误");
           return false;
       }
       $data = [
           'wuliu_channel_id' =>$this->wuliu_channel_id,
           'wuliu_no'  => $this->wuliu_no,
           'update_time'=> time(),
       ];
       $b =$receive_table->where(['receive_id'=>$this->receive_id])->save($data);

       if(!$b){
           set_error("更新收货单失败");
           return false;
       }
       return true;
        
    }
}