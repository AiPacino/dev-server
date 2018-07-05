<?php
/**
 * 资金授权 服务层
 */
use zuji\Config;
use zuji\payment\FundAuth;
use zuji\payment\Instalment;

class fund_auth_service extends service {

    public function _initialize() {
    	$this->order_service   = $this->load->service('order2/order');
		$this->fund_auth_table = $this->load->table('payment/payment_fund_auth');
		$this->fund_auth_notify_table = $this->load->table('payment/payment_fund_auth_notify');
        $this->fund_auth_record_table = $this->load->table('payment/payment_fund_auth_record');
        $this->instalment_table = $this->load->table('order2/order2_instalment');
		$this->member_table = $this->load->table('member/member');
    }

    /**
     * 创建 资金授权 记录
     * @param array $data
     * [
     *	    'auth_channel' => '',   //【必须】string；渠道类型
     *	    'orderid' => '',	    //【必须】int；	订单ID
     *	    'order_no' => '',	    //【必须】string；	订单编号
     *	    'amount' => '',	    //【必须】int；预授权金额；单位：分
     * ]
     * @return mixed	false：失败；int：成功，返回主键ID
     */
    public function create_auth($data){
		$data = filter_array($data, [
			'auth_channel' => 'required',
			'order_id' => 'required',
			'order_no' => 'required',
			'fundauth_no' => 'required',
			'request_no' => 'required',
			'amount' => 'required',
		]);
		if( count($data)!=6 ){
			set_error('创建资金预授权信息，参数错误');
			return false;
		}
		$time = time();
		$data['create_time'] = $time;
		$data['auth_status'] = FundAuth::CREATED;	// 已创建
		//
		// 开启事务
		$this->fund_auth_table->startTrans();
		// 保存 授权记录
		$auth_id = $this->fund_auth_table->create( $data );
		if( !$auth_id ){
			set_error('创建资金预授权信息，保存失败');
			$this->fund_auth_table->rollback();
			return false;
		}
		$data['auth_id'] = $auth_id;

		// 成功，提交事务
		$this->fund_auth_table->commit();
		return $data;
	}

