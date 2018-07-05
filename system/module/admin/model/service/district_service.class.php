<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class district_service extends service
{
    private $hash_key = 'zuji-district-hash';
    
    public function _initialize() {
        $this->model = $this->load->table('admin/district');
    }
    
    /**
     * 获取一条订单的地址
     * @param int $id	    主键ID
     * @return mixed	false：查询失败或订单收地址不存在；array：收货地址信息
     * [
     *      'id' => '',		    //【必须】  主键ID
     *      'parent_id' => '',	//【必须】  父ID
     *      'name' => '',	    //【必须】  名称
     *      'zipcode' => '',	//【必须】  邮政编码
     *      'pinyin' => '',	    //【必须】  拼音
     *      'lng' => '',        //【必须】  坐标
     *      'lat' => '',	    //【必须】  坐标
     *      'level' => '',	    //【必须】  第几级
     *      'sort' => '',	    //【必须】
     *      'location' => '',	//【必须】  备注
     * ]
     */
    public function get_info($id) {
	// 读取缓存
	$info = $this->_get_info_check($id);
	if($info){
	    return $info;
	}
	$fields = 'id,parent_id,name,zipcode,pinyin,lng,lat,level,sort,location';
	$info = $this->model->field($fields)->find($id);
	// 更新缓存
	$this->_set_info_check($id, $info);
        return $info;
    }

    /**
     *  获取一条订单的地址名称
     * @param int $id	    主键ID
     * @return mixed	不存在返回空''；存在返回 array：收货地址信息
     * [
     *      'name' => '',	    //【必须】  名称
     * ]
     */
    public function get_name($id){
        $row = $this->get_info($id);
        return $row ? $row['name'] : '';
    }

    /**
     * 获取详细地址
     * @param array $address_info
     * [
     *'province_id'
     * 'city_id'
     * 'country_id'
     * ]
     * @return string
     */
    public function get_address_detail($address_info){
        $province= $this->get_name($address_info['province_id']);
        $city= $this->get_name($address_info['city_id']);
        $country= $this->get_name($address_info['country_id']);

        return $province.' '.$city.' '.$country.' '.$address_info['address'];
    }
    
	/**
	 * 添加地址
	 */
	public function update($params = array()) {
		$params['parent_id'] = (int) $params['parent_id'];
		if(!$params['name']) {
			$this->error = lang('region_not_exist','admin/language');
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
		if(!$params['pinyin'] && !isset($params['id'])) {
			$pinyin = pinyin($params['name']);
			$params['pinyin'] = implode($pinyin, '');
		}
        runhook('district_update',$params);
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
     * 获取所有地区信息
     * @param string $fields 字段名
     * @return array
     */
    public function fetch_all() {
        return $this->model->getField('id,parent_id,name',TRUE);
    }

    /**
     * 获取所有地区信息
     * @return array
     */
    public function fetch_all_by_tree($root = 0, $level = 0) {
    		$_map = array();
		if($level > 0) {
			$_map['level'] = array("LT", $level);
		}
        $result = $this->model->where($_map)->select();
        return list_to_tree($result, 'id', 'parent_id', '_child', $root);
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
     * 获取指定地区完整路径
     * @param int $id 地区ID
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

    /**
     * 返回指定地区下级地区
     * @param int $parent_id
     * @return array
     */
    public function get_children($parent_id = 0 ,$order = '`sort` ASC,`id` ASC') {
       $data = $this->model->where(array('parent_id' => $parent_id))->order($order)->select();
       $setting = model('admin/setting','service')->get();
       if($setting['regional_classification']=='1'){
            $list =array();
            foreach ($data as $value) {
            if($value['id']==100000){ $value['location']='省份,城市,区县';};
                $list[] = $value;
            }
            return $list;
        }else{
            return $data;
        }
    }
    /**
     * 返回中国大陆省市
     */
    public function get_children2($parent_id = 100000 ,$order = '`sort` ASC,`id` ASC') {
       $data = $this->model->where(array('parent_id' => $parent_id))->order($order)->field('id,name,parent_id')->select();
       $setting = model('admin/setting','service')->get();
       if($setting['regional_classification']=='1'){
            $list =array();
            foreach ($data as $value) {
            if($value['id']==100000){ $value['location']='省份,城市,区县';};
                $list[] = $value;
            }
            return $list;
        }else{
            return $data;
        }
    }

    public function get_lists($parent_id = 0,$order = '`sort` ASC,`id` ASC'){
        $data = $data = $this->model->where(array('parent_id' => $parent_id))->order($order)->select();
        $lists = array();
        foreach ($data AS $v) {
            $lists[] =array(
                'id'=>$v['id'],
                'sort'=>$v['sort'],
                'name'=>$v['name'],
                'parent_id' =>$v['parent_id'],
                'zipcode'=>$v['zipcode'],
                'pinyin'=>$v['pinyin'],
                'lng'=>$v['lng'],
                'lat'=>$v['lat'],
                'level'=>$v['level'],
                'location'=>$v['location'],
                );

        }
        return $lists;
    }



    /**
     * 删除指定地区
     * @param type $ids
     * @return boolean
     */
    public function delete($ids = array()) {
        $district_ids = array();
        foreach($ids AS $id) {
            $district_ids = array_merge($district_ids, $this->fetch_all_childrens_by_id($id));
        }
        runhook('district_delete',$district_ids);
        if($district_ids) {
            $_map = array();
            $_map['id'] =array("IN", $district_ids);
            $result = $this->model->where($_map)->delete();
            if($result === false) {
                $this->error = $this->model->getError();
                return false;
            }
            // 删除缓存
            foreach ($district_ids as $_id){
                $this->_del_info_check($id);
            }
        }
        return true;
    }

    /**
     * 获取指定地区所有下级地区
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
}