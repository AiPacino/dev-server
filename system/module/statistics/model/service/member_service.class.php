<?php
/**
 *      统计服务层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */

class member_service extends service {

	public function _initialize() {
		$this->member_table = $this->load->table('member2/member');
		$this->certification_service = $this->load->service('member2/certification_alipay');
	}

	public function _query($field,$sqlmap,$group){
		return $this->member_table->field($field)->where($sqlmap)->group($group)->select();
	}
	
	public function _count($sqlmap){
		return $this->member_table->where($sqlmap)->count();
	}

	public function build_data($data){
		$params = $data;
		$sqlmap = array();
		$xAxis = array();
		/* 时间周期 */
		if(isset($params['days']) && $params['days']>0){
			$params['etime'] = time();
			$params['stime'] = strtotime("-{$params['days']}day",$params['etime']);
			
		}
		if(isset($params['stime']{0}) && isset($params['etime']{0})){
			$params['etime'] = strtotime($params['etime']);
			$params['stime'] = strtotime($params['stime']);
		}
		
		$days=round(($params['etime']-$params['stime'])/86400);
		
		//两个时间戳之间的天数
		
		$sqlmap['register_time'] = array('between',array(
			strtotime(date('Y-m-d',$params['stime']).'00:00:00'),
			strtotime(date('Y-m-d',$params['etime']).'23:59:59')
		));
		$group = 'days';
		$subtext = $params['stime'].'-'.$params['etime'];
		for ($i=0; $i <= $days; $i++) { 
			$xAxis[$i] = date('Y-m-d',strtotime("+{$i}day",$params['stime']));
		}
		//注册数
		$field = "FROM_UNIXTIME(register_time,'%Y-%m-%d') days,count(id) as member_num";
		$_reg = $this->_query($field,$sqlmap,$group);
	
		foreach($_reg as $k =>$v){
			$_reg[$v['days']] = $_reg[$k];
		}
		//登录数
        $field = "FROM_UNIXTIME(login_time,'%Y-%m-%d') days,count(id) as login_num";
        $sqlmap2['login_time'] = array('between',array(
            strtotime(date('Y-m-d',$params['stime']).'00:00:00'),
            strtotime(date('Y-m-d',$params['etime']).'23:59:59')
        ));
        $_reg2 = $this->_query($field,$sqlmap2,$group);

        foreach($_reg2 as $k =>$v){
            $_reg2[$v['days']] = $_reg2[$k];
        }
        //认证数
        $field = "FROM_UNIXTIME(credit_time,'%Y-%m-%d') days,count(id) as credit_num";
        $sqlmap3['credit_time'] = array('between',array(
            strtotime(date('Y-m-d',$params['stime']).'00:00:00'),
            strtotime(date('Y-m-d',$params['etime']).'23:59:59')
        ));
        $sqlmap3['certified'] = 1;
        $_reg3 = $this->_query($field,$sqlmap3,$group);

        foreach($_reg3 as $k =>$v){
            $_reg3[$v['days']] = $_reg3[$k];
        }

		
		//组装数据
		foreach ($xAxis as $key => $value) {
			$_regval[] = isset($_reg[$value]['member_num'])?$_reg[$value]['member_num']:'0';
			$_loginval[] = isset($_reg2[$value]['login_num'])?$_reg2[$value]['login_num']:'0';
            $_creditval[] = isset($_reg3[$value]['credit_num'])?$_reg3[$value]['credit_num']:'0';
		}		
		
		$row['member'] ['xAxis']= $xAxis;
		$row['member'] ['reg'][]= $_regval;
        $row['member'] ['login'][]= $_loginval;
        $row['member'] ['credit'][]= $_creditval;
		return $row;
	}
	public function get_list_by_group_appid($where) {
		return $this->member_table->get_list_by_group_appid($where);
	}
	//拒绝用户信息导出
	public function get_list_by_reject_user($where,$option) {
		return $this->certification_service->get_list_by_reject_user($where,$option);
	}
	//拒绝用户信息导出
	public function get_list_by_count($where) {
		return $this->certification_service->get_list_by_count($where);
	}
	/**
	 * 获取新增用户数
	 * @param type $start_time
	 * @param type $end_time
	 * @return int 新增用户数
	 */
	public function get_newly_member($start_time,$end_time) {
		return $this->member_table->get_newly_member($start_time,$end_time);
	}
}
