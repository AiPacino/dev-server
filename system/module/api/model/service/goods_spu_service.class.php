<?php
/**
 *		商品模型数据层
 *      [wangjinlin]
 */
class goods_spu_service extends service {
	public function _initialize() {
		$this->spu_db = $this->load->table('api/goods_spu');
        $this->sku_db = $this->load->table('api/goods_sku');
		$this->brand_db = $this->load->table('api/brand');
	}
	/**
	 * 获取商品万能表格列表
	 */
	public function get_lists(){
        // 全部商品 spu表 casualty
        $lists = $this->spu_db->field("id,name,subtitle,thumb,imgs,brand_id,description,sku_total,yiwaixian,min_price,min_month")->where(array('status'=>1))->select();
        foreach ($lists as &$item){
            $item['flag'] = 'spu';
            $_imgs = json_decode($item['imgs'],true);
            $item['imgs'] = $_imgs?$_imgs:[];
        }
        // 全部品牌
        $brands = $this->brand_db->field("id,name")->where("status=1")->select();

		return array('brand_list' => $brands,'spu_list' => $lists);
	}
	/**
     * 获取商品详情
     */
	public function get_detail($id){
        if ( $id < 1) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
	    $spu_row = $this->spu_db->field("id,name,subtitle,thumb,imgs,brand_id,description,sku_total,yiwaixian,min_price,min_month")->where(array('id'=>$id))->find();
        $sku_select = $this->sku_db->field("sku_id,sku_name,number,deposit,shop_price,zuqi,spec")->where(array('spu_id'=>$spu_row['id']))->select();
        $spu_row['flag'] = 'spu';
        $spu_row['sku_list']=$sku_select;
        return array('spu_info'=>$spu_row);
    }

}