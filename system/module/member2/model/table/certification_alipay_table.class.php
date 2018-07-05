<?php
class certification_alipay_table extends table
{
    protected $fields = [
	'id',
	'member_id',
	'zm_score',
	'user_id',
	'cert_no',
	'name',
	'mobile',
	'house',
	'open_id',
	'zm_face',
	'zm_risk',
	'channel_id',
	'trade_no',
    'order_no',
    'create_time',
    ];


    //查询一条信息
    public function get_info($id) {
        $rs = $this->field($this->fields)
		->where(array('id'=>$id))
		->limit(1)
		->find();
        return $rs ? $rs : false;
    }
	
    //根据 支付宝订单编号，查询一条信息
    public function get_info_by_order_no($order_no) {
        $rs = $this->field($this->fields)
		->where(array('order_no'=>$order_no))
		->order('id DESC')
		->limit(1)
		->find();
        return $rs ? $rs : false;
    }
    
    //查询列表
    public function get_list($where,$additional) {
        $rs = $this->field($this->fields)
		->where(array('id'=>$id))
		->limit(1)
		->find();
        return $rs ? $rs : false;
    }
    
    //新增信息
    public function create($data) {
	//var_dump( $data );exit;
        $id = $this->add($data);
        return $id ? $id : false;
    }

	/**
	 * 查询记录数
	 * @return int  符合查询条件的总数
	 */
	public function get_count($where) {
		return $list = $this->where($where)->count('id');
	}
	/**
	 * 查询记录数拒绝用户数
	 * @return int  符合查询条件的总数
	 */
	public function get_count_csv($where) {
//		print_r($where);die;
		return $list = $this->where($where)->count('id');
	}
	/**
	 * 获取拒绝用户数量
	 * @param int $start_time
	 * @param int $end_time
	 * @return int 拒绝用户数
	 */
	public function get_refuse_member($start_time,$end_time){
		$fields = 'count(distinct order_no) as N';
		$where_arr['UNIX_TIMESTAMP(create_time)'] = ['BETWEEN',[$start_time,$end_time]];
		$where_arr['zm_score'] = ['LT',600];
		$where_arr['zm_face'] = 0;
		$where_arr['zm_risk'] = 0;
		$where_arr['member_id'] = ['GT',20];
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
	 * 获取下单用户数量
	 * @param int $start_time
	 * @param int $end_time
	 * @return int 下单用户数
	 */
	public function get_order_place($start_time,$end_time){
		$fields = 'count(distinct order_no) as N';
		$where_arr['UNIX_TIMESTAMP(create_time)'] = ['BETWEEN',[$start_time,$end_time]];
		$where_arr['member_id'] = ['GT',20];
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
		$sql = 'select sum(count_abc.a) as a,sum(count_abc.b) as b,sum(count_abc.c) as c, sum(count_abc.d) as d, sum(count_abc.e) as e, sum(count_abc.f) as f from 
			(select 
				distinct order_no,
				case when zm_score<600 
					then 1
					else 0
					end a,
				case when zm_score between 600 and 649 
					then 1
					else 0
					end b,
				case when zm_score between 650 and 699 
					then 1
					else 0
					end c,
				case when zm_score between 700 and 749
					then 1
					else 0
					end d,
				case when zm_score between 750 and 799 
					then 1
					else 0
					end e,
				case when zm_score>900 
					then 1
					else 0
					end f,
				zm_score
				from zuji_certification_alipay
				where member_id>20
				and UNIX_TIMESTAMP(create_time)>'.$start_time.'
				and UNIX_TIMESTAMP(create_time)<'.$end_time.'
			) count_abc;';
		return $this->query($sql);
	}

	/**
	 * 查询一段时间拒绝用户的信息
	 *
	 * @param array  【可选】查询条件
	 * [
	 *      'start_time' => '',	//int【可选】查询的开始时间
	 *      'end_time' => '',	//int【可选】查询的结束时间
	 * ]
	 * @return array查看 get_info 定义
	 */
	public function get_list_by_reject_user($where,$option) {
		$fields = 'member_id,zm_score,user_id,cert_no,name,mobile,zm_face,zm_risk,channel_id,create_time';
		//拼接where条件
		$where_arr = ['zm_score < 600 OR zm_face = 0 OR zm_risk = 0'];
//		$where_arr = ['zm_score < 600 OR zm_face = 0 OR zm_risk = 0 in( select max(create_time) from zuji_certification_alipay b where a.name=b.name group by name )'];
		$where_arr['member_id'] = ['GT','20'];
		if( isset($where['start_time']) && isset($where['end_time']) ){
			$where_arr['create_time'] = ['BETWEEN',[$where['start_time'],$where['end_time']]];
		}
		elseif( isset($where['start_time']) ) {
			$where_arr['create_time'] = array('GT',$where['start_time']);
		}
		elseif( isset($where['end_time']) ) {
			$where_arr['create_time'] = array('LT',$where['end_time']);
		}
		$list = $this->field($fields)
			->page($option['page'])
			->limit($option['size'])
			->where($where_arr)
			->order($option['orderby'])
			->select();
//		   echo( $this->getLastSql());exit;
		if( empty($list) ){
			return array();
		}
		return $list;
	}


    /**
     * 获取其他认证的用户id
     * @param $alipay_user_id
     * @param $member_id
     * @return mixed
     */
	public function get_cert_other($alipay_user_id, $member_id){
	    $where['user_id'] = $alipay_user_id;
	    $where['member_id'] = ['neq', $member_id];
        return $this->distinct('member_id')->where($where)->getField('member_id', true);
    }
}