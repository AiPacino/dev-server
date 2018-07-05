<?php

/**
 * 增加发送短信的记录
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */
class sms_service extends service {

    
    
    public function _initialize() {
	//实例化数据层
	$this->sms_table = $this->load->table('order2/sms');
    }

    /**
     * 创建debug记录
     * @param array   $data	      【必选】
     * array(
            'sms_no',
            'user_mobile',
            'order_no', //
            'json_data', //
     *      )
     * @author 
     * @return mixed    false:创建失败；int：创建成功，返回debug编码
     *
     */
    public function create($data = array()) {
        $data = filter_array($data, [
            'sms_no' => 'required',
            'user_mobile' => 'required',
            'order_no' => 'required',
            'json_data' => 'required',
            'response'=>'required',
        ]);
	$this->sms_table->create($data);

	return true;
    }

    /**
     * 获取
     * @param int $id	【必选】主键
     * @author 
     * @return  array 参考get_list参数
     */
    public function get_info($id) {
	$data = $this->sms_table->get_info($id);
	return $data;
    }

    /**
     *
     * @param array $where
     * @param array  $where 【可选】查询条件
     * [
     *      'sms_no' => '',	//【可选】
     *      'user_mobile' => '',	//【可选】int；主编号
     *      'order_id' => '',	//【可选】int；序列号
     *      'order_no' => '',	//【可选】int；位置
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
	$_list = $this->sms_table->get_list($where,$additional);
	return $_list;
    }

    /**
     * 获取符合条件的服务单记录数
     * @param   array	$where  参考 get_list() 参数说明
     * @return int 查询总数
     */
    public function get_count($where) {
	return $this->sms_table->get_count($where);
    }



}
