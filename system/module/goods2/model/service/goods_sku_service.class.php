<?php

/**
 * 		子商品数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class goods_sku_service extends service {

    public function _initialize() {
        $this->sku_db = $this->load->table('goods2/goods_sku');
        $this->spu_service = $this->load->service('goods2/goods_spu');
    }
    /**
     *  退款成功时 库存 加1
     *  @param int $spu_id 
     *  @param int $sku_id
     *  @return boolean true：成功 false:失败
     */
    public function add_number($spu_id,$sku_id){
        if($spu_id<1){
            set_error("spu_id 错误");
            return false;
        }
        if($sku_id<1){
            set_error("sku_id 错误");
            return false;
        }
	    try{
	        // 开启事务
	        $this->sku_db->startTrans();
	        $up_sku =$this->sku_db->add_number($sku_id);
	        if(!$up_sku){
	            $this->sku_db->rollback();
	            set_error("修改sku库存失败");
	            return false;
	        }
	        $up_spu=$this->spu_service->add_number($spu_id);
	        if(!$up_spu){
	            $this->sku_db->rollback();
	            set_error("修改spu库存失败");
	            return false;
	        }
	        
	    }catch (\Exception $exc){
	        // 关闭事务
	        $this->sku_db->rollback();
	        set_error("异常错误");
	        return false;
	    }
	    // 提交事务
	    $this->sku_db->commit();
	    return true;
        
    }
    /**
     *  退款成功时 库存 减1
     *  @param int $spu_id 
     *  @param int $sku_id
     *  @return boolean true：成功 false:失败
     */
    public function minus_number($spu_id,$sku_id){
        if($spu_id<1){
            set_error("spu_id 错误");
            return false;
        }
        if($sku_id<1){
            set_error("sku_id 错误");
            return false;
        }
        try{
            // 开启事务
            $this->sku_db->startTrans();
            $up_sku =$this->sku_db->minus_number($sku_id);
            if(!$up_sku){
                $this->sku_db->rollback();
                set_error("修改sku库存失败");
                return false;
            }
            $up_spu=$this->spu_service->minus_number($spu_id);
            if(!$up_spu){
                $this->sku_db->rollback();
                set_error("修改spu库存失败");
                return false;
            }
             
        }catch (\Exception $exc){
            // 关闭事务
            $this->sku_db->rollback();
            set_error("异常错误");
            return false;
        }
        // 提交事务
        $this->sku_db->commit();
        return true;
    
    }

    /**
     * [change_sku_info 改变商品货号]
     * @param  [array] $params []
     * @return [boolean]     [返回更改结果]
     */
    public function change_sku_info($params){
        if((int)$params['sku_id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->sku_db->where(array('sku_id' => $params['sku_id']))->save($params);
        return $result;
    }
   
    /**
     * [根据kpuid获取单条sku商品]
     * @param  [type] $field [字段信息]
     * @return [type]         [boolean]
     */
    public function api_get_info($id,$field=""){
        if($id>0){
            $data = $this->sku_db->get_info($id,$field);
            return $data;
        }
        return false;
    }
    /**
     * [根据kpuid获取所有sku商品]
     * @param  [type] $field [字段信息]
     * @return [type]         [boolean]
     */
    public function api_get_list($where="",$field=""){
        $data    = $this->sku_db->get_list($where,$field);
        return $data;
    }
}