    /**
     * 保存资金解冻相关通知
     * @param type $data
     */
    public function unfreeze_to_pay_notify( $data ){
        $data = filter_array($data, [
            'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
            'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
            'notify_id' => 'required',	    // 通知校验ID
            'sign_type' => 'required',
            'sign' => 'required',
            'notify_action_type' => 'required',

            'out_trade_no' => 'required',   // 支付宝交易流水号
            'trade_no' => 'required',	    // 租机交易号
            'trade_status' => 'required',
            'subject' => 'required',
            'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
            'gmt_payment' => 'required',
            'gmt_close' => 'required',

            'seller_email' => 'required',
            'seller_id' => 'required',
            'buyer_id' => 'required',
            'buyer_email' => 'required',
            'total_amount'=>'required',
            'trade_no'=>'required',

            'total_amount' => 'required',
            'price' => 'required',
            'quantity' => 'required',
        ]);
        //
        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号

		$time = time();
		
        //更新 授权交易表状态
        $record_info = $this->fund_auth_record_table->where(['trade_no'=>$data['trade_no']])->find(['lock'=>true]);
        if(!$record_info){
            set_error("[预授权异步通知]查询预授权交易记录失败");
            return false;
        }
        $b =$this->fund_auth_record_table->where(['trade_no'=>$data['trade_no']])->save([
			'status'=>1,
			'update_time'=>$time,
			'out_trade_no'=>$data['out_trade_no'],// 支付宝交易号
		]);
        if(!$b){
            set_error("[预授权异步通知]更新预授权交易记录状态失败");
            return false;
        }
        $b = $this->fund_auth_table->where(['auth_id'=>$record_info['auth_id'],])->save([
			'pay_amount' => $data['total_amount'],
			'update_time' => $time,
		]);

        if( !$b ){
            set_error('更新资金预授权金额失败');
            return false;
        }

        $b = $this->instalment_table->where(['id'=>$record_info['instalment_id']])->save(['status'=>Instalment::SUCCESS,'unfreeze_status'=>1]);
        if( !$b ){
            set_error('更新分期表状态失败');
            return false;
        }
        return true;
    }
    /**
     * 保存押金资金解冻相关通知
     * @param type $data
     */
    public function yajin_unfreeze_to_pay_notify( $data ){
        $data = filter_array($data, [
            'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
            'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
            'notify_id' => 'required',	    // 通知校验ID
            'sign_type' => 'required',
            'sign' => 'required',
            'notify_action_type' => 'required',

            'out_trade_no' => 'required',   // 支付宝交易流水号
            'trade_no' => 'required',	    // 租机交易号
            'trade_status' => 'required',
            'subject' => 'required',
            'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
            'gmt_payment' => 'required',
            'gmt_close' => 'required',

            'seller_email' => 'required',
            'seller_id' => 'required',
            'buyer_id' => 'required',
            'buyer_email' => 'required',
            'total_amount'=>'required',
            'trade_no'=>'required',

            'total_amount' => 'required',
            'price' => 'required',
            'quantity' => 'required',
        ]);
        //
        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号

        $time = time();

        //更新 授权交易表状态
        $record_info = $this->fund_auth_record_table->where(['trade_no'=>$data['trade_no']])->find(['lock'=>true]);
        if(!$record_info){
            set_error("[预授权异步通知]查询预授权交易记录失败");
            return false;
        }
        $b =$this->fund_auth_record_table->where(['trade_no'=>$data['trade_no']])->save([
            'status'=>1,
            'update_time'=>$time,
            'out_trade_no'=>$data['out_trade_no'],// 支付宝交易号
        ]);
        if(!$b){
            set_error("[预授权异步通知]更新预授权交易记录状态失败");
            return false;
        }
		// 查询授权信息
        $auth_info = $this->fund_auth_table->where(['auth_id'=>$record_info['auth_id'],])->find(['lock'=>true]);
		if( $auth_info === false ){
            set_error("[预授权异步通知]预授权查询失败");
            return false;
		}
		// 获取 最新状态，最新数据 
        $FundAuth = new \alipay\fund\FundAuth();
		$auth_info_alipay = $FundAuth->query_auth(['auth_no'=>$auth_info['auth_no']]);
		if( $auth_info_alipay === false ){
			return false;
		}
		
		// 状态
        $auth_status =$auth_info['auth_status'];
		if( $auth_info_alipay['order_status'] == 'INIT' ){
			$auth_status = FundAuth::INIT;
		}elseif( $auth_info_alipay['order_status'] == 'AUTHORIZED' ){
			$auth_status = FundAuth::AUTHORIZED;
		}elseif( $auth_info_alipay['order_status'] == 'FINISH' ){
			$auth_status = FundAuth::FINISH;
		}elseif( $auth_info_alipay['order_status'] == 'CLOSED' ){
			$auth_status = FundAuth::CLOSED;
		}
		
        $b = $this->fund_auth_table->where(['auth_id'=>$record_info['auth_id'],])->save([
            'amount' => $auth_info_alipay['total_freeze_amount'],
            'unfreeze_amount' => $auth_info_alipay['total_unfreeze_amount'],
            'pay_amount' => $auth_info_alipay['total_pay_amount'],
            'auth_status' =>$auth_status,
            'update_time' => $time,
        ]);

        if( !$b ){
            set_error('更新资金预授权金额失败');
            return false;
        }
//
//        $b = $this->instalment_table->where(['id'=>$record_info['instalment_id']])->save(['status'=>Instalment::SUCCESS,'unfreeze_status'=>1]);
//        if( !$b ){
//            set_error('更新分期表状态失败');
//            return false;
//        }
        return true;
    }
    /**
     * 保存资金解冻相关通知
     * @param type $data
     */
    public function to_unfreeze_notify( $data ){
        $data = filter_array($data, [
            'request_no' => 'required',
            'request_status' => 'required',
            'fundauth_no' => 'required',	// 商户资金授权号
            'notify_time' => 'required',
            'notify_id' => 'required',
            'notify_type' => 'required',
            'total_pay_amount' => 'required',
            'auth_no' => 'required',
            'total_freeze_amount' => 'required',
            'total_unfreeze_amount' => 'required',
            'reset_amount' => 'required',
            'order_status' => 'required',
            'operation_type' => 'required',
            'operation_id' => 'required',
            'gmt_create' => 'required',
            'gmt_trans' => 'required',
            'amount' => 'required',
            'unfreeze_amount' => 'required',
            'pay_amount' => 'required',
            'payer_logon_id' => 'required',
            'payer_user_id' => 'required',
            'payee_logon_id' => 'required',
            'payee_user_id' => 'required',
            'sign_type' => 'required',
            'sign' => 'required',
            'create_time' => 'required',
        ]);

        //更新 授权交易表状态

        $record_info = $this->fund_auth_record_table->where(['trade_no'=>$data['request_no']])->find(['lock'=>true]);
        if(!$record_info){
            set_error("[预授权异步通知]查询预授权交易记录失败");
            return false;
        }
        $b =$this->fund_auth_record_table->where(['trade_no'=>$data['request_no']])->save([
			'status'=>1,
            'update_time' => $data['create_time'],
		]);
        if(!$b){
            set_error("[预授权异步通知]更新预授权交易记录状态失败");
            return false;
        }
        // 获取授权记录 fundauth_no
        $fundauth_info = $this->get_info_by_fundauth_no($data['fundauth_no']);
        if( !$fundauth_info ){
            set_error('[预授权异步通知]查询预授权记录失败');
            return false;
        }

		//更新 授权表状态
		$auth_status = 0;
		if( $data['order_status'] == 'INIT' ){
			$auth_status = FundAuth::INIT;
		}elseif( $data['order_status'] == 'AUTHORIZED' ){
			$auth_status = FundAuth::AUTHORIZED;
		}elseif( $data['order_status'] == 'FINISH' ){
			$auth_status = FundAuth::FINISH;
		}elseif( $data['order_status'] == 'CLOSED' ){
			$auth_status = FundAuth::CLOSED;
		}
		
        // 更新授权信息
        $b = $this->fund_auth_table->where(['fundauth_no'=>$data['fundauth_no']])->save([
			'auth_status' => $auth_status,
            'amount' => $data['total_freeze_amount'],
            'unfreeze_amount' => $data['total_unfreeze_amount'],
            'pay_amount' => $data['total_pay_amount'],
            'update_time' => $data['create_time'],
        ]);
        if( !$b ){
            set_error('更新资金预授权金额失败');
            return false;
        }

        //更新分期表 解冻状态  -- 押金没有分期
        $b = $this->instalment_table->where(['id'=>$record_info['instalment_id']])->save(['unfreeze_status'=>1]);
        if( !$b ){
            set_error('更新分期表解冻状态失败');
            return false;
        }
        return true;
    }
    /**
     * 保存资金解冻相关通知
     * @param type $data
     */
    public function unfreeze_notify( $data ){
        $data = filter_array($data, [
            'request_no' => 'required',
            'request_status' => 'required',
            'fundauth_no' => 'required',	// 商户资金授权号
            'notify_time' => 'required',
            'notify_id' => 'required',
            'notify_type' => 'required',
            'total_pay_amount' => 'required',
            'auth_no' => 'required',
            'total_freeze_amount' => 'required',
            'total_unfreeze_amount' => 'required',
            'reset_amount' => 'required',
            'order_status' => 'required',
            'operation_type' => 'required',
            'operation_id' => 'required',
            'gmt_create' => 'required',
            'gmt_trans' => 'required',
            'amount' => 'required',
            'unfreeze_amount' => 'required',
            'pay_amount' => 'required',
            'payer_logon_id' => 'required',
            'payer_user_id' => 'required',
            'payee_logon_id' => 'required',
            'payee_user_id' => 'required',
            'sign_type' => 'required',
            'sign' => 'required',
            'create_time' => 'required',
        ]);

        //更新 授权交易表状态

        $record_info = $this->fund_auth_record_table->where(['trade_no'=>$data['request_no']])->find(['lock'=>true]);
        if(!$record_info){
            set_error("[预授权异步通知]查询预授权交易记录失败");
            return false;
        }
        $b =$this->fund_auth_record_table->where(['trade_no'=>$data['request_no']])->save([
            'status'=>1,
            'update_time' => $data['create_time'],
        ]);
        if(!$b){
            set_error("[预授权异步通知]更新预授权交易记录状态失败");
            return false;
        }
        // 获取授权记录 fundauth_no
        $fundauth_info = $this->get_info_by_fundauth_no($data['fundauth_no']);
        if( !$fundauth_info ){
            set_error('[预授权异步通知]查询预授权记录失败');
            return false;
        }

        //更新 授权表状态
        $auth_status = 0;
        if( $data['order_status'] == 'INIT' ){
            $auth_status = FundAuth::INIT;
        }elseif( $data['order_status'] == 'AUTHORIZED' ){
            $auth_status = FundAuth::AUTHORIZED;
        }elseif( $data['order_status'] == 'FINISH' ){
            $auth_status = FundAuth::FINISH;
        }elseif( $data['order_status'] == 'CLOSED' ){
            $auth_status = FundAuth::CLOSED;
        }

        // 更新授权信息
        $b = $this->fund_auth_table->where(['fundauth_no'=>$data['fundauth_no']])->save([
            'auth_status' => $auth_status,
            'amount' => $data['total_freeze_amount'],
            'unfreeze_amount' => $data['total_unfreeze_amount'],
            'pay_amount' => $data['total_pay_amount'],
            'update_time' => $data['create_time'],
        ]);
        if( !$b ){
            set_error('更新资金预授权金额失败');
            return false;
        }

        //更新分期表 解冻状态  -- 押金没有分期
        $b = $this->instalment_table->where(['id'=>$record_info['instalment_id']])->save(['unfreeze_status'=>1]);
        if( !$b ){
            set_error('更新分期表解冻状态失败');
            return false;
        }
        return true;
    }
    /**
     * 保存资金解冻相关通知
     * @param type $data
     */
    public function yajin_unfreeze_notify( $data ){
        $data = filter_array($data, [
            'request_no' => 'required',
            'request_status' => 'required',
            'fundauth_no' => 'required',	// 商户资金授权号
            'notify_time' => 'required',
            'notify_id' => 'required',
            'notify_type' => 'required',
            'total_pay_amount' => 'required',
            'auth_no' => 'required',
            'total_freeze_amount' => 'required',
            'total_unfreeze_amount' => 'required',
            'reset_amount' => 'required',
            'order_status' => 'required',
            'operation_type' => 'required',
            'operation_id' => 'required',
            'gmt_create' => 'required',
            'gmt_trans' => 'required',
            'amount' => 'required',
            'unfreeze_amount' => 'required',
            'pay_amount' => 'required',
            'payer_logon_id' => 'required',
            'payer_user_id' => 'required',
            'payee_logon_id' => 'required',
            'payee_user_id' => 'required',
            'sign_type' => 'required',
            'sign' => 'required',
            'create_time' => 'required',
        ]);

        //更新 授权交易表状态

        $record_info = $this->fund_auth_record_table->where(['trade_no'=>$data['request_no']])->find(['lock'=>true]);
        if(!$record_info){
            set_error("[预授权异步通知]查询预授权交易记录失败");
            return false;
        }
        $b =$this->fund_auth_record_table->where(['trade_no'=>$data['request_no']])->save([
            'status'=>1,
            'update_time' => $data['create_time'],
        ]);
        if(!$b){
            set_error("[预授权异步通知]更新预授权交易记录状态失败");
            return false;
        }
        // 获取授权记录 fundauth_no
        $fundauth_info = $this->get_info_by_fundauth_no($data['fundauth_no']);
        if( !$fundauth_info ){
            set_error('[预授权异步通知]查询预授权记录失败');
            return false;
        }

        //更新 授权表状态
        $auth_status = 0;
        if( $data['order_status'] == 'INIT' ){
            $auth_status = FundAuth::INIT;
        }elseif( $data['order_status'] == 'AUTHORIZED' ){
            $auth_status = FundAuth::AUTHORIZED;
        }elseif( $data['order_status'] == 'FINISH' ){
            $auth_status = FundAuth::FINISH;
        }elseif( $data['order_status'] == 'CLOSED' ){
            $auth_status = FundAuth::CLOSED;
        }

        // 更新授权信息
        $b = $this->fund_auth_table->where(['fundauth_no'=>$data['fundauth_no']])->save([
            'auth_status' => $auth_status,
            'amount' => $data['total_freeze_amount'],
            'unfreeze_amount' => $data['total_unfreeze_amount'],
            'pay_amount' => $data['total_pay_amount'],
            'update_time' => $data['create_time'],
        ]);
        if( !$b ){
            set_error('更新资金预授权金额失败');
            return false;
        }
        //查看解冻金额是否等于授权金额 如果相等 授权状态改变
        if($data['total_unfreeze_amount'] == $data['total_freeze_amount']){

            $b = $this->fund_auth_table->where(['auth_id'=>$record_info['auth_id'],])->save([
                'update_time' => $data['create_time'],
                'auth_status' =>FundAuth::FINISH,
            ]);

            if( !$b ){
                set_error('更新资金预授权金额失败');
                return false;
            }
        }

        //更新分期表 解冻状态  -- 押金没有分期
//        $b = $this->instalment_table->where(['id'=>$record_info['instalment_id']])->save(['unfreeze_status'=>1]);
//        if( !$b ){
//            set_error('更新分期表解冻状态失败');
//            return false;
//        }
        return true;
    }

