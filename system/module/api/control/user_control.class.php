<?php


use zuji\debug\Debug;
use zuji\debug\Location;
hd_core::load_class('api', 'api');
/**
 * 用户 API
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class user_control extends api_control {


    public function _initialize() {
        parent::_initialize();

        $this->service = $this->load->service('member2/member');

        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
	
        if( METHOD_NAME != 'login' && ( !$this->member || $this->member['id']<1 ) ){// 游客时，不允许操作
            api_resopnse('',  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录')->flush();
            exit;
        }
    }

    /**
     * 登录
     * 手机号+验证码
     */
    public function login() {

	    $user_info = $this->get_user();
	
        $result = [
            'auth_token'=> $this->auth_token,
            'user_info'=>[],
        ];

        if( $user_info ){
            $result['user_info'] = filter_array($user_info, [
                'id' => 'required',
                'username' => 'required',
                'mobile' => 'required',
                'certified' => 'required',
                'certified_platform' => 'required',
                'certified_platform_name' => 'required',
                'credit' => 'required',
                'credit_time' => 'required',
                'session_cache_time' => $_SERVER['SESSION_GC_MAXLIFETIME']
            ]);
           api_resopnse( $result, ApiStatus::CODE_0,'登录成功', '','登录成功' );
           return;
        }
        $params = $this->params;
        //验证手机号和短信验证码
        $params = filter_array($params,[
            'mobile' => 'required|is_mobile',
            'sm_code' => 'required',
        ]);
        if( !isset($params['mobile'])){
    	   api_resopnse( [], ApiStatus::CODE_20001,'手机号必选', ApiSubCode::Login_Error_Mobile,'' );
           return;
        }
        if( !isset($params['sm_code'])){
    	   api_resopnse( [], ApiStatus::CODE_20001,'验证码必选', ApiSubCode::Login_Error_Sm_code,'' );
           return;
        }
	
	// 从 session 中获取 验证码
	$data = filter_array($_SESSION, [
            '_login_mobile_' => 'required|is_mobile',
            '_login_expiry_' => 'required',
	]);
	
	// session中不存在手机和验证码，则返回错误
	if( count($data)!=2 ){
    	   api_resopnse( [$params,$data], ApiStatus::CODE_40004,'登录失败', ApiSubCode::Login_Error_Illegal,'参数错误' );
           return;
	}
	// 提交信息与session中的不匹配
	if( $params['mobile']!=$data['_login_mobile_'] ){
    	   api_resopnse( [$params,$data], ApiStatus::CODE_40004,'登录失败', ApiSubCode::Login_Error_Illegal,'登录异常' );
           return;
	}

    if($params['mobile']==config("Test_Mobile") && config("Test_Mobile_On")==true){
        $b = $params['sm_code']==config("Test_Mobile_Verify")?true:false;
    }
    else{
        // 接口校验验证码
        $sms = new \zuji\sms\HsbSms();
        $b = $sms->verify_sm_code($params['mobile'],'SMS_113450943',$params['sm_code']);
    }

	if( !$b ){
    	   api_resopnse( [$params,$data], ApiStatus::CODE_40004,'登录失败', ApiSubCode::Login_Error_Sm_code,'验证码错误' );
           return;
	}
	
	// 获取用户基本信息
	$where['mobile'] = $params['mobile'];
	$user_info = $this->service->get_info($where);
	// 未查询到手机管理的用户，则进行注册
	if( !$user_info ){
	    // 注册
	    $id = $this->service->register_info([
            'mobile' => $params['mobile'],
            'username' => $params['mobile'],
			'appid' => intval($this->appid)
	    ]);
	    if(!$id){
            api_resopnse([], ApiStatus::CODE_50001,'用户错误', ApiSubCode::User_Register_Error );
            return;
	    }
	    $user_info = array(
            'id' => $id,
            'username' => $params['mobile'],
            'mobile' => $params['mobile'],
            'certified' => '',
            'certified_platform' => '',
            'certified_platform_name' => '',
            'credit' => '',
            'credit_time' => '',
            'session_cache_time' => $_SERVER['SESSION_GC_MAXLIFETIME']
	    );
	}
	// 更新
	$this->service->update_login_info($user_info);
	
	$user_info = filter_array($user_info, [
		'id' => 'required',
		'username' => 'required',
		'mobile' => 'required',
		'certified' => 'required',
		'certified_platform' => 'required',
		'certified_platform_name' => 'required',
		'credit' => 'required',
		'credit_time' => 'required',
        'session_cache_time' => 'required',
	]);
	
	// 保存回话
	$this->set_user($user_info);
	
	// 检查 第三方登录绑定
	$third_user_info = session('__THIRD_USER_INFO__');
	
	if( $third_user_info ){
	    // 支付宝授权
	    if( $third_user_info['__third_platform__'] == 'ALIPAY' ){
		$member_alipay = $this->load->service('member2/member_alipay');
		// 先查询是否已经存在
		$_info = $member_alipay->get_info( $third_user_info['user_id'] );
		// 不存在时，才可以进行绑定
		if( !$_info ){
		    // 保存第三方授权信息，关联本地用户
		    $_id = $member_alipay->create($third_user_info,$user_info['id']);
		    if( !$_id ){
			$this->debug_error(2, '授权绑定失败', ''.get_error());
		    }
		}
	    }
	    // 情况当前会话绑定数据
	    session('__THIRD_USER_INFO__',null);
	}
	
	// 清除登录的业务数据
	unset($_SESSION['_login_mobile_']);
	unset($_SESSION['_login_sm_code_']);
	unset($_SESSION['_login_time_']);
	unset($_SESSION['_login_expiry_']);
    $user_info ['session_cache_time'] = $_SERVER['SESSION_GC_MAXLIFETIME'];
	$result['user_info'] = $user_info;
	//返回用户信息
	api_resopnse($result, ApiStatus::CODE_0 );
	return;
    }
    //会员退出
    public function logout(){
	// 销毁当前session
	session_destroy();
        api_resopnse($_SESSION, ApiStatus::CODE_0 ,'退出成功');
        return;
    }
	
	/**
	 * 记录无法下单原型
	 * @param type $user_id
	 * @param type $order_remark
	 */
	protected function order_remark($user_id,$order_remark){
		// 更新（无法下单原因）
		$member_table = \hd_load::getInstance()->table('member/member');
		$member_table->where(['id'=> $user_id])->save(['order_remark'=>$order_remark]);
	}
}
