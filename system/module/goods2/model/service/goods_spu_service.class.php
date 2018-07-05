<?php
/**
 *		商品模型数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class goods_spu_service extends service {
	public function _initialize() {
		$this->spu_db = $this->load->table('goods2/goods_spu');;
	}
	
	/**
	 *  支付成功时 库存 减1
	 */
	public function minus_number($spu_id){
	    //校验
		if($spu_id <1){
	        set_error("spu ID 错误");
	        return false;
	    }
	    return $this->spu_db->minus_number($spu_id);
	}
	/**
	 *  支付成功时 库存 加1
	 */
	public function add_number($spu_id){
	    //校验
	    if($spu_id <1){
	        set_error("spu ID 错误");
	        return false;
	    }
	    return $this->spu_db->add_number($spu_id);
	}
    /**
     * [获取单条商品]
     * @param  [type] $field [字段信息]
     * @return [type]         [boolean]
     */
	public function api_get_info($id=0,$field=""){
        $field    = $field?$field:'id,name,specs,subtitle,thumb,imgs,brand_id,catid,description,sku_total,yiwaixian,min_price,min_month,max_month,min_zuqi_type,max_zuqi_type,content,status,peijian,channel_id,contract_id';
        $data = $this->spu_db->get_info($id,$field);
        return $data;
	}
    /**
     * [获取多条商品信息]
     * @param  [type] $field [字段信息]
     * @return [type]         [boolean]
     */
    public function api_get_list($where="",$field=""){
        $field    = $field?$field:'id,name,subtitle,thumb,imgs,brand_id,catid,description,sku_total,yiwaixian,min_price,min_month,max_month,min_zuqi_type,max_zuqi_type,status,channel_id';
        $order = "sort desc";
        if(isset($where['channel_id'])){
            $data = $this->spu_db->get_hash_list_by_channel_id($where['channel_id']);
            if(empty($data)){
                $data = $this->spu_db->get_list($where,$field,$order);
                if($data){
                    $this->spu_db->set_hash_by_spu($where['channel_id'], $data);
                }
            }
        }else{
            $data = $this->spu_db->get_list($where,$field,$order);
        }

        return $data;
    }
}