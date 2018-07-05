<?php

hd_core::load_class('api', 'api');

/**
 * 第三方授权初始化
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class authorization_control extends api_control {

	public function _initialize() {
		parent::_initialize();

		$this->load->service("pay/alipay");

		$params = api_params();
	}

	/**
	 * 支付宝授权初始化，获取 url 地址
	 */
	public function initialize() {
		$params = filter_array($this->params, [
			'auth_channel' => 'required',
			'return_url' => 'required',
		]);

		if (!isset($params['auth_channel'])) {
			api_resopnse(['_' => ''], ApiStatus::CODE_20000, 'auth_channel 错误');
			return;
		}
		if (!isset($params['return_url'])) {
			api_resopnse(['_' => ''], ApiStatus::CODE_20000, 'return_url 错误');
			return;
		}

		// 该业务码暂时无用
		$data['zuji_no'] = zuji\Business::create_business_no();
		// 支付宝授权
		if ($params['auth_channel'] == 'ALIPAY') {
			$redirect_uri = urlencode(urldecode($params['return_url']));

			$appid = config('ALIPAY_APP_ID');
			$appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
			$Auth = new \alipay\Auth($appid);
			$data['url'] = $Auth->getUrl($redirect_uri);
		}

		api_resopnse($data, ApiStatus::CODE_0);
		return;
	}

	/**
	 * 支付宝授权查询
	 */
	public function query() {
		//file_put_contents('./data/anuthorization-query-log.txt', 'PARAMS:'.var_export($this->params,true),FILE_APPEND);
		$params = filter_array($this->params, [
			'auth_channel' => 'required|zuji\auth\Authorization::is_auth_channel',
			'auth_code' => 'required',
		]);

		if (!isset($params['auth_channel'])) {
			api_resopnse([], ApiStatus::CODE_20000, 'auth_channel 错误');
			return;
		}
		if (!isset($params['auth_code'])) {
			api_resopnse([], ApiStatus::CODE_20000, 'auth_code 错误');
			return;
		}

		// 当前用户
		$member = $this->get_user();
		//file_put_contents('./data/anuthorization-query-log.txt', '$member:'.var_export($member,true),FILE_APPEND);
		

		// 支付宝授权
        $appid = '';
		if ($params['auth_channel'] == 'ALIPAY') {
            $appid = config('ALIPAY_APP_ID');
            $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
        }elseif($params['auth_channel'] == 'ALIPAY-MINI'){
            $appid = config('ALIPAY_MINI_APP_ID');
        }

        $Auth = new \alipay\Auth($appid);
        $third_user_info = $Auth->getUserInfo($params['auth_code']);
        if (!$third_user_info) {
            $this->debug_error(\zuji\debug\Location::L_UserAuthorization, '支付宝用户授权失败', get_error());
            api_resopnse([], ApiStatus::CODE_50001, '授权失败');
            return;
        }
		//zuji\debug\Debug::error(zuji\debug\Location::L_UserAuthorization, '芝麻小程序--授权信息', $third_user_info);
        //file_put_contents('./data/anuthorization-query-log.txt', '$third_user_info:'.var_export($third_user_info,true),FILE_APPEND);

//	    $third_user_info = array (
//		'code' => '10000',
//		'msg' => 'Success',
//		'city' => '邯郸市',
//		'gender' => 'm',
//		'is_certified' => 'T',
//		'is_student_certified' => 'F',
//		'province' => '河北省',
//		'user_id' => '2088502596805705',
//		'user_status' => 'T',
//		'user_type' => '2',
//	      );
        unset($third_user_info['code']);
        unset($third_user_info['msg']);

        // 查询是否已经授权绑定了本地用户
        $member_alipay = $this->load->service('member2/member_alipay');
        // 更具支付宝用户ID（user_id）查询记录
        $_info = $member_alipay->get_info($third_user_info['user_id']);
        //file_put_contents('./data/anuthorization-query-log.txt', '$member_alipay_info:'.var_export($_info,true),FILE_APPEND);

        // 用户已经与本地用户绑定了，就设置登录态
        if ($_info && $_info['member_id']) {

            // 获取绑定用户信息
            $member_service = $this->load->service('member2/member');
            $member_info = $member_service->get_info(['id' => $_info['member_id']]);
            //file_put_contents('./data/anuthorization-query-log.txt', '$member_info:'.var_export($member_info,true),FILE_APPEND);

            // 更新用户登录记录
            $member_service->update_login_info($member_info);
            // 保存回话
            $member_info = filter_array($member_info, [
                'id' => 'required',
                'username' => 'required',
                'mobile' => 'required',
                'certified' => 'required',
                'certified_platform' => 'required',
                'certified_platform_name' => 'required',
                'credit' => 'required',
                'face' => 'required',
                'credit_time' => 'required',
            ]);
            $this->set_user($member_info);

            api_resopnse(['bind_user' => 'Y', 'auth_token' => $this->auth_token], ApiStatus::CODE_0);
            return;
        }
        /**/
        else{// 未绑定

            // 如果用户已经登录，则绑定到当前用户
            if( $member ){
                // 保存第三方授权信息，关联本地用户
                $_id = $member_alipay->create($third_user_info,$member['id']);
                if( !$_id ){
                    $this->debug_error(2, '支付宝授权登录绑定失败', ''.get_error());
                }
                api_resopnse(['bind_user' => 'Y', 'auth_token' => $this->auth_token], ApiStatus::CODE_0);
                return;
            }
        }
        // 没有绑定本地用户，授权信息放入 session 中
        $third_user_info['__third_platform__'] = 'ALIPAY'; // 第三方平台标识
        session('__THIRD_USER_INFO__', $third_user_info);

        // 返回为绑定标识
        api_resopnse(['bind_user' => 'N', 'auth_token' => $this->auth_token], ApiStatus::CODE_0);
        return;
	}

	public function report() {
		$auth_code = $_GET['auth_code'];
		var_dump($_GET);
	}

}