	/**
	 * 保存资金冻结相关通知
	 * @param type $data
	 */
    public function auth_notify( $data ){
		$data = filter_array($data, [
            'request_no' => 'required',
            'request_status' => 'required',
            'fundauth_no' => 'required',	// 商户资金授权号
            'notify_time' => 'required',
            'notify_id' => 'required',
            'notify_type' => 'required',
            'total_pay_amount' => 'required',
            'auth_no' => 'required',
            'total_freeze_amount' => 'required',
            'total_unfreeze_amount' => 'required',
            'reset_amount' => 'required',
            'order_status' => 'required',
            'operation_type' => 'required',
            'operation_id' => 'required',
            'gmt_create' => 'required',
            'gmt_trans' => 'required',
            'amount' => 'required',
			'unfreeze_amount' => 'required',
			'pay_amount' => 'required',
            'payer_logon_id' => 'required',
            'payer_user_id' => 'required',
            'payee_logon_id' => 'required',
            'payee_user_id' => 'required',
            'sign_type' => 'required',
            'sign' => 'required',
            'create_time' => 'required',
		]);
		
		//更新 授权表状态
		$auth_status = 0;
		if( $data['order_status'] == 'INIT' ){
			$auth_status = FundAuth::INIT;
		}elseif( $data['order_status'] == 'AUTHORIZED' ){
			$auth_status = FundAuth::AUTHORIZED;
		}elseif( $data['order_status'] == 'FINISH' ){
			$auth_status = FundAuth::FINISH;
		}elseif( $data['order_status'] == 'CLOSED' ){
			$auth_status = FundAuth::CLOSED;
		}
		// 获取授权记录 fundauth_no
		$fundauth_info = $this->get_info_by_fundauth_no($data['fundauth_no']);
		if( !$fundauth_info ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '[预授权异步通知]查询预授权记录失败', $data);
			set_error('[预授权异步通知]查询预授权记录失败');
			return false;
		}
		
