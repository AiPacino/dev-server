<?php
/**
 * 代扣
 * 注意：暂只支持 支付宝代扣
 */
hd_core::load_class('api', 'api');

/**
 * 代扣签约控制器
 * @access public
 * @author limin <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class withholding_control extends api_control {

	public function _initialize() {
		parent::_initialize();
	}
	
	public function test(){
		
//		$withholding = new alipay\Withholding();
//		$b = $withholding->unsign( '20185008409316160776', '2088702003214762' );
//		var_dump( $b );exit;
//		
//		$withholding = new alipay\Withholding();
//		$status = $withholding->query('2088702003214762' );
//		var_dump($status);
	}
	
	/**
	 * 查询 代扣签约协议状态
	 */
	public function get( ){
//		$user_id = 5;
//		$username = '15311371612';
		if( !$this->member['id'] ){
            api_resopnse([],  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录');
            return;
		}
		$user_id = $this->member['id'];
		$username = $this->member['username'];
		
		
		$member_table = $this->load->table('member/member');
		// 查询用户ID和协议签署状态
		$user_where = ['id'=>$user_id];
		$user_info = $member_table->field(['id','username','withholding_no'])->where($user_where)->find();
		//\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣协议]数据', $user_info);
		
		if( !$user_info ){
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣协议]查询失败', $user_where);
            api_resopnse([],  ApiStatus::CODE_50001,'查询代扣协议失败', ApiSubCode::Params_Error, '服务器繁忙，请稍候重试...');
            return;
		}
		// 代扣协议码必须，不存在时，直接返回未签约
		if( !$user_info['withholding_no'] ){
			api_resopnse(['withholding'=>'N'],  ApiStatus::CODE_0);
			return;
		}
		
		//--本地查询用户签约状态----------------------------------
		// 更新用户签约协议状态
		$withholding_table = $this->load->table('payment/withholding_alipay');
		
		// 一个合作者ID下同一个支付宝用户只允许签约一次
		$where = [
			'user_id' => $user_id,
			'agreement_no' => $user_info['withholding_no'],
		];
		$withholding_info = $withholding_table->field(['id','user_id','partner_id','alipay_user_id','agreement_no','status'])->where( $where )->limit(1)->find();
		if( !$withholding_info ){// 查询失败
			$member_table->rollback();
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣协议]查询用户代扣协议失败', $where);
            api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
			return;
		}
		
		//--网络查询支付宝接口，获取代扣协议状态----------------------------------
		try {
			$withholding = new alipay\Withholding();
			$status = $withholding->query($withholding_info['alipay_user_id'] );
			if( $status=='Y' ){
				$withholding_status = 'Y';
			}else{
				$withholding_status = 'N';
			}
		} catch (\Exception $exc) {
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣协议]查询用户代扣协议出现异常', $exc->getMessage());
			$withholding_status = 'N';
		}

		api_resopnse(['withholding'=>$withholding_status],  ApiStatus::CODE_0);
		return;
	}
	
	/**
	 * 代扣签约初始化，获取签约页面链接地址或form表单
	 * 1）必须校验登录状态
	 * 2）禁止重复签约
	 */
	public function initialize() {
//		$user_id = 5;
//		$username = '15311371612';
		if( !$this->member['id'] ){
            api_resopnse([],  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录');
            return;
		}
		$user_id = $this->member['id'];
		$username = $this->member['username'];
		
		try {
			$params = $this->params;
			$params = filter_array($params, [
				'return_url' => 'required',
			]);
			set_default_value($params['return_url'], '');
			
			$withholding = new alipay\Withholding();
			$url = $withholding->buildRequestForm( $username, $params['return_url'] );
			
            api_resopnse( ['url'=>$url], ApiStatus::CODE_0, '');
            return;
		} catch (\Exception $exc) {
            api_resopnse( [], ApiStatus::CODE_20001, $exc->getMessage());
            return;
		}

	}
	
	/**
	 * 代扣签约接口（签约后同步调用接口）
	 * 暂时不支持同步返回签约成功处理的接口
	 */
//	public function sign() {
////		if( !$this->member['id'] ){
////            api_resopnse([],  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录');
////            return;
////		}
//	}
	
	/**
	 * 代扣解约接口
	 * 值能解约当前登录用户的协议
	 * 1）必须校验登录状态
	 * 2）返回结果只代表本次调用接口的状态，不代表解约处理结果（解约结果有异步通知处理）
	 */
	public function unsign() {
//		$user_id = 5;
//		$username = '15311371612';
		if( !$this->member['id'] ){
            api_resopnse([],  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录');
            return;
		}
		$user_id = $this->member['id'];
		$username = $this->member['username'];
		
		$member_table = $this->load->table('member/member');
		
        //开启事务
		$b = $member_table->startTrans();
		if( !$b ){
            api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            return;
		}
		
		// 用户 代扣协议
		$user_where = ['id'=>$user_id];
		$user_info = $member_table->field(['id','withholding_no'])->where($user_where)->find(['lock'=>true]);
		if( !$user_info ){
			$member_table->rollback();
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]lock查询用户信息失败', []);
            api_resopnse( [], ApiStatus::CODE_50000, '用户未签约该协议');
			return;
		}
		if( !$user_info['withholding_no'] ){
			$member_table->rollback();
			api_resopnse( [], ApiStatus::CODE_50000, '用户未签约该协议');
			return;
		}
		
		// 查看用户是否有未扣款的分期
		/* 如果有未扣款的分期信息，则不允许解约 */
		$instalment_server = $this->load->table('order2/order2_instalment');
		$n = $instalment_server->where([
			'agreement_no' => $user_info['withholding_no'],
			'status' => ['IN',[1,3]]
		])->count('id');
		if( is_null($n) ){
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]订单分期查询错误', $user_info);
            api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
			return;
		}
		if( $n>0 ){
            api_resopnse( [], ApiStatus::CODE_50000, '解约失败，有未完成分期');
			return;
		}
		
		// 更新用户签约协议状态
		$withholding_table = $this->load->table('payment/withholding_alipay');
		
		// 一个合作者ID下同一个支付宝用户只允许签约一次
		$where = [
			'user_id' => $user_id,
			'agreement_no' => $user_info['withholding_no'],
		];
		$withholding_info = $withholding_table->field(['id','user_id','partner_id','alipay_user_id','agreement_no','status'])->where( $where )->limit(1)->find(['lock'=>true]);
		if( !$withholding_info ){
			$member_table->rollback();
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]lock查询用户代扣协议失败', $where);
            api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
			return;
		}
		
		// 协议非已签约状态时，直接返回成功
		if( $withholding_info['status'] != 1 ){
			$member_table->rollback();
            api_resopnse( [], ApiStatus::CODE_0, '解约成功');
			return;
		}
		
		try {
			
			$withholding = new alipay\Withholding();
			$b = $withholding->unsign( $withholding_info['agreement_no'],$withholding_info['alipay_user_id'] );
			if( !$b ){
				$member_table->rollback();
				\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]调用支付宝解约接口失败', ['error'=> get_error()]);
				api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
				return;
			}
			
			// 更新数据
			// 1) 用户表协议码 清除
			$n = $member_table->where( $user_where )->limit(1)->save(['withholding_no'=>'']);
			if( $n===false ){
				$member_table->rollback();
				//\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]清除用户表协议码失败', $data);
				api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
				return;
			}
			
			// 2) 用户代扣协议 状态改为 解约(status=2)
			$withholding_table->where( ['id'=>$withholding_info['id']] )->limit(1)->save(['status'=>2]);
			if( $n===false ){
				$member_table->rollback();
				//\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣解约]更新代扣协议状态失败', $data);
				api_resopnse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
				return;
			}
			// 成功
			$member_table->commit();
			
            api_resopnse( [], ApiStatus::CODE_0, '');
            return;
		} catch (\Exception $exc) {
            api_resopnse( [], ApiStatus::CODE_20001, $exc->getMessage());
            return;
		}
		
	}


	/**
	 * 代扣签约异步通知接口
	 * 注意：该接口，不限制用户登录
	 * 1）签约
	 * 2）解约
	 */
	public function notify() {
        ob_start();
		$query_status = false;
		//--网络查询支付宝接口，获取代扣协议状态----------------------------------
		try {
			$withholding = new alipay\Withholding();
			$query_status = $withholding->query($_POST['alipay_user_id'] );
			if( $query_status===false ){
                ob_clean();
				echo '查询代扣签约状态失败';
				exit;
			}
		} catch (\Exception $exc) {
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '[代扣协议]查询用户代扣协议出现异常', $exc->getMessage());
			echo '[代扣协议]查询用户代扣协议出现异常';
			exit;
		}
		try {
		} catch (\Exception $exc) {
			echo $exc->getTraceAsString();exit;
		}
		
		// verifyNotify() 和 query() 目前只支持一个，不能同时使用
