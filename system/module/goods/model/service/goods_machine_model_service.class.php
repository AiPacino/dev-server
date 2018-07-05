<?php
/**
 * 商品机型服务层
 * Class goods_machine_model_service
 */

class goods_machine_model_service extends service {
	public function _initialize() {
		$this->db = $this->load->table('goods/goods_machine_model');
	}

    /**
     * [get_lists 获取列表]
     * @return [type] [description]
     */
    public function get_list($where=[],$options = []){
        $result = $this->db->get_list($where, $options);
        return $result;
    }

    /**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap = array()){
        $result = $this->db->where($sqlmap)->count('id');
        if($result === false){
            $this->error = $this->db->getError();
            return false;
        }
        return $result;
    }

    /**
     * 添加
     * @param array $params
     * @return bool
     */
    public function add_machine_model($params = array()){
        $result = $this->db->update($params);
        if($result === FALSE){
            $this->error = $this->db->getError();
            return FALSE;
        }
        return $result;
    }

    /**
     * 编辑
     * @param $params
     * @return bool
     */
    public function edit_machine_model($params){
        runhook('before_edit_machine_model', $params);
        if((int)$params['id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->db->update($params);
        if($result === FALSE){
            $this->error = $this->db->getError();
            return FALSE;
        }
        return $result;
    }

    /**
     * [delete 删除，可批量删除]
     * @param  [fixed] $params [类型id]
     * @return [boolean]         [返回删除结果]
     */
    public function delete($params){
        $params = (array) $params;
        if(empty($params)){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $sqlmap = array();
        $sqlmap['id'] = array('IN',$params);
        $result = $this->db->where($sqlmap)->delete();
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * 获取一条数据
     * @param $id
     * @param $options
     * @return mixed
     */
    public function get_info($id, $options=[]){
        $result = $this->db->where(['id' => $id])->find($options);
        return $result;
    }

	/**
	 * [change_status 改变状态]
	 * @param  [int] $id [id]
	 * @return [boolean]     [返回更改结果]
	 */
	public function change_status($id){
		if((int)$id < 1){
			$this->error = lang('_param_error_');
			return FALSE;
		}
		$data = array();
		$data['status']=array('exp',' 1-status ');
		$result = $this->db->where(array('id' => $id))->save($data);
		if(!$result){
    		$this->error = lang('_operation_fail_');
    	}
    	return $result;
	}

    /**
     * [search_brand 关键字查找机型]
     * @param  [type] $keyword [description]
     * @return [type]          [description]
     */
    public function ajax_machine($keyword){
        $sqlmap = array();
        if($keyword){
            $sqlmap = array('name'=>array('LIKE','%'.$keyword.'%'));
        }
        $result = $this->db->where($sqlmap)->getField('id,name',TRUE);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    public function change_info($params){
        if((int)$params['id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->db->where(array('id'=>$params['id']))->save($params);
        if($result === false){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }
}