		// 更新授权信息
		$b = $this->fund_auth_table->where(['fundauth_no'=>$data['fundauth_no']])->save([
			'auth_status' => $auth_status,
			'auth_no' => $data['auth_no'],
            'amount' => $data['total_freeze_amount'],
            'unfreeze_amount' => $data['total_unfreeze_amount'],
			'payer_logon_id' => $data['payer_logon_id'], // 付款方支付宝账号
			'payer_user_id' => $data['payer_user_id'],	// 付款方支付宝用户ID
			'payee_logon_id' => $data['payee_logon_id'], // 收款方支付宝账号
			'payee_user_id' => $data['payee_user_id'],	// 收款方支付宝用户ID
			'update_time' => $data['create_time'],
		]);
		if( !$b ){
			set_error('更新资金预授权状态失败');
			return false;
		}

		// 只有资金授权成功状态时，更新订单状态
		if( $auth_status == FundAuth::AUTHORIZED  && $data['total_unfreeze_amount'] =="0.00"){
			// 订单信息
			$order_no = $fundauth_info['order_no'];
			$additional =['lock' =>true];
			$order_info = $this->order_service->get_order_info(['order_no'=>$order_no],$additional);

			if( !$order_info ){
				\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '[预授权异步通知]查询关联订单失败', [
					'$data' => $data,
					'$fundauth_info' => $fundauth_info
				]);
				set_error('查询关联订单失败');
				return false;
			}
			//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			// | 当前下单用户是否签署代扣协议【未签署直接解冻资金】																			    +++
			//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			$withholding_no = $this->member_table->fetch_by_id($order_info['user_id'],'withholding_no');	
			//未签署代扣协议（获取解除了代扣协议）
			if( !$withholding_no ) {
				//解冻资金
				if (!$fundauth_info || !$data['auth_no'] || !$fundauth_info['request_no'] || !$fundauth_info['auth_channel']) {
					set_error('解冻:数据错误');
					return false;
				}
				if (!\zuji\payment\FundAuth::verifyPlatform($fundauth_info['auth_channel'])) {
					set_error('解冻:授权渠道值错误');
					return false;
				}
				$unfreeze_data_arr = [
					'auth_no' => $data['auth_no'],
					'out_request_no' => $fundauth_info['request_no'],
					'amount' => $data['total_freeze_amount'],
					'remark' => '解冻',
					'notify_url' => config('ALIPAY_FundAuth_Notify_Url'),
				];
				$auth = new alipay\fund\FundAuth();
				$result = $auth->unfreeze($unfreeze_data_arr);
				if (!$result) {	
					set_error('解冻:失败');
					return false;
				}
				//插入授权记录表zuji_payment_fund_auth_record
				$trade_no = \zuji\Business::create_business_no();
				$unfreeze_record_data = [
					'order_id'=>$order_info['order_id'],
					'amount'=>intval($data['total_freeze_amount']), //元
					'trade_no'=>$trade_no,
				];
				$unfreeze_record_data = filter_array($unfreeze_record_data, [
					'order_id' => 'required|is_id',
					'amount' =>'required|is_int',
					'trade_no'=>'required',
				]);
				if( count($unfreeze_record_data)!=3 ){
					set_error('解冻：解冻记录插入参数错误');
					return false;
				}
				$record_info = $this->fund_auth_record_table->where([
					'trade_no' => $unfreeze_record_data['trade_no'],
				])->find();
				if(!$record_info){
					$record_data =[
						'auth_id'=>$fundauth_info['auth_id'],
						'type'=>FundAuth::YajinToUnfreeze,
						'amount'=>$unfreeze_record_data['amount'],
						'order_id'=>$unfreeze_record_data['order_id'],
						'trade_no'=>$unfreeze_record_data['trade_no'],
						'create_time'=>time(),
					];
					$b =$this->fund_auth_record_table->add($record_data);
					if(!$b){
						set_error("插入预授权解冻交易记录失败");
						return false;
					}
				}
				//发送短信
				$sms_data =[
					'mobile' => $order_info['mobile'],
					'orderNo' => $order_info['order_no'],
					'realName' =>$order_info['realname'],
					'goodsName' =>$order_info['goods_name'],
				];
				\zuji\sms\SendSms::remove_authorize($sms_data);
				return true;
			}
			//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			// | 代扣协议存在，继续更新订单信息																									+++
			//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			$member_username = $this->member_table->fetch_by_id($order_info['user_id'],'username');
			// 当前 操作员
			$admin = [
				'id' => $order_info['user_id'],
				'username' =>$member_username
			];
			$Operator = new oms\operator\User( $admin['id'], $admin['username'] );