//		try {
//			$withholding = new alipay\Withholding();
//			$b = $withholding->verifyNotify( $_POST );
//			if( $b != true ){
//				ob_clean();
//				echo 'fail';
//				exit;
//			}
//		} catch (\Exception $exc) {
//			echo $exc->getTraceAsString();exit;
//		}
//		
		
		// 校验成功，进行业务处理（注意：异步通知记录保存，不在事务中）
		$data = filter_array($_POST, [
			'notify_id' => 'required',
			'notify_time' => 'required',
			'notify_type' => 'required',
			'sign_type' => 'required',
			'sign' => 'required',
			'external_user_id' => 'required',// 商户用户名
			'partner_id' => 'required',
			'alipay_user_id' => 'required',
			'agreement_no' => 'required',
			'product_code' => 'required',
			'scene' => 'required',
			'status' => 'required',
			'sign_time' => 'required',
			'valid_time' => 'required',
			'invalid_time' => 'required',
			'sign_modify_time' => 'required',
			// 解约时间
			'unsign_time' => 'required'
		]);
		
		set_default_value($data['sign_time'], '000-00-00 00:00:00');
		set_default_value($data['valid_time'], '000-00-00 00:00:00');
		set_default_value($data['invalid_time'], '000-00-00 00:00:00');
		set_default_value($data['sign_modify_time'], '000-00-00 00:00:00');
		set_default_value($data['unsign_time'], '000-00-00 00:00:00');
		
		// 用户表 协议号
		$withholding_no = '';
		// 操作名称
		$name = '';
		// 代扣协议状态
		$status = 0;
		if( $data['status'] == 'NORMAL' ){ // 签约成功
			$name = '代扣签约';
			$status = 1;
			$withholding_no = $data['agreement_no'];// 签约时，赋值
		}elseif( $data['status'] == 'UNSIGN' ){ // 解约成功
			$name = '代扣解约';
			$status = 2;
			$withholding_no = ''; // 解约时，清空
		}
		
		$notify_table = $this->load->table('payment/withholding_notify_alipay');
		
		// 保存异步通知
		$id = $notify_table->add( $data );
		if( $id<1 ){
			\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '['.$name.']异步通知保存失败', $data);
            ob_clean();
			echo '['.$name.']异步通知保存失败';
			exit;
		}
		
		if( ($status==1 && $query_status=='Y') || ($status==2 && $query_status=='N') ){
			//开启事务
			$b = $notify_table->startTrans();
			if( !$b ){
                ob_clean();
			    echo '事务开启失败';
				exit;
			}
			//?????????????????????????????????????????????????????????????????
			// 必须找到对应的租机用户ID（资讯支付宝的技术）
			// 已解决：签约参数 external_user_id 作为租机用户名
			//?????????????????????????????????????????????????????????????????
			$member_table = $this->load->table('member/member');

			// 用户名
			$username = $data['external_user_id'];

			// 查询用户ID和协议签署状态
			$user_where = ['username'=>$username];
			$user_info = $member_table->field(['id','username','withholding_no'])->where($user_where)->find(['lock'=>true]);
			if( !$user_info ){
				$notify_table->rollback();
				\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '['.$name.']lock查询用户信息失败', $data);
                ob_clean();
				echo '['.$name.']lock查询用户信息失败';
				exit;
			}
			// 用户ID
			$user_id = $user_info['id'];

			// 更新 用户表 协议号
			$b = $member_table->where( $user_where )->limit(1)->save(['withholding_no'=>$withholding_no]);
			if( $b===false ){
				$notify_table->rollback();
				\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '['.$name.']设置用户代扣协议码失败', $data);
                ob_clean();
				echo '['.$name.']设置用户代扣协议码失败';
				exit;
			}

			// 更新用户签约协议状态
			$withholding_table = $this->load->table('payment/withholding_alipay');
			// 代扣协议码唯一
			$where = [
				'agreement_no' => $data['agreement_no'],
			];
			$n = $withholding_table->where( $where )->limit(1)->count('id');
			if( $n==0 ){// 不存在，创建 协议
				$_data = [
					'user_id'		=> $user_id,
					'partner_id'	=> $data['partner_id'],
					'alipay_user_id' => $data['alipay_user_id'],
					'agreement_no'	=> $data['agreement_no'],
					'status'		=> $status,
					'sign_time'		=> $data['sign_time'],
					'valid_time'	=> $data['valid_time'],
					'invalid_time'	=> $data['invalid_time'],
					'unsign_time'	=> $data['unsign_time'],
				];
				$id = $withholding_table->add( $_data );
				if( $id<1 ){
					$notify_table->rollback();
					\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '['.$name.']创建代扣协议失败', $data);
                    ob_clean();
					echo '['.$name.']创建代扣协议失败';
					exit;
				}
			}else{
				$info = $withholding_table->field(['id','user_id','partner_id','alipay_user_id','status'])->where( $where )->limit(1)->find();
				if( !$info ){
					$notify_table->rollback();
					\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '['.$name.']查询代扣协议失败', $data);
                    ob_clean();
					echo '['.$name.']查询代扣协议失败';
					exit;
				}
				$_data = ['status'=>$status];
				if( $status==1 ){// 签约成功
					$_data = ['status'=>$status];
				}elseif( $status==2 ){// 解约成功
					$_data['unsign_time'] = $data['unsign_time'];// 更新解约时间
				}
				$n = $withholding_table->where(['id'=>$info['id']])->limit(1)->save( $_data );
				if( $n===false ){
					$notify_table->rollback();
					\zuji\debug\Debug::error(zuji\debug\Location::L_Withholding, '['.$name.']更新代扣协议状态失败', $data);
                    ob_clean();
					echo '['.$name.']更新代扣协议状态失败';
					exit;
				}
			}
			$notify_table->commit();
			ob_clean();
			echo 'success';
			exit;
		}
		ob_clean();
		echo 'fail';
		exit;
	}

	
	/**
	 * 代扣扣款异步通知处理
	 */
	public function createpay_notify(){
		$withholding = new \alipay\Withholding();
		$b = $withholding->verifyNotify($_POST);
		if( !$b ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[扣款异步通知]校验失败', [
				'$_POST' => $_POST,
			]);
			echo '[扣款异步通知]校验失败';
			exit;
		}
		
		$notify_info = filter_array($_POST, [
			'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
			'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
			'notify_id' => 'required',	    // 通知校验ID
			'notify_action_type' => 'required',
			'paytools_pay_amount' => 'required',// 支付金额信息说明
			'sign_type' => 'required',
			'sign' => 'required',
			'subject' => 'required',
			'trade_no' => 'required',		// 支付宝交易码
			'out_trade_no' => 'required',	// 原支付请求的商户订单号
			'trade_status' => 'required',   // 交易目前所处的状态
			'total_amount' => 'required',   // 本次交易支付的订单金额，单位为人民币（元）
			'price' => 'required',			// 价格
			'quantity' => 'required',
			'refund_fee' => 'required',	// 退款通知中，返回总退款金额，单位为元，支持两位小数
			'gmt_create' => 'required',	    // 该笔交易创建的时间。格式为yyyy-MM-dd HH:mm:ss
			'gmt_payment' => 'required',    // 该笔交易的买家付款时间。格式为yyyy-MM-dd HH:mm:ss
			'gmt_close' => 'required',		    // 该笔交易结束时间。格式为yyyy-MM-dd HH:mm:ss
			'buyer_email' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
			'buyer_id' => 'required',	    //【可选】付款方支付宝用户号
			'seller_email' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
			'seller_id' => 'required',	    //【可选】付款方支付宝用户号
		]);
		// 总金额
		set_default_value($notify_info['total_amount'], 0);
		// 退款金额
		set_default_value($notify_info['refund_fee'], 0);
		// 该笔交易结束时间
		set_default_value($notify_info['gmt_close'], '000-00-00 00:00:00');

		// * 注意： 
		// * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
		// * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
		$_no = $notify_info['out_trade_no'];
		$notify_info['out_trade_no'] = $notify_info['trade_no'];
		$notify_info['trade_no'] = $_no;
		
		//-+--------------------------------------------------------------------
		// | 更新交易表状态
		//-+--------------------------------------------------------------------
		
		//状态值转换
		$trade_status = 0;
		if( $notify_info['trade_status'] == 'WAIT_BUYER_PAY' ){
			$trade_status = 2;
		}elseif( $notify_info['trade_status'] == 'TRADE_PENDING' ){
			$trade_status = 3;
		}elseif( $notify_info['trade_status'] == 'TRADE_SUCCESS' ){
			$trade_status = 4;
		}elseif( $notify_info['trade_status'] == 'TRADE_CLOSED' ){
			$trade_status = 5;
		}elseif( $notify_info['trade_status'] == 'TRADE_FINISHED' ){
			$trade_status = 6;
		}
		
		// 记录异步通知
		$notify_table = $this->load->table('payment/withholding_trade_notify');
		$notify_info['create_time'] = time();
		$id = $notify_table->add( $notify_info );
		if( !$id ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[扣款异步通知]保存失败', $notify_info);
			echo '异步通知存储失败';
			exit;
		}
		
		$time = time();
		// 交易成功
		if( $notify_info['trade_status'] == 'TRADE_SUCCESS' ){
			// 更新 分期表
			$instalment_table = $this->load->table('order2/order2_instalment');

			//开启事务
			$b = $instalment_table->startTrans();
			if( !$b ){
				\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[扣款异步通知]事务开启失败', $notify_info);
				echo '事务开启失败';
				exit;
			}
			
			$n = $instalment_table->where([
				'trade_no' => $notify_info['trade_no'],
			])->limit(1)->save([
				'status' => 2,
				'out_trade_no' => $notify_info['out_trade_no'],
				'payment_time' => $time,
				'update_time' => $time,
			]);
			if( $n===false ){
				\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[扣款异步通知]更新分期扣款状态失败', $notify_info);
				echo '更新分期状态失败';
				exit;
			}
			
			// 提交事务
			$b = $instalment_table->commit();
			if( !$b ){
				\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[扣款异步通知]事务提交失败', $notify_info);
				echo '事务提交失败';
				exit;
			}
		}
		
		echo 'success';
		exit;
	}
}
