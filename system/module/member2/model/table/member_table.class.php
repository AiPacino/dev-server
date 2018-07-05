<?php

class member_table extends table {



    /**
     * 查询用户基本
     * @param array    $where	【可选】
     * [
     *      'id' => '',	//【可选】int；用户ID
     *      'mobile'=>'',	//【可选】string；商品名称
     * ]
     * @return mixed	false：查询失败或用户不存在；array：用户基本信息
     * [
     * 	    'id' => '',	    //【必须】int；用户ID
     * 	    'mobile' => '', //【必须】string；手机号
     * 	    'id' => '',	//【必须】int；用户ID
     * ]
     */
    public function get_info(array $where) {
	    $fields = 'id,username,password,encrypt,mobile,cert_no,realname,certified,face,risk,certified_platform,credit,credit_time,islock,block,login_ip,login_num,order_remark,withholding_no';
	    $rs = $this->where($where)->field($fields)->find();
	    return $rs ? $rs : false;
    }

    /**
     * 获取用户列表
     *
     * @param array  【可选】查询条件
     * [
     *      'user_id' => '',	//【可选】订单ID
     *      'mobile' => '',	    //【可选】string；手机号
     * ]
     * @return array查看 get_info 定义
     */
    public function get_list($where,$additional) {
        $fields = 'id AS user_id,username,password,encrypt,mobile,cert_no,realname,certified,face,risk,certified_platform,credit,islock,block,login_ip,login_num,order_remark,withholding_no';

        // 字段替换
        $where = replace_field( $where,[
            'user_id' => 'id',
            'mobile' => 'mobile',
        ]);
        if( isset($where['id']) ){
            $where['id'] = ['IN',$where['id']];
        }

        $list = $this->field($fields)
            ->page($additional['page'])
            ->limit($additional['size'])
            ->where($where)
            ->order($additional['orderby'])
            ->select();
        //var_dump( $this->getLastSql());exit;
        return $list;
    }
    /**
     * 查询一段时间的不同渠道的注册会员数
     *
     * @param array  【可选】查询条件
     * [
     *      'start_time' => '',	//int【可选】查询的开始时间
     *      'end_time' => '',	//int【可选】查询的结束时间
     * ]
     * @return array查看 get_info 定义
     */
    public function get_list_by_group_appid($where) {
		$fields = 'count(id) as N,appid';
		//拼接where条件
		$where_arr = array();
		if( isset($where['start_time']) && isset($where['end_time']) ){
			$where_arr['register_time'] = ['BETWEEN',[$where['start_time'],$where['end_time']]];
		}
		elseif( isset($where['start_time']) ) {
			$where_arr['register_time'] = array('GT',$where['start_time']);
		}
		elseif( isset($where['end_time']) ) {
			$where_arr['register_time'] = array('LT',$where['end_time']);
		}
        $list = $this->field($fields)
            ->where($where_arr)
			->group('appid')
            ->select();
//        echo( $this->getLastSql());exit;
		if( empty($list) ){
			return array();
		}
        return $list;
    }
	/**
	 * 获取新增用户数
	 * @param type $start_time
	 * @param type $end_time
	 * @return int 新增用户数
	 */
	public function get_newly_member($start_time,$end_time) {
		$fields = 'count(id) as N';
		$where_arr['register_time'] = ['BETWEEN',[$start_time,$end_time]];
        $count = $this->field($fields)
            ->where($where_arr)
            ->select();
//        echo( $this->getLastSql());exit;
		if( empty($count) ){
			return 0;
		}
        return $count[0]['N'];
	}
    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $list = $this->where($where)->count('id');
    }
    //注册会员
    public function register($data){
        return $this->add($data);
    }
    /**
     * 更新登录信息
     * @return int  符合查询条件的总数
     */
    public function update_table($data){
        $data['login_time'] = time();
        $result = $this->update($data);
        if($result)
            return true;
        else
            return false;
    }
}