			// 订单对象
			$Order = new oms\Order($order_info);

			// 订单 观察者主题
			$OrderObservable = $Order->get_observable();
			// 订单 观察者  订单流
			$FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
			// 订单 观察者  日志
			$LogObserver = new oms\observer\LogObserver( $OrderObservable,'资金授权通知', '资金授权通知' );
			$LogObserver->set_operator($Operator);

	        if(!$Order->allow_to_funds_authorize()){
				set_error('禁止资金授权:'.get_error());
				return false;
	        }
			// 资金授权成功
			$b = $Order->funds_authorize();
			if( !$b ){
				set_error('更新订单资金预授权状态失败:'.get_error());
				return false;
			}

			//查看是否有取消的分期 如果有 恢复分期
            $this->instalment_table = $this->load->table('order2/order2_instalment');
            $data = [
                'status' =>Instalment::UNPAID,
                'update_time'=> time(),//更新时间
            ];
            $b =$this->instalment_table->where(['order_id'=>$order_info['order_id'],'status'=>Instalment::CANCEL])->save($data);
            if($b===false){
                set_error("订单分期状态恢复失败");
                return false;
            }
			try{
			//授权成功发送邮件给客服人员
            //发送邮件 -----begin
            $data =[
                'subject'=>'用户已授权',
                'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
                'address'=>[
                    ['address' => \zuji\email\EmailConfig::Service_Username]
                ],
            ];

            $send =\zuji\email\EmailConfig::system_send_email($data);
            if(!$send){
                \zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, "发送邮件失败", $data);
            }
            //发送邮件------end

