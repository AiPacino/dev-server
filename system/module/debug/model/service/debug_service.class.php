<?php

/**
 * 
 * @copyright (c) 2017, Huishoubao
 */
class debug_service extends service {

    
    
    public function _initialize() {
	//实例化数据层
	$this->error_table = $this->load->table('debug/debug_error');
    }

    /**
     * debug位置列表
     * @return array 位置列表
     */
    public function get_location_list(){
	return \zuji\debug\Location::getLocationList();
    }
    /**
     * 获取debug位置名称
     * @param int $location_id
     * @return string
     */
    public function get_location_name($location_id){
	$_list = $this->get_location_list();
	if( isset($_list[$location_id]) ){
	    return $_list[$location_id];
	}
	return '';
    }
    
    
    
    /**
     * 创建debug记录
     * @param array   $data	      【必选】
     * array(
     *      'location_id'   => '',//【必选】  int 位置标识
     *      'subject'	=> '',	//【必选】  string
     *      'data'	=>'',	//【可选】 string
     *      )
     * @author 
     * @return mixed    false:创建失败；int：创建成功，返回debug编码
     *
     */
    public function create($data = array()) {
	$data = filter_array($data, [
	    'location_id' => 'required|is_id',
	    'subject' => 'required',
	    'data' => 'required',
	]);
	$this->_input_format($data);
	if (count($data) != 4) {
	    return false;
	}
	list( $micro, $time ) = explode(' ',microtime());
	$microsecond = substr($micro, 2, 4);
	// 生成debug编号
	$data['main_no'] = date('Ymd',$time);
	$data['sub_no'] = intval(date('His',$time).$microsecond);
	
	$this->error_table->create($data);
	
	return true;
    }

    /**
     * 获取
     * @param array   $debug_no	【必选】
     * @author 
     * @return  array 参考get_list参数
     */
    public function get_info($debug_no) {
	$data = $this->error_table->get_info($debug_no);
	$this->_output_format($data);
	return $data;
    }

    /**
     * 
     * @param array $where
     * [
     *      'main_no' => '',	//【可选】int；主编号
     *      'sub_no' => '',	//【可选】int；序列号
     *      'location_id' => '',	//【可选】int；位置
     *      'begin_time' => '',	//【可选】int；开始时间戳
     *      'end_time' => '',	//【可选】int；结束时间戳
     * ]
     * @param array $additional
     * [
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'order'	=> '',             【可选】string 排序；默认 time_DESC：时间倒序；time_ASC：时间顺序
     * ]
     * @return type
     */
    public function get_list($where = [], $additional = []) {
	$_list = $this->error_table->get_list($where,$additional);
	foreach ($_list as &$_item) {
	    $this->_output_format($_item);
	}
	return $_list;
    }

    /**
     * 获取符合条件的服务单记录数
     * @param   array	$where  参考 get_list() 参数说明
     * @return int 查询总数
     */
    public function get_count($where) {
	return $this->error_table->get_count($where);
    }

    /**
     * 输入过滤
     * @param array $info
     */
    private function _input_format( &$info ){
	if( is_object($info['data'])  ){
	    $info['data_type'] = 2;// 序列化
	    $info['data'] = serialize($info['data']);
	}
	elseif( is_array($info['data'])  ){
	    $info['data_type'] = 3;// json
	    $info['data'] = json_encode($info['data']);
	}else{
	    $info['data_type'] =1;// 字符串
	    $info['data'] = $info['data'];
	}
    }
    
    /**
     * 输出过滤
     * @param type $info
     */
    private function _output_format( &$info ){
	$info['debug_no'] = $info['main_no'].'-'.$info['sub_no'];
	$info['create_time_show'] = date('Y-m-d H:i:s',$info['create_time']);
	$info['location_name'] = $this->get_location_name($info['location_id']);
	if( $info['data_type'] == 2 ){
	    $info['data'] = unserialize($info['data']);
	}elseif( $info['data_type'] == 3 ){
	    $info['data'] = json_decode($info['data'],true);
	}
    }
    
    
}
