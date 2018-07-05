<?php


hd_core::load_class('api', 'api');
/**
 * 电子合同
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class contract_control extends user_control {

    /**
    * 订单电子合同查询
    * @return $data = [
     *      ''
     * ]
    * @author limin
    */
    public function get() {
        $params   = $this->params;
        $params = filter_array($params,[
            'order_no'=>'required',
        ]);
        if(!$params['order_no']){
            api_resopnse( [], ApiStatus::CODE_20001 ,'order_no必须');
            return;
        }
        $where = [
            'order_no'=>$params['order_no'],
            'user_id'=>$this->member['id']
        ];
        $this->contract = $this->load->table("order2/order2_contract");
        $result = $this->contract->where($where)->find();
        api_resopnse( $result, ApiStatus::CODE_0 );
        return;
        
    }
    
}
