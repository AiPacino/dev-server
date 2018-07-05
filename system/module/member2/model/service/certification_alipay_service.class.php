<?php

/** 
 * 支付宝芝麻认证信息
 * 
 */
class certification_alipay_service extends service {

    public function _initialize() {
        $this->model = $this->load->table('member2/certification_alipay');
    }
    
    /**
     * 新增芝麻认证认证记录
     * @param array $data
     * [
     *	    'member_id',    => '',  //【必须】int
     *	    'zm_score',	    => '',  //【必须】int
     *	    'user_id',	    => '',  //【必须】int
     *	    'cert_no',	    => '',  //【必须】string
     *	    'name',	    => '',  //【必须】string
     *	    'mobile',	    => '',  //【必须】string
     *	    'house',	    => '',  //【必须】string
     *	    'open_id',	    => '',  //【必须】string
     *	    'zm_face',	    => '',  //【必须】int；是否人脸识别核身；0：不是；1：是
     *	    'channel_id',   => '',  //【必须】string；数据来源
     *	    'trade_no',   => '',    //【必须】string；租机交易号（如果使用的是深圳的接口查询认证结果，该字段可选）
     *      'order_no',=>'',        //【必须】string；订单编号
     *      
     * ]
     * @return mixed	false：失败；int：成功，主键ID
     */
    public function create( $data ){
	$data = filter_array($data, [
	    'member_id' => 'required|is_id',	// 租机用ID
	    'zm_score' => 'required',	// 芝麻分
	    'name' => 'required',	// 身份证号
	    'cert_no' => 'required',	// 身份证号
	    'user_id' => 'required',	// 支付宝用户ID
	    'open_id' => 'required',	// openID
	    'mobile' => 'required',	// 手机号
	    'house' => 'required',	// 住宅住址
	    'zm_face' => 'required',	// 是否人脸识别 1：是；0：否
	    'zm_risk' => 'required',	// 芝麻风控产品集联合结果，1：无风险；0：有风险
	    'channel_id' => 'required',	// 渠道来源 creditlife
	    'trade_no' => 'required',	// 租机交易号
	    'order_no'=>'required',//订单编号
	]);
	
	// 位兼容 深圳接口的返回值，没有该字段，所以必须指定默认值
	if( !isset($data['trade_no']) ){
	    $data['trade_no'] = '';
	}
	
	if( count($data)!=13 ){
	    set_error('芝麻认证结果保存失败，参数错误');
	    return false;
	}
	
	// 新增认证记录
	$id = $this->model->create($data);
	if( !$id ){
	    set_error('保存芝麻认证记录失败，数据保存失败');
	    return false;
	}
	$_data = [
	    'realname' => $data['name'],
	    'cert_no' => $data['cert_no'],
	    'certified' => '1',		    // 已认证状态
	    'certified_platform' => '1',    // 芝麻认证平台
	    'credit' => $data['zm_score'],  // 芝麻分
	    'face'=>$data['zm_face'],      //人脸识别
	    'risk'=>$data['zm_risk'],      //风控结果
	    'credit_time' => time(),
	];
	
	// 同步更新用户认证状态和积分
	$member_table = $this->load->table('member2/member');
	$b = $member_table->where(['id'=>$data['member_id']])->save($_data);
	
//	if( !$b ){
//	    set_error('芝麻认证失败，同步用户认证状态错误');
//	    return false;
//	}
	return $id;
    }
    
    /**
     * 用户最后一次认证的信息
     */
    public function get_last_info_by_user_id($user_id){
	$info = $this->model->where(['member_id'=>intval($user_id)])->order('create_time DESC')->find();
	return $info ? $info : false;
    }
	
	/**
	 * 根据 芝麻认证订单号 查询认证结果
	 * @param string $order_no
	 * @return mixed  false：查询失败；array：查询成功
	 */
	public function get_info_by_order_no( $order_no ){
		return $this->model->get_info_by_order_no( $order_no );
	}

    /**
     */
    public function get_list($where,$additional){
    }

	public function get_list_by_reject_user($where,$option){
		return $this->model->get_list_by_reject_user($where,$option);
	}

	public function get_list_by_count($where){
		return $this->model->get_count($where);
	}

	public function get_list_by_count_csv($where){
		return $this->model->get_count_csv($where);
	}

	/**
	 * 获取拒绝用户数量
	 * @param int $start_time
	 * @param int $end_time
	 * @return int 拒绝用户数
	 */
	public function get_refuse_member($start_time,$end_time){
		return $this->model->get_refuse_member($start_time,$end_time);
	}
	
	/**
	 * 获取下单用户数量
	 * @param int $start_time
	 * @param int $end_time
	 * @return int 下单用户数
	 */
	public function get_order_place($start_time,$end_time){
		return $this->model->get_order_place($start_time,$end_time);
	}
	/**
	 * 获取芝麻下单分数段
	 * @param int $start_time
	 * @param int $end_time
	 * @return array $result
	 * $result = [
	 *		'a'=>'',//[600,650)
	 *		'b'=>'',//[650,700)
	 *		'c'=>'',//[700,750)
	 *		'd'=>'',//[750,800)
	 *		'e'=>'',//>=800
	 * ]
	 */
	public function get_zm_group($start_time,$end_time){
		$result = $this->model->get_zm_group($start_time,$end_time);
		if( !empty($result) ) {
			$result = $result[0];
		}
		return [
			'a' => isset($result['a']) ? $result['a'] : 0,
			'b' => isset($result['b']) ? $result['b'] : 0,
			'c' => isset($result['c']) ? $result['c'] : 0,
			'd' => isset($result['d']) ? $result['d'] : 0,
			'e' => isset($result['e']) ? $result['e'] : 0,
			'f' => isset($result['f']) ? $result['f'] : 0,
		];
	}

    /**
     * 获取其他的认证用户
     * @param $alipay_user_id
     * @param $member_id
     * @return mixed
     */
	public function get_cert_other($alipay_user_id, $member_id){
        return $this->model->get_cert_other($alipay_user_id, $member_id);
    }
}
