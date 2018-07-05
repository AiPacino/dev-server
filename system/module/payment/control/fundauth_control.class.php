<?php

use zuji\payment\FundAuth;

hd_core::load_class('init', 'admin');

class fundauth_control extends init_control {

	/**
	 * @var array 关键字搜索类型列表
	 */
	protected $keywords_type_list = [
		'request_no' => '请求号',
		'order_no' => '订单号',
		'auth_no' => '授权码',
	];

	public function _initialize() {
		parent::_initialize();
		$this->order_service 		= $this->load->service('order2/order');
		$this->fundauth_service = $this->load->service('payment/fund_auth');
		$this->service_order_log = $this->load->service('order/order_log');
		$this->load->helper('order/function');

	}

	/**
	 * 资金预授权列表
	 */	
	public function index() {
		$this->fundauth_service = $this->load->service('payment/fund_auth');

		$where = [];
		$additional = ['page' => 1, 'size' => 20];

		// 查询条件
		if (isset($_GET['auth_status'])) {
			$where['auth_status'] = intval($_GET['auth_status']);
			if ($where['auth_status'] == 0) {
				unset($where['auth_status']);
			}
			if (FundAuth::CLOSED == $where['auth_status'] && isset($_GET['close_type'])) {
				if ($_GET['close_type'] == 'jiedong') {
					$where['topay'] = 0;
				} elseif ($_GET['close_type'] == 'zhifu') {
					$where['topay'] = 1;
				}
			}
		}
		if ($_GET['keywords'] != '') {
			if ($_GET['kw_type'] == 'auth_id') {
				$where['auth_id'] = $_GET['keywords'];
			} elseif ($_GET['kw_type'] == 'auth_no') {
				$where['auth_no'] = $_GET['keywords'];
			} elseif ($_GET['kw_type'] == 'request_no') {
				$where['request_no'] = $_GET['keywords'];
			} elseif ($_GET['kw_type'] == 'order_no') {
				$where['order_no'] = $_GET['keywords'];
			}
		}

		if (isset($_GET['begin_time'])) {
			$where['begin_time'] = strtotime($_GET['begin_time']);
			if (!$where['begin_time']) {
				unset($where['begin_time']);
			}
		}
		if (isset($_GET['end_time'])) {
			$where['end_time'] = strtotime($_GET['end_time']);
			if (!$where['end_time']) {
				unset($where['end_time']);
			}
		}

		$limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
		$additional['page'] = intval($_GET['page']);
		$additional['size'] = intval($limit);
		// 查询
		$count = $this->fundauth_service->get_count($where, $additional);
		$fundauth_list = [];
		if ($count > 0) {
			// 授权列表查询
			$fundauth_list = $this->fundauth_service->get_list($where, $additional);
			foreach ($fundauth_list as &$item) {
				// 格式化状态
				$item['auth_status_show'] = FundAuth::getStatusName($item['auth_status']);
				if (FundAuth::CLOSED == $item['auth_status']) {
					if ($item['topay'] == 1) {
						$item['auth_status_show'] .= '（转支付）';
					} elseif ($item['topay'] == 0) {
						$item['auth_status_show'] .= '（解冻）';
					}
				}
				$item['create_time_show'] = date('Y-m-d H:i:s', $item['create_time']);
				$item['update_time_show'] = $item['update_time'] > 0 ? date('Y-m-d H:i:s', $item['update_time']) : '--';
				// 按钮是否展示
				$item['yajinjiedong'] 	  = $item['auth_status'] == FundAuth::AUTHORIZED ? true : false;
				$item['yajinzhifu'] 	  = $item['auth_status'] == FundAuth::AUTHORIZED ? true : false;
			}
		}
		$data_table = array(
			'th' => array(
				'request_no' => array('length' => 10, 'title' => '请求编号'),
				'order_no' => array('title' => '订单编号', 'length' => 10),
				'auth_no' => array('title' => '授权号', 'length' => 15),
				'auth_channel' => array('title' => '授权渠道', 'length' => 5),
				'amount' => array('title' => '授权金额', 'length' => 8),
				'unfreeze_amount' => array('title' => '解冻金额', 'length' => 8),
				'pay_amount' => array('title' => '支付金额', 'length' => 8),
				'auth_status_show' => array('title' => '状态', 'length' => 6),
				'create_time_show' => array('title' => '创建时间', 'length' => 10),
				'update_time_show' => array('title' => '更新时间', 'length' => 10),
			),
			'record_list' => $fundauth_list,
			'pages' => $this->admin_pages($count, $additional['size']),
		);

		// 头部 tab 切换设置
		$tab_list = [];
		$status_list = array_merge(['0' => '全部'], FundAuth::getStatusList());
		foreach ($status_list as $k => $name) {
			$css = '';
			if ($_GET['auth_status'] == $k) {
				$css = 'current';
			}
			$url = self::current_url(array('auth_status' => $k));
			$tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
		}
		foreach (['jiedong' => '（解冻）', 'zhifu' => '（转支付）'] as $k => $name) {
			$css = '';
			if ($_GET['auth_status'] == FundAuth::CLOSED && $_GET['close_type'] == $k) {
				$css = 'current';
			}
			$url = self::current_url(['auth_status' => FundAuth::CLOSED, 'close_type' => $k]);
			$tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
		}

		$this->load->librarys('View')
				->assign('tab_list', $tab_list)
				->assign('keywords_type_list', $this->keywords_type_list)
				->assign('data_table', $data_table)
				->display('index');
	}

