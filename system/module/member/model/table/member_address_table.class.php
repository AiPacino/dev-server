<?php
class member_address_table extends table
{
	protected $_validate = array(
		/* array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间]) */
		array('mid', 'number', '{member/this_user_name_require}', table::EXISTS_VALIDATE, 'regex', table:: MODEL_BOTH),
		array('name', 'require', '{member/consignee_name_not_empty}', table::MUST_VALIDATE, 'regex', table:: MODEL_BOTH),
		array('mobile', 'mobile', '{member/mobile_format_exist}', table::MUST_VALIDATE, 'regex', table:: MODEL_BOTH),
		array('district_id', 'number', '{member/receiving_area_not_correct}', table::MUST_VALIDATE, 'regex', table:: MODEL_BOTH),
		array('address', 'require', '{member/receiving_address_not_empty}', table::MUST_VALIDATE, 'regex', table:: MODEL_BOTH),
	);


	public function fetch_all_by_mid($mid, $order = '') {
		return $this->where(array('mid' => $mid))->order($order)->select();
	}
    
    /**
     * 获取用户收货地址
     * $type Int 1返回默认地址 2返回所有
     * @return $address
     * @author limin
     */
    public function user_address($userid,$field,$type=1){
        if($type == 1){
            return $this->where(array('mid'=>$userid,'isdefault'=>1,'status'=>1))->field($field)->find();
        }
        else
        if($type == 2){
            return $this->where(array('mid'=>$userid,'status'=>1))->field($field)->select();
        }
        return false;
    }

    /**
     * 编辑地址
     * @param array $params
     * @return bool|mixed
     */
    public function edit_address( $params=[] ){
        $address_arr = explode(' ', $params['address']);
        $district = $this->load->table('admin/district');
        $district_info = $district->field('id')->where(['level' => 3, 'name' => ['like', $address_arr[2]]])->find();
        if($district_info){
            $params['district_id'] = $district_info['id'];
            $params['address'] = $address_arr[3];
        }
        $params['status'] = 1;
        $params['isdefault'] =1;

        $address_info = $this->user_address($params['mid'], 'id');
        if($address_info){
            $id = $address_info['id'];
            $result = $this->where(['id' => $id])->save($params);
        }else{
            $id = $this->add($params);
        }

        return $id;
    }
    
}