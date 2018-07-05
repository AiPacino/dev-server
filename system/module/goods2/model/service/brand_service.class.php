<?php
/**
 *		品牌模型数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class brand_service extends service {
	public function _initialize() {
		$this->brand_db = $this->load->table('goods2/brand');;
	}
    /**
     * API获取所有品牌数据
     * @param  [$where] $field [字段信息]
     * @return [Array]         [boolean]
     */
    public function api_get_list($where="",$field="",$order=""){
        $order = $order?$order:'sort desc';
        $data    = $this->brand_db->get_list($where,$field,$order);
        return $data;
    }
}