	/**
	 * 预授权操作记录
	 */
	public function jilu() {
		$this->fundauth_service = $this->load->service('payment/fund_auth');

		$auth_id = intval($_REQUEST['auth_id']);
		if ($auth_id<1) {
			echo "请求错误";
			exit;
		}
		
		$auth_info = $this->fundauth_service->get_info($auth_id);

		if ( !$auth_info ) {
			echo "数据错误";
			exit;
		}
//		jilu_list
		// 查询 记录
		
        $fund_auth_record_table = $this->load->table('payment/payment_fund_auth_record');
		$where = [
			'auth_id' => $auth_id,
		];
		$additional = [
			'page' => 1,
			'size' => 20,
		];
		$count = $fund_auth_record_table->get_count($where);
		$record_list = 0;
		if( $count>0 ){
			$record_list = $fund_auth_record_table->get_list($where,$additional);
		}

		// 格式化数据
		foreach( $record_list as &$item){
			$item['amount'] = \zuji\order\Order::priceFormat($item['amount']/100);
			$item['type_show'] = FundAuth::getAuthName($item['type']);
			$item['trade_time_show'] = $item['update_time']>0?date('Y-m-d H:i:s',$item['update_time']):'--';
		}
		$data_table = array(
			'th' => array(
				'id' => array('title' => 'ID','length' => 10),
				'auth_id' => array('title' => '授权ID', 'length' => 10),
				'type_show' => array('title' => '操作类型', 'length' => 15),
				'amount' => array('title' => '金额', 'length' => 10),
				'trade_time_show' => array('title' => '交易时间', 'length' => 15),
				'trade_no' => array('title' => '租机交易码', 'length' => 15),
				'out_trade_no' => array('title' => '支付宝交易码', 'length' => 25),
			),
			'lists' => $record_list,
			'pages' => $this->admin_pages($count, $additional['size']),
		);
		$this->load->librarys('View')
				->assign('data_table', $data_table)
				->display('jilu_list');
	}
	/**
	 * 解冻
	 */
	public function unfreeze() {
		$this->fundauth_service = $this->load->service('payment/fund_auth');

		if (!isset($_POST['auth_id']) > 0) {
			echo "请求错误";
			exit;
		}
		$auth_info = $this->fundauth_service->get_info($_POST['auth_id']);

		if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
			echo "数据错误";
			exit;
		}
		if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
			echo "非法操作";
			exit;
		}
		if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
			echo "授权渠道值错误";
			exit;
		}
		$data = [
			'auth_no' => $auth_info['auth_no'],
			'out_request_no' => $auth_info['request_no'],
			'amount' => $auth_info['amount'],
			'remark' => '解冻',
			'notify_url' => config('ALIPAY_FundAuth_Notify_Url'),
		];
		$auth = new alipay\fund\FundAuth();
		$result = $auth->unfreeze($data);

		if (!$result) {
			echo "解冻失败";
			exit;
		}

		echo "操作异常";
		exit;
	}

	/**
	 * 解冻转支付
	 */
	public function unfreeze_and_pay() {
		$this->fundauth_service = $this->load->service('payment/fund_auth');

		if (!isset($_POST['auth_id']) > 0) {
			echo "请求错误";
			exit;
		}
		$auth_info = $this->fundauth_service->get_info($_POST['auth_id']);

		if (!$auth_info || !$auth_info['auth_no'] || !$auth_info['request_no'] || !$auth_info['auth_channel']) {
			echo "数据错误";
			exit;
		}
		if ($auth_info['auth_status'] != FundAuth::AUTHORIZED) {
			echo "非法操作";
			exit;
		}
		if (!\zuji\payment\FundAuth::verifyPlatform($auth_info['auth_channel'])) {
			echo "授权渠道值错误";
			exit;
		}
		$auth = \zuji\payment\FundAuth::create($auth_info['auth_channel']);
		if (!$auth) {
			echo "授权渠道实例化失败";
			exit;
		}

		$subject = '预授权解冻转支付--测试';

		// 商户交易码，用于后续查账和退款
		if (!$auth_info['trade_no']) { // 不存在，则创建
			//
			$auth_info['subject'] = $subject;
			// 初始化 解冻转支付 记录的 交易码
			$trade_no = $this->fundauth_service->init_trade_no($auth_info['request_no']);
			if (!$trade_no) {
				echo "交易码创建失败";
				exit;
			}
			// 交易码赋值
			$auth_info['trade_no'] = $trade_no;
		}

		// 执行解冻操作
		$data = [
			'out_trade_no' => $auth_info['trade_no'],
			'auth_no' => $auth_info['auth_no'],
			'payer_logon_id' => $auth_info['payer_logon_id'],
			'payee_logon_id' => $auth_info['payee_logon_id'],
			'amount' => $auth_info['amount'],
			'subject' => $subject,
			'notify_url' => config('ALIPAY_FundAuth_Pay_Url'),
		];

		// 请求解冻转支付接口
		$result = $auth->unfreeze_and_pay($data);
		//file_put_contents('./unfreeze_and_pay.log',"\n".$result,FILE_APPEND);
		//解析XML
		$doc = new DOMDocument();
		$doc->loadXML($result);
		// 下单并支付成功
		if ($doc->getElementsByTagName("result_code")->item(0)->nodeValue == 'ORDER_SUCCESS_PAY_SUCCESS') {
			$code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
			$msg = $doc->getElementsByTagName("result_message")->item(0)->nodeValue;
			echo '成功(' . $code . ')：' . $msg;
			exit;
		}
		if ($doc->getElementsByTagName("result_code")->item(0)->nodeValue) {
			$code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
			$msg = $doc->getElementsByTagName("display_message")->item(0)->nodeValue;
			echo '错误(' . $code . ')：' . $msg;
			exit;
		}
		echo "解冻转支付操作失败";
		exit;
	}

	/**
	 * 解冻押金
	 */
	public function deposit_unfreeze() {
		$this->order_service 		= $this->load->service('order2/order');
		$this->fundauth_service = $this->load->service('payment/fund_auth');
		$this->service_order_log = $this->load->service('order/order_log');

		$id = intval($_REQUEST['auth_id']);
		$auth_info = $this->fundauth_service->get_info($id);
        $jiedongamount = \zuji\order\Order::priceFormat($auth_info['amount'] - $auth_info['unfreeze_amount'] - $auth_info['pay_amount']);

		if (checksubmit('dosubmit')) {

			if( $id < 1 ){
				showmessage('参数错误');
			}
			$amount 	= floatval($_POST['amount']);
			if( $amount < 0 ){
				showmessage('参数错误');
			}

			if($amount > $jiedongamount){
				showmessage('解冻金额不能大于授权金额');
			}

			$trade_no = \zuji\Business::create_business_no();

			$data = [
				'order_id'=>$auth_info['order_id'],
				'amount'=>intval($amount * 100), //元转化分
				'trade_no'=>$trade_no,
			];
			// 开启事务
			$b = $this->order_service->startTrans();
			if( !$b ){
				$this->order_service->rollback();
				showmessage('事务开启失败', 'null');
			}
			// 写日志
			$operator = get_operator();
			$log=[
				'order_no'=>$auth_info['order_no'],
				'action'=>"解冻押金",
				'operator_id'=>$operator['id'],
				'operator_name'=>$operator['username'],
				'operator_type'=>$operator['operator_type'],
				'msg'=>trim($_POST['remark']),
			];
			$log_b = $this->service_order_log->add($log);
			if( !$log_b ){
				$this->order_service->rollback();
				showmessage('解冻押金写入日志失败:'.get_error());
			}

			// 请求解冻接口
			$b = $this->fundauth_service->unfreeze_yajin($data);
			if( !$b ){
				$this->order_service->rollback();
				showmessage('解冻押金失败:'.get_error());
			}

			// 事务提交
			$this->order_service->commit();
			showmessage('解冻押金成功', '', 1);
		}else{

			$this->load->librarys('View')
				->assign('auth_info',$auth_info)
				->assign('jiedongamount',$jiedongamount)
				->display('deposit_unfreeze');
		}

	}

	/**
	 * 押金转支付
	 */
	public function deposit_unfreeze_topay() {
		$this->order_service 		= $this->load->service('order2/order');
		$this->fundauth_service = $this->load->service('payment/fund_auth');
		$this->service_order_log = $this->load->service('order/order_log');
		
		$id = intval($_REQUEST['auth_id']);
		$auth_info = $this->fundauth_service->get_info($id);
		$zhifuamount = \zuji\order\Order::priceFormat($auth_info['amount'] - $auth_info['unfreeze_amount'] - $auth_info['pay_amount']);

		if (checksubmit('dosubmit')) {
			if( $id < 1 ){
				showmessage('参数错误');
			}
			$amount 	= floatval($_POST['amount']);
			if( $amount < 0 ){
				showmessage('参数错误');
			}

			if($amount > $zhifuamount){
				showmessage('转支付金额不能大于剩余金额');
			}

			$trade_no = \zuji\Business::create_business_no();

			$data = [
				'order_id'=>$auth_info['order_id'],
				'amount'=> intval($amount * 100), // 元转化分
				'trade_no'=>$trade_no,
			];

			// 开启事务
			$b = $this->order_service->startTrans();
			if( !$b ){
				$this->order_service->rollback();
				showmessage('事务开启失败', 'null');
			}

			// 写日志
			$operator = get_operator();
			$log=[
				'order_no'=>$auth_info['order_no'],
				'action'=>"押金转支付",
				'operator_id'=>$operator['id'],
				'operator_name'=>$operator['username'],
				'operator_type'=>$operator['operator_type'],
				'msg'=>trim($_POST['remark']),
			];
			$log_b = $this->service_order_log->add($log);
			if( !$log_b ){
				$this->order_service->rollback();
				showmessage('押金转支付写入日志失败:'.get_error());
			}

			// 请求转支付接口
			$b = $this->fundauth_service->unfreeze_to_pay_yajin($data);
			if( !$b ){
				$this->order_service->rollback();
				showmessage('押金转支付失败:'.get_error());
			}

			// 事务提交
			$this->order_service->commit();
			showmessage('押金转支付成功', '', 1);
		}else{

			$this->load->librarys('View')
				->assign('auth_info',$auth_info)
				->assign('zhifuamount',$zhifuamount)
				->display('deposit_unfreeze_topay');
		}
	}
}
