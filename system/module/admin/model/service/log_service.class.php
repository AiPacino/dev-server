<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class log_service extends service {
	public function _initialize() {
		$this->model = $this->load->table('admin/log');
	}


    /**
     * 单条记录操作日志
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function create_log($data){
	    if(empty($data['user_id'])){
            $this->error = lang('_param_error_');
            return false;
        }
        if(empty($data['action_ip'])){
            $this->error = lang('_param_error_');
            return false;
        }
        if(empty($data['remark'])){
            $this->error = lang('_param_error_');
            return false;
        }
        if(empty($data['url'])){
            $this->error = lang('_param_error_');
            return false;
        }
        if(empty($data['dateline'])){
            $this->error = lang('_param_error_');
            return false;
        }
        $result = $this->model->create_log($data);
        if(!$result){
            $this->error = $this->model->getError();
        }
        return $result;
    }

	public function get_lists($sqlmap = array(), $limit = 10,$page = 1,$order = 'id DESC'){
        $where = $this->_parse_log_where($sqlmap);
		$result = $this->model->where($where)->page($page)->limit($limit)->order($order)->select();
		if(!$result){
    		$this->error = $this->model->getError();
    	}
    	return $result;
    }
	/**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap){
        $where = $this->_parse_log_where($sqlmap);
        $result = $this->model->where($where)->count();
        if($result === false){
            $this->error = $this->model->getError();
            return false;
        }
        return $result;
    }
    /**
	* [删除]
	* @param array $ids 主键id
	*/
	public function delete($ids) {
		if(empty($ids)) {
			$this->error = lang('_param_error_');
			return false;
		}
		$_map = array();
		if(is_array($ids)) {
			$_map['id'] = array("IN", $ids);
		} else {
			$_map['id'] = $ids;
		}
		$result = $this->model->where($_map)->delete();
		if($result === false) {
			$this->error = $this->model->getError();
			return false;
		}
		return true;
	}
	private function _parse_log_where($where){
	    //参数过滤
        $where = filter_array($where,[
            'begin_time' => 'required',
            'end_time' => 'required',
            'option_id' => 'required|is_int',
            'user_id'=> 'required',
            'action_ip' => 'required|is_id',
        ]);
        // 结束时间（可选），默认为为当前时间
        if( !isset($where['end_time']) ){
            $where['end_time'] = time();
        }
        // 开始时间（可选）
        if( isset($where['begin_time'])){
            if( $where['begin_time']>$where['end_time'] ){
                return false;
            }
            $where['dateline'] = ['between',[$where['begin_time'], $where['end_time']]];
        }else{
            $where['dateline'] = ['LT',$where['end_time']];
        }
        unset($where['begin_time']);
        unset($where['end_time']);
        if( !isset($where['option_id']) ) {
            $where['option_id'] = ['LIKE',$where['option_id']. '%'];
        }
        if( !isset($where['user_id']) ) {
            $where['user_id'] = ['LIKE',$where['user_id']. '%'];
        }
        return $where;
    }
}
