<?php
class service extends hd_base {
    
    public function __call($method, $args) {
	    throw new Exception(lang('_method_not_exist_', 'language', array('class' => get_class($this),'method' => $method)));
    }
    
    /**
     * 设置单条记录的缓存数据
     * @param string $id    索引
     * @param array $info   数据
     */
    protected function _set_info_check( $id, $info ){
	if( !is_array($info) ){
	    return false;
	}
	$Redis = \zuji\cache\Redis::getInstans();
	$Redis->hset($this->hash_key, $id, json_encode($info) );
    }
    /**
     * 获取单条记录的缓存
     * @param string $id    索引
     * @return mixed	false：失败；array：数据
     */
    protected function _get_info_check( $id ){
	$Redis = \zuji\cache\Redis::getInstans();
	$info = $Redis->hget($this->hash_key, $id);
	if($info){
	    $info = json_decode($info,true);
	}
	return $info;
    }
    
    /**
     * 删除单条缓存
     * @param string $id
     * @return boolean
     */
    protected function _del_info_check( $id ){
	$Redis = \zuji\cache\Redis::getInstans();
	$Redis->hdel($this->hash_key, $id);
	return true;
    }
  
    /**
     * 获取所有缓存结果
     * @return mixed	false：失败；array：数据
     */
    public function _get_all_check( ){
	$Redis = \zuji\cache\Redis::getInstans();
	$list = $Redis->hgetall($this->hash_key);
	if($list){
	    foreach( $list as $k => $item){
		$list[$k] = json_decode($item,true);
	    }
	}
	return count($list)?$list:false;
    }
    /**
     * 设置 支持 多值查询条件
     * @param string $field   字段名称
     * @param array $where   查询条件
     * @return boolean
     */
    protected function _parse_where_field_array( $field, &$where ){
	//支持多个
	if( isset($where[$field]) ){
	    if( is_string($where[$field]) ){
		$where[$field] = explode(',',$where[$field]);
	    }elseif( is_int($where[$field]) ){
		$where[$field] = [$where[$field]];
	    }
	    if( !is_array($where[$field]) ){
		return false;
	    }
	    if(count($where[$field])==1 ){
		$where[$field] = $where[$field][0];
	    }
	    if(count($where[$field])>1 ){
		$where[$field] = ['IN',$where[$field]];
	    }
	}
	return true;
    }
    
}	