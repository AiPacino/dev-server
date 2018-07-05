<?php
/**
 *  基类控制器
 */
class base_control extends control
{
    protected $auth_token = '';
    protected $member = array();
    protected $params = [];
    protected $appid = 0;
    
    public function _initialize() {
	parent::_initialize();
	// 回话标识
//	if( !isset($_POST['auth_token']) ){
//	    $_POST['auth_token'] = $_GET['auth_token'];
//	}
	$this->params = $_REQUEST;
	$authToken = $this->params['auth_token'];
	if( $this->session_valid_id($authToken) ){
	    session_id( $authToken );
	}
	$b = session_start();
	if( $b ){
	    
	}
	$this->auth_token = session_id();
	$this->member = $this->get_user( );
	session('__last_time__',time());
	session('__auth_token__',$this->auth_token);
	
    }
    final protected function session_valid_id($session_id){
	return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
    }
    
    
    /**
     * 获取当前登录的用户信息
     * @return mixed	false：失败，当前用户未登录；array：用户基本信息
     * array(
     *	    'id' => '',		// 用户ID
     *	    'username' => '',	// 用户名
     *	    'mobile' => '',	// 用户手机号
     *	    'certified' => '',	// 认证状态
     *	    'certified_platform' => '',	// 认证平台标识
     *	    'certified_platform_name' => '',	// 认证平台名称
     *	    'credit' => '',	// 信用分
     *	    'credit_time' => '',// 信用分获取时间
     *	    'avatar' => '',	// 头像url
     *      'face' =>'',        //人脸识别
     * )
     */
    final protected function get_user(){
	$member = session('__USER_INFO__');
	if( $member ){
	    $member_service = $this->load->service('member2/member');
	    $member = $member_service->get_info(['id'=>$member['id']]);
	    $this->set_user($member);
	}
        return $member?$member:false;
    }
    
    /**
     * 设置当前登录的用户信息
     * @param array $member_info  用户基本信息
     * array(
     *	    'id' => '',		// 用户ID
     *	    'username' => '',	// 用户名
     *	    'mobile' => '',	// 用户手机号
     *	    'certified' => '',	// 认证状态
     *	    'certified_platform' => '',	// 认证平台
     *	    'credit' => '',	// 信用分
     *	    'credit_time' => '',// 信用分获取时间
     *	    'avatar' => '',	// 头像url
     *      'face' =>'',        //人脸识别
     * )
     * @return \member_service
     */
    final public function set_user($info){
        session('__USER_INFO__',$info);
    }
    
    /**
     * 记录订单操作日志
     * @param string $order_no	订单编号
     * @param string $action	操作名称
     * @param string $msg	操作说明
     */
    final protected function add_order_log( $operator_id,$operator_name,$order_no, $action, $msg ){
	$order_log = $this->load->service('order2/order_log');
	$r = $order_log->add([
	    'order_no' => $order_no,
	    'action' => $action,
	    'msg' => $msg,
	    'operator_id' => $operator_id,
	    'operator_name' => $operator_name,
	    'operator_type' => 2,
	]);
    }
    
    private function _get_debug_service(){
	if( $this->debug_service==null){
	    $this->debug_service = $this->load->service('debug/debug');
	}
	return $this->debug_service;
    }
    
    final protected function debug_error($location_id,$subject,$data){
	$this->_get_debug_service()->create([
	    'location_id' => $location_id,
	    'subject' => $subject,
	    'data' => $data,
	]);
    }
}