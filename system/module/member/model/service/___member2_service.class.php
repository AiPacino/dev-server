<?php

/**
 */
class member2_service extends service {

    protected $result;

    public function _initialize() {
        $this->vcode_table = $this->load->table('vcode');
        $this->member = $this->load->table('member/member');
        $this->group_model = $this->load->table('member/member_group');
    }
    
    /**
     * 设置当前登录的用户信息
     * @param array $userInfo  用户基本信息
     * array(
     *	    'id' => '',		// 用户ID
     *	    'username' => '',	// 用户名
     *	    'mobile' => '',	// 用户手机号
     * )
     * @return \member_service
     */
    public function set_current_user_info($userInfo){
        $userInfo['avatar'] = getavatar($userInfo['id']);
        session('__USER_INFO__',$userInfo);
        return $this;
    }
    /**
     * 获取当前登录的用户信息
     * @return mixed	false：失败，当前用户未登录；array：用户基本信息
     * array(
     *	    'id' => '',		// 用户ID
     *	    'username' => '',	// 用户名
     *	    'mobile' => '',	// 用户手机号
     *	    'avatar' => '',	// 头像url
     * )
     */
    public function get_current_user_info(){
	    return session('__USER_INFO__');;
    }

    
    /**
     * 根据手机号，获取用基本信息
     * @param string $username	    【必须】 用户名
     * @return mixed  false：失败；array：用户基本信息
     * array(
     *	    'id' => '',		// 用户ID
     *	    'username' => '',	// 用户名
     *	    'password' => '',	// 密文密码
     *	    'encrypt' => '',	// 加密盐值
     *	    'mobile' => '',	// 用户手机号
     *	    'login_time',	// 上次登录时间戳
     * )
     */
    public function fetch_by_mobile($username){
        $r = $this->model->field(array(
	    'id',	// 用户ID
	    'username',	// 用户名
	    'password',	// 密文密码
	    'encrypt',	// 加密盐值
	    'mobile',	// 用户手机号
	    'islock',	// 状态 0：正常；1禁用
	    'login_time',   // 上次登录时间
	))->fetch_by_username($username);
        if (!$r) {
            $this->error = '_select_not_exist_';
            return FALSE;
        }
        return $r;
    }
    
    /**
     * 初始化
     * @return [type] [description]
     */
    public function init($authkey) {
        $_member = array(
            'id' => 0,
            'username' => '游客',
            'group_id' => 0,
            'email' => '',
            'mobile' => '',
            'money' => 0,
            'integral' => 0,
            'exp' => 0
        );
        if ($authkey) {
            list($mid, $rand) = explode("\t", authcode($authkey));
           // var_dump(authcode($authkey));exit;
            $_member = $this->model->setid($mid)->address()->group()->output();
        }
        $_member['avatar'] = getavatar($_member['id']);
        runhook('member_init', $_member);
        return $_member;
    }
    /**
     * 用户登录
     * @return 
     */
    public function login($mobile, $sm_code) {
        
        //验证手机号和短信验证码        
        if(empty($mobile) && empty($sm_code)){
            return false;
        }
        
        //校验短信验证码
        if($sm_code){
            
        }
        
        $field = "id,mobile,username,certified,login_num";
        $user = $this->member->fetch_by_mobile($mobile,$field);
        
        if($user){
            //更新时间
            $data['login_time'] = time();
            $data['login_num']  = $user['login_num']+1;
            $data['login_ip']   = $_SERVER['REMOTE_ADDR'];
            $ret = $this->member->user_update($user['id'],$data);
            unset($user['login_num']);
            return $user;
        }
        return false;
    }
    /**
     * 手机号注册
     * @return $user
     */
    public function mobile_register($mobile){
        
        $field = "id";
        $user = $this->member->fetch_by_mobile($mobile,$field);
        
        if(!$user){
            
            $data['mobile']     = $mobile;
            $data['login_ip']   = $_SERVER['REMOTE_ADDR'];
            $data['login_num']  = 1;
            $data['login_time'] = time();
            //注册
            $ret = $this->member->register($data);
 
            if($ret){
                $arr['id']        = $ret;
                $arr['mobile']    = $mobile;
                $arr['username']  = $mobile;
                $arr['certified'] = 0;
                return $arr;
            }
            return false;
        }
        return false;
    }
}