            //发送短信
            $sms_data =[
                'mobile' => $order_info['mobile'],
                'orderNo' => $order_info['order_no'],
                'realName' =>$order_info['realname'],
                'goodsName' =>$order_info['goods_name'],
            ];
            \zuji\sms\SendSms::authorize_success($sms_data);
			}catch (\Exception $exc) {
				\zuji\debug\Debug::error(\zuji\debug\Location::L_Trade, '[支付异步通知]发送邮件失败', ['data'=>data,'send'=>$send,'error_msg'=>$exc->getMessage()]);
			}

		}
		return true;
    }

    private function _parse_where( $where=[] ){

	// 参数过滤
	$where = filter_array($where, [
	    'auth_id' => 'required',
	    'request_no' => 'required|is_string',
	    'order_no' => 'required|is_string',
	    'auth_no' => 'required|is_string',
	    'auth_status' => 'required',
	    'topay' => 'required',
	    'begin_time' => 'required',
	    'end_time' => 'required',
	]);
//
	// 结束时间（可选），默认为为当前时间
	if( !isset($where['end_time']) ){
	    $where['end_time'] = time();
	}
	// 开始时间（可选）
	if( isset($where['begin_time'])){
	    if( $where['begin_time']>$where['end_time'] ){
		return false;
	    }
	    $where['create_time'] = ['between',[$where['begin_time'], $where['end_time']]];
	}else{
	    $where['create_time'] = ['LT',$where['end_time']];
	}
	unset($where['begin_time']);
	unset($where['end_time']);

	// auth_id 支持多个
	if( isset($where['auth_id']) ){
	    if(is_string($where['auth_id']) ){
		$where['auth_id'] = explode(',',$where['auth_id']);
		if( !is_array($where['auth_id']) ){
			return false;
		}
	    }elseif(is_int($where['auth_id'])){
		$where['auth_id'] = [$where['auth_id']];
	    }
	    if(count($where['auth_id'])==1 ){
		$where['auth_id'] = $where['auth_id'][0];
	    }
	    if(count($where['auth_id'])>1 ){
		$where['auth_id'] = ['IN',$where['auth_id']];
	    }
	}
	if( isset($where['auth_status']) ){
	    $where['auth_status'] = intval($where['auth_status']);
	    if( isset($where['topay']) ){   // topay 只支持在 auth_status 指定时查询（是一个复合索引）
		$where['topay'] = $where['topay']?1:0;
	    }
	}
	// request_no 请求编号查询，使用前缀模糊查询
        if( isset($where['request_no']) ){
	    $where['request_no'] = ['LIKE', $where['request_no'] . '%'];
	}
	// order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['order_no']) ){
	    $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
	}
	// auth_no 授权号查询，使用前缀模糊查询
        if( isset($where['auth_no']) ){
	    $where['auth_no'] = ['LIKE', $where['auth_no'] . '%'];
	}
	return $where;
    }

    /**
     * 获取符合条件的记录数
     * @param   array	$where  参考 get_order_list() 参数说明
     * @return int 查询总数
     */
    public function get_count($where=[]){
	// 参数过滤
	$where = $this->_parse_where($where);
	if( $where===false ){
	    return 0;
	}
        return $this->fund_auth_table->get_count($where);
    }
    public function get_list($where=[],$additional=[]){

        // 参数过滤
        $where = $this->_parse_where($where);
        if( $where===false ){
            return [];
        }

        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required|is_int',
            'orderby' => 'required',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = Config::Page_Size;
        }
        $additional['size'] = min( $additional['size'], Config::Page_Size );

        if( !isset($additional['orderby']) ){	// 排序默认值
            $additional['orderby']='time_DESC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
	// 列表查询
        return $this->fund_auth_table->get_list($where,$additional);
    }

    public function get_info(int $auth_id){
	return $this->fund_auth_table->where(['auth_id'=>$auth_id])->find();
    }

    // request_no 是唯一的
    public function get_info_by_request_no(string $request_no){
	return $this->fund_auth_table->where(['request_no'=>$request_no])->find();
    }
    // order_no 是唯一的
    public function get_info_by_order_no(string $order_no){
	return $this->fund_auth_table->where(['order_no'=>$order_no])->find();
    }
	// order_no 是唯一的
	public function get_info_by_order_id(string $order_id){
		return $this->fund_auth_table->where(['order_id'=>$order_id])->find();
	}
    // fundauth_no 是唯一的
    public function get_info_by_fundauth_no(string $fundauth_no){
	return $this->fund_auth_table->where(['fundauth_no'=>$fundauth_no])->find();
    }

    // trade_no 是唯一的
    public function get_info_by_trade_no(string $trade_no){
	return $this->fund_auth_table->where(['trade_no'=>$trade_no])->find();
    }

	// 更新 fundauth_no
	public function set_fundauth_no(int $id,string $fundauth_no){
		$b = $this->fund_auth_table
			->where([
				'auth_id'=>$id,
			])
			->limit(1)
			->save([
				'fundauth_no'=> $fundauth_no,
				'update_time' => time(),
			]);
		if( !$b ){
			return false;
		}
		return true;
	}
	
	
    /**
     * 初始化 解冻转支付 的交易码
     * 解冻转支付时使用
     * @param array $data
     * [
     *	    'request_no' => '',
     * ]
     * @return mixed	false：初始化错误；string：交易码
     */
    public function init_trade_no($request_no){
	$trade_no = \zuji\Business::create_business_no();
	$b = $this->fund_auth_table
		->where([
		    'request_no'=>$request_no,
		])
		->limit(1)
		->save([
		    'trade_no'=> $trade_no,
		    'update_time' => time(),
		]);
	if( !$b ){
	    return false;
	}
	return $trade_no;
	/*
	 * 一下注释，禁止修改
//	$data = filter_array($params,[
//	    'request_no' => 'required',
//	    'order_id' => 'required',
//	    'order_no' => 'required',
//	    'auth_channel' => 'required',
//	    'amount' => 'required',
//	    'payer_user_id' => 'required', // 付款账号ID
//	    'payee_user_id' => 'required', // 收款账号ID
//	    'subject' => 'required',
//	]);
//	if( count($data) != 8 ){
//	    set_error('解冻转支付交易码初始化失败，参数错误');
//	    \zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '解冻转支付交易码初始化失败', ['params'=>$params,'data'=>$data]);
//	    return false;
//	}
//	// 加载
//	$payment_trade_service = $this->load->service('payment/payment_trade');
//
//	// 开启事务
//	$this->fund_auth_table->startTrans();
//	// 创建 解冻转支付 交易记录
//	$trade_info = $payment_trade_service->create([
//	    'order_id' => $data['order_id'],	    //【必须】int；订单ID
//	    'order_no' => $data['order_no'],	    //【必须】string；订单编号
//	    'trade_type' => 1,	    //【必须】int；交易类型； 这里固定值：解冻转支付
//	    'trade_channel' => $data['auth_channel'],  //【必须】string；交易渠道
//	    'amount' => $data['amount'],	    //【必须】price；交易金额
//	    'seller_id' => $data['payee_user_id'],	    //【必须】int；收款方用户ID
//	    'buyer_id' => $data['payer_user_id'],	    //【必须】int；付款方用户ID
//	    'subject' => $data['subject'],	    //【必须】string；交易标题
//	]);
//	if( !$trade_info || ! $trade_info['trade_no'] ){	// 失败
//	    $this->fund_auth_table->rollback();
//	    //set_error('交易记录创建失败');
//	    return false;
//	}
//	$b = $this->fund_auth_table
//		->where([
//		    'request_no'=>$data['request_no'],
//		])
//		->limit(1)
//		->save([
//		    'trade_no'=>$trade_info['trade_no'],
//		    'update_time' => time(),
//		]);
//	if( !$b ){
//	    $this->fund_auth_table->rollback();
//	    //set_error('解冻转支付交易码初始化失败');
//	    return false;
//	}
//	$this->fund_auth_table->commit();
//	return $trade_info['trade_no'];
	 *
	 */
    }

    /**
     * 解冻转支付 成功
     * @param array $data
     * [
     *	    'trade_no' => '',	//【必须】string； 租机交易码【在资金授权表中唯一】
     *	    'amount' => '',	//【必须】string； 支付金额（单位：元）
     * ]
     * @return mixed	false：失败；true：成功
     */
    public function unfreeze_and_pay_success($data){
		$data = filter_array($data,[
			'trade_no' => 'required',
			'amount' => 'required',
		]);
		if( count($data) != 2 ){
			set_error('unfreeze_and_pay_success()，参数错误');
			return false;
		}

		// 开启事务
		$this->fund_auth_table->startTrans();
		$b = $this->fund_auth_table
			->where([
				'trade_no'=>$data['trade_no'],
			])
			->limit(1)
			->save([
				'auth_status' => FundAuth::CLOSED,	// 关闭
				'topay' => 1,			// 解冻转支付标识
				'update_time' => time(),
			]);
		if( !$b ){
			$this->fund_auth_table->rollback();
			//set_error('解冻转支付交易码初始化失败');
			return false;
		}
		$this->fund_auth_table->commit();
		return true;
    }


     /**
     * 解冻授权
     * @param   int	 $order_id
     * @return  bool true false
     */
    public function order_unfreeze_fundauth($order_id,$amount=""){
		if(intval($order_id) < 0){
			set_error('参数错误');
			return false;
		}

		$auth_info 	= $this->get_info_by_order_id($order_id);
		if($auth_info['auth_status'] == FundAuth::CLOSED ||$auth_info['auth_status'] == FundAuth::FINISH ){
			return true;
		}

		if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
			set_error('数据错误');
			return false;
		}
		if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
			set_error('非法操作');
			return false;
		}
		if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
			set_error('授权渠道值错误');
			return false;
		}

        if ($auth_info['auth_status'] == FundAuth::FINISH || $auth_info['auth_status'] == FundAuth::CLOSED) {
            return true;
        }

		if($amount == ""){
			$amount = $auth_info['amount'] - $auth_info['unfreeze_amount'];
		}else{
		    $amount = $amount/100;
        }
		$data = [
			'auth_no' => $auth_info['auth_no'],
			'out_request_no' => $auth_info['request_no'],
			'amount' => $amount,
			'remark' => '解冻',
			'notify_url' => config('ALIPAY_FundAuth_Notify_Url'),
		];
		$auth = new alipay\fund\FundAuth();
		$b = $auth->unfreeze($data);
        if(!$b){
			set_error('支付宝资金解冻接口请求失败');
            return false;
        }

        $trade_no = \zuji\Business::create_business_no();

        $record_data =[
            'auth_id'=>$auth_info['auth_id'],
            'type'=>FundAuth::FundAuthToUnfreeze,
            'amount'=>$amount * 100,
            'order_id'=>$order_id,
            'trade_no'=>$trade_no,
            'create_time'=>time(),
            'update_time'=>time(),
            'status'=>1,
        ];
        $b = $this->fund_auth_record_table->add($record_data);

        if(!$b){
            set_error("插入预授权交易记录失败");
            return false;
        }
        return true;
    }
    /**
     *  调用支付宝分期解冻接口
     * @param array $data
     * [
     *      'order_id'=>'',//订单ID
     *      'trade_no'=>''//交易编号
     *      'amount'=>'', //解冻金额 分
     * ]
     * @return boolean
     */
    public function unfreeze_alipay($data){
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        $data = [
            'auth_no' => $auth_info['auth_no'],
            'out_request_no' => $data['trade_no'],
            'amount' => $data['amount']/100,
            'remark' => '分期解冻',
            'notify_url'=>config('ALIPAY_FundAuth_Fenqi_Unfreeze_Notify_Url'),//回调地址
        ];
        $auth = new alipay\fund\FundAuth();
        return $auth->unfreeze($data);
    }
    /**
     *  调用支付宝押金解冻接口
     * @param array $data
     * [
     *      'order_id'=>'',//订单ID
     *      'trade_no'=>''//交易编号
     *      'amount'=>'', //解冻金额 分
     * ]
     * @return boolean
     */
    public function yajin_unfreeze_alipay($data){
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        $data = [
            'auth_no' => $auth_info['auth_no'],
            'out_request_no' => $data['trade_no'],
            'amount' => $data['amount']/100,
            'remark' => '押金解冻',
            'notify_url'=>\config('ALIPAY_FundAuth_Yajin_Unfreeze_Notify_Url'),//回调地址
        ];
        $auth = new alipay\fund\FundAuth();
        return $auth->unfreeze($data);
    }
    /**
     * 调用支付宝转支付接口
     * @param array $data
     * [
     *      'order_id'=>'',//订单ID
     *      'trade_no'=>''//交易编号
     *      'amount'=>'', //解冻金额 分
     * ]
     * @return boolean
     */
    public function unfreeze_to_pay($data){
        // 执行转支付操作
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        $data = [
            'out_trade_no' => $data['trade_no'],
            'auth_no' => $auth_info['auth_no'],
            'payer_logon_id' => $auth_info['payer_logon_id'],
            'payee_user_id' => $auth_info['payee_logon_id'],
            'amount' => $data['amount']/100,
            'subject' => '预授权分期解冻转支付',
            'notify_url' => $_SERVER['ALIPAY_FundAuth_Fenqi_Unfreeze_To_Pay_Notify_Url'],
        ];
        // 请求解冻转支付接口
        $auth = new alipay\fund\FundAuth();
        return $auth->unfreeze_and_pay($data);
    }

    /**
     * 调用支付宝转支付接口
     * @param array $data
     * [
     *      'order_id'=>'',//订单ID
     *      'trade_no'=>''//交易编号
     *      'amount'=>'', //解冻金额 分
     * ]
     * @return boolean
     */
    public function yajin_unfreeze_to_pay($data){
        // 执行转支付操作
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        $data = [
            'out_trade_no' => $data['trade_no'],
            'auth_no' => $auth_info['auth_no'],
            'payer_logon_id' => $auth_info['payer_logon_id'],
            'payee_logon_id' => $auth_info['payee_logon_id'],
            'amount' => $data['amount']/100,
            'subject' => '预授权押金解冻转支付',
            'notify_url' => $_SERVER['ALIPAY_FundAuth_Yajin_Unfreeze_To_Pay_Notify_Url'],
        ];
        // 请求解冻转支付接口
        $auth = new alipay\fund\FundAuth();

        return $auth->unfreeze_and_pay($data);
    }


    /**
     * 解冻 - 分期
     * @param array $data
     * [
     *      'order_id'=>,
     *      'instalment_id'=>'',//int 分期ID
     *      'amount'=>'',// int 解冻金额 单位(分)
     *      'trade_no'=> //string 交易码
     * ]
     * @return boolean
     */
    public function unfreeze_fenqi($data){
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'instalment_id' => 'required|is_id',
            'amount' =>'required',
            'trade_no'=>'required',
            'subject'=>'required',
        ]);
        if( count($data)!=5 ){
            set_error('参数错误');
            return false;
        }
		//  查询资金授权记录
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
            set_error("数据错误");return false;
        }
        if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
            set_error("资金授权状态不允许解冻");return false;
        }
        if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
            set_error("授权渠道值错误");return false;
        }
		
		// 查询 分期对应的解冻记录
        $record_info = $this->fund_auth_record_table->where([
            'instalment_id' => $data['instalment_id'],
            ])->find();
		// 不存在是新增 解冻记录
        if(!$record_info){
            $record_info =[
                'auth_id'=>$auth_info['auth_id'],
                'type'=>FundAuth::FenqiToUnfreeze,
                'amount'=>$data['amount'],
                'order_id'=>$data['order_id'],
                'instalment_id'=>$data['instalment_id'],
                'trade_no'=>$data['trade_no'],
                'create_time'=>time(),
            ];
            $b =$this->fund_auth_record_table->add($record_info);
            if(!$b){
                set_error("创建分期解冻记录失败");
                return false;
            }
		}else{// 已经存在，则判断 记录状态，禁止重复操作
			if( $record_info['status']==1 ){
                set_error("已解冻，禁止重复操作");
                return false;
			}
		}
		
        $data = [
            'auth_no' => $auth_info['auth_no'],
            'out_request_no' => $record_info['trade_no'],
            'amount' => $record_info['amount']/100,
            'remark' => $data['subject'],
            'notify_url'=>config('ALIPAY_FundAuth_Fenqi_Unfreeze_Notify_Url'),//回调地址
        ];
        $auth = new \alipay\fund\FundAuth();
        $b = $auth->unfreeze($data);
        if(!$b){
            set_error('分期解冻失败('. get_error().')');
            return false;
        }
        return true;
    }

    /**
     * 转支付 -分期
     * @param array $data
     * [
     *      'order_id'=>,
     *      'instalment_id'=>'',//分期ID
     *      'amount'=>'',// 金额 单位(分)
     *     'trade_no'=> //string 交易码
     * ]
     * @return boolean
     */
    public function unfreeze_to_pay_fenqi($data){
        $data = filter_array($data, [
            'order_id' => 'required',
            'instalment_id' => 'required',
            'amount' =>'required',
            'trade_no'=>'required',
        ]);
        if( count($data)!=4 ){
            set_error('参数错误');
            return false;
        }
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
            set_error("数据错误");return false;
        }
        if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
            set_error("非法操作");return false;
        }
        if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
            set_error("授权渠道值错误");return false;
        }
        $auth = \zuji\payment\FundAuth::create($auth_info['auth_channel']);
        if (!$auth) {
            set_error("授权渠道实例化失败");return false;
        }
        $record_info = $this->fund_auth_record_table->where([
            'instalment_id' => $data['instalment_id'],
        ])->find();
        if(!$record_info){
            $record_info =[
                'auth_id'=>$auth_info['auth_id'],
                'type'=>FundAuth::FenqiToPay,
                'amount'=>$data['amount'],
                'order_id'=>$data['order_id'],
                'instalment_id'=>$data['instalment_id'],
                'create_time'=>time(),
                'trade_no'=>$data['trade_no'],
            ];
            $b =$this->fund_auth_record_table->add($record_info);
            if(!$b){
                set_error("插入预授权分期解冻转支付记录失败");
                return false;
            }
        }
        $b =$this->unfreeze_to_pay($data);
        if(!$b){
            return false;
        }
        return true;
    }

    /**
     * 解冻-押金
     * @param array $data
     * [
     *      'order_id'=>,
     *      'amount'=>'',// int 解冻金额 单位(分)
     *      'trade_no'=> //string 交易码
     * ]
     * @return boolean
     */
    public function unfreeze_yajin($data){
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'amount' =>'required|is_int',
            'trade_no'=>'required',
        ]);
        if( count($data)!=3 ){
            set_error('参数错误');
            return false;
        }
        $auth_info = $this->get_info_by_order_id($data['order_id']);
        if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
            set_error("数据错误");return false;
        }
        if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
            set_error("非法操作");return false;
        }
        if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
            set_error("授权渠道值错误");return false;
        }
        $record_info = $this->fund_auth_record_table->where([
            'trade_no' => $data['trade_no'],
        ])->find();
        if(!$record_info){
            $record_data =[
                'auth_id'=>$auth_info['auth_id'],
                'type'=>FundAuth::YajinToUnfreeze,
                'amount'=>$data['amount'],
                'order_id'=>$data['order_id'],
                'trade_no'=>$data['trade_no'],
                'create_time'=>time(),
            ];
            $b =$this->fund_auth_record_table->add($record_data);
            if(!$b){
                set_error("插入预授权交易记录失败");
                return false;
            }
        }
        $b =$this->yajin_unfreeze_alipay($data);
        if(!$b){
            //set_error("预授权押金解冻失败");
            return false;
        }
        return true;
    }
    /**
     * 转支付 -押金
     * @param array $data
     * [
     *      'order_id'=>,
     *      'amount'=>'',// 金额 单位(分)
     *      'trade_no'=>'',// string 交易码
     * ]
     * @return boolean
     */
    public function unfreeze_to_pay_yajin($data){
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'amount' =>'required|is_int',
            'trade_no'=>'required',
        ]);
        if( count($data)!=3 ){
            set_error('参数错误');
            return false;
        }

        $auth_info = $this->get_info_by_order_id($data['order_id']);
        if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
            set_error("数据错误");return false;
        }
        if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
            set_error("非法操作");return false;
        }
        if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
            set_error("授权渠道值错误");return false;
        }
        $auth = \zuji\payment\FundAuth::create($auth_info['auth_channel']);
        if (!$auth) {
            set_error("授权渠道实例化失败");return false;
        }
        $record_info = $this->fund_auth_record_table->where([
            'trade_no' => $data['trade_no'],
        ])->find();
        if(!$record_info){
            $record_data =[
                'auth_id'=>$auth_info['auth_id'],
                'type'=>FundAuth::YajinToPay,
                'amount'=>$data['amount'],
                'order_id'=>$data['order_id'],
                'create_time'=>time(),
                'trade_no'=>$data['trade_no'],
            ];
            $b =$this->fund_auth_record_table->add($record_data);
            if(!$b){
                set_error("插入预授权押金解冻转支付记录失败");
                return false;
            }
        }

        $b =$this->yajin_unfreeze_to_pay($data);
        if(!$b){
            set_error("预授权押金解冻转支付失败".get_error());
            return false;
        }

        return true;
    }


}
