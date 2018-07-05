<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class node_service extends service {
    protected $sqlmap = array();

    public function _initialize() {
        $this->model = $this->load->table('node');
    }
    public function setAdminid($admin_id) {
        return $this;
    }

    public function getAll() {
        $this->sqlmap['status'] = 1;
        $result =  $this->model->where($this->sqlmap)->order('sort ASC,id ASC')->select();
        return $this->_format($result);
    }

    public function get_lists($parent_id = 0,$order = '`sort` ASC,`id` ASC'){
        $data = $data = $this->model->where(array('parent_id' => $parent_id, 'status' => ['GT', '-1']))->order($order)->select();
        $lists = array();
        foreach ($data AS $v) {
            $lists[] =array(
                'id'=>$v['id'],
                'sort'=>$v['sort'],
                'name'=>$v['name'],
                'parent_id' =>$v['parent_id'],
                'status'=>$v['status'],
                'm'=>$v['m'],
                'c'=>$v['c'],
                'a'=>$v['a'],
                'param'=>$v['param'],
            );

        }
        return $lists;
    }


	public function fetch_all_by_ids($ids, $status = -1) {
		$_map = array();
		if($ids) {
			$_map['id'] = array("IN", explode(",", $ids));
		}
        if($status > -1) {
            $_map['status'] = $status;
        }
		$result = $this->model->where($_map)->order('sort ASC,id ASC')->select();;
		return $this->_format($result);
	}

	public function get_checkbox_data(){
		return$this->model->where(array('status'=>1))->order('sort asc')->getField('id as id,parent_id,name',TRUE);
	}

    private function _format($data) {
        if(empty($data)) return false;
        $result = array();
        foreach($data as $k => $v) {
            $v['url'] = $v['url'] ? $v['url'] : url($v['m'].'/'.$v['c'].'/'.$v['a'], $param);
            $result[$k] = $v;
        }
        return $result;
    }
    /**
     * @param  string  获取的字段
     * @param  array    sql条件
     * @return [type]
     */
    public function getField($field = '', $sqlmap = array()) {
        $result = $this->model->where($sqlmap)->getfield($field);
        if($result === false){
            $this->error = $this->model->getError();
            return false;
        }
        return $result;
    }

    /*修改*/
    public function setField($data, $sqlmap = array()){
        if(empty($data)){
            $this->error = lang('_param_error_');
            return false;
        }
        $result = $this->model->where($sqlmap)->save($data);
        if($result === false){
            $this->error = $this->model->getError();
            return false;
        }
        return $result;
    }

    /**
     * [启用禁用节点]
     * @param string $id id标识
     * @return TRUE OR ERROR
     */
    public function change_status($id) {
        $result = $this->model->where(array('id' => $id))->save(array('status' => array('exp', '1-status')));
        if (!$result) {
            $this->error = $this->model->getError();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 返回指定节点下级节点
     * @param int $parent_id
     * @return array
     */
    public function get_children($parent_id = 0 ,$order = '`sort` ASC,`id` ASC') {
        $data = $this->model->where(array('parent_id' => $parent_id, 'status' => ['GT', '-1']))->order($order)->select();
        return $data;
    }

    /**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap = array()){
        $result = $this->model->where($sqlmap)->count('id');
        if($result === false){
            $this->error = $this->model->getError();
            return false;
        }
        return $result;
    }

    /**
     * 获取指定地区信息
     * @param int $id
     * @return array
     */
    public function fetch_by_id($id) {
        return $this->model->find($id);
    }

    /**
     * 获取指定地区的所有上级地区数组
     * @param int $id 地区主键ID
     * @return array
     */
    public function fetch_parents($id, $isclear = true) {
        static $position;
        if($isclear === true) $position = array();
        //从缓存读取单条
        $r = $this->get_info($id);
        if($r && $r['parent_id'] > 0) {
            $position[] = $r;
            $this->fetch_parents($r['parent_id'], FALSE);
        }
        if($r['parent_id'] == 0){
            $position[] = $r;
        }
        return $position;
    }

    /**
     * 获取指定节点完整路径
     * @param int $id 节点ID
     * @param string $filed 字段
     * @return array
     */
    public function fetch_position($id, $filed = 'name') {
        $position = $this->fetch_parents($id);
        krsort($position);
        $result = array();
        foreach($position AS $pos) {
            $result[] = $pos[$filed];
        }
        return $result;
    }

    public function get_info($id) {
        // 读取缓存
        $info = $this->_get_info_check($id);
        if($info){
            return $info;
        }
        $fields = 'id,parent_id,name,m,c,a,param,level,sort,status,url,appid,split';
        $info = $this->model->field($fields)->find($id);
        // 更新缓存
        $this->_set_info_check($id, $info);
        return $info;
    }

    /**
     * 添加节点
     */
    public function update($params = array()) {
        $params['parent_id'] = (int) $params['parent_id'];
        if(!$params['name']) {
            $this->error = '节点不存在';
            return false;
        }
        if($params['parent_id'] > 0) {
            //缓存读取单条数据
            $parent_info = $this->get_info($params['parent_id']);
            if(!$parent_info){
                $this->error = lang('superior_area_no_exist','admin/language');
                return false;
            }
            $params['level'] = $parent_info['level'] + 1;
        }
        $result = $this->model->update($params);
        if($result === false) {
            $this->error = $this->model->getError();
            return false;
        }
        $parent_new_info = $this->fetch_by_id($params['parent_id']);
        // 更新缓存
        $this->_set_info_check($params['parent_id'], $parent_new_info);
        return true;
    }

    /**
     * 删除指定节点
     * @param type $ids
     * @return boolean
     */
    public function delete($ids = array()) {
        $node_ids = array();
        foreach($ids AS $id) {
            $node_ids = array_merge($node_ids, $this->fetch_all_childrens_by_id($id));
        }
        if($node_ids) {
            $_map = array();
            $_map['id'] =array("IN", $node_ids);
            $result = $this->model->where($_map)->save(['status' => -1]);
            if($result === false) {
                $this->error = $this->model->getError();
                return false;
            }
            // 删除缓存
            foreach ($node_ids as $_id){
                $this->_del_info_check($id);
            }
        }
        return true;
    }

    /**
     * 获取指定地区所有下级节点
     * @param int $id 地区ID
     */
    public function fetch_all_childrens_by_id($id = 0, $isself = 1) {
        static $ids = array();
        if($isself == 1) {
            $ids[] = $id;
        }
        $rs = $this->model->where(array('parent_id' => $id))->getField('id', TRUE);
        if($rs) {
            $ids = array_merge($ids, $rs);
            foreach($rs AS $id) {
                $this->fetch_all_childrens_by_id($id, 0);
            }
        }
        return $ids;
    }
}
