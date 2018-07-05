<?php
/**
 *		商品规格模型数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class spec_service extends service {
	public function _initialize() {
		$this->spec_db = $this->load->table('goods2/spec');;
	}
    /**
     * [获取所有规格信息]
     * @param  [type] $field [字段信息]
     * @return [type]         [boolean]
     */
    public function api_get_list($where=""){
        $data = $this->spec_db->get_list($where);
        return $data;
    }
}