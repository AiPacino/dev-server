<?php

/**
 * 	   物流
 *   @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class logistics_service extends service {

	public function _initialize() {

		$this->logistics = $this->load->table('order/delivery');
	}

     /**
      * @param  string $field 获取的字段
      * @param  array  $where sql条件
      * @return [type]
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      */
     public function getField($field = '',$sqlmap = array()) {
         
         return $this->logistics->getFields($field,$sqlmap);
     }
    /**
     * @param  string $field 获取的字段
     * @param  array  $where sql条件
     * @return [type]
     * @author limin <limin@huishoubao.com.cn>
     */
    public function get_api_list($field = '',$where = array()) {
        $where = filter_array($where,[
            'enabled' => 'required'
        ]);
        return $this->logistics->get_api_list($field,$where);
    }
     /**
      * @param int id
      * @return array
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      */
     public function get_name($id){
         
       $logistics =$this->logistics->where(['id'=>$id])->find();
       return $logistics['name'];
     }



}