<?php
use zuji\order\DeliveryStatus;
use zuji\Business;
/**
 * 		图片
 *      @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 */
class order_image_service extends service {

	public function _initialize() {
        $this->table = $this->load->table('order2/order2_image');
	}

    /**
     * 查取单条数据
     * @param $order_id 订单id
     * @return [result]
     */
    public function get_one($order_id){

        if( !isset($order_id) || $order_id < 1){
            set_error('参数错误');
            return false;
        }
        $where = array('order_id'=>$order_id);
        return $this->table->get_img_info($where);
    }
    
    
}