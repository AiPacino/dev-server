<?php

use zuji\payment\Instalment;
use zuji\order\Order;
use zuji\debug\Location;
use zuji\payment\FundAuth;
use zuji\payment\Payment;

hd_core::load_class('init', 'admin');
class instalment_control extends init_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单号',
		'order_id' => '订单ID',
		'mobile' => '手机号',
    ];
	
	protected $unfreeze_status = [
		'0' => '有冻结',
		'1' => '已解冻',
		'2' => '无冻结',
	];

	public function _initialize() {
        parent::_initialize();
  //       $this->instalment_service   = $this->load->service('order2/instalment');
  //       $this->order_service 		= $this->load->service('order2/order');
		// $this->order_table 			= $this->load->table('order2/order');
		// $this->member_service 		= $this->load->service('member2/member');
		// $this->trade_service 		= $this->load->service('payment/payment_trade');
		// $this->withhold_service		= $this->load->service('payment/withhold');
		// $this->fundauth_service		= $this->load->service('payment/fund_auth');
		// $this->fund_auth_record_table = $this->load->table('payment/payment_fund_auth_record');
    }

    public function index(){
    	$this->instalment_service   = $this->load->service('order2/instalment');

        $where = [];
        $additional = ['page'=>1,'size'=>20];
        // 查询条件
        if(isset($_GET['status'])){
            $where['status'] = intval($_GET['status']);
            if( $where['status'] == 0){
                unset($where['status']);
            }
        }


        if($_GET['keywords']!=''){
            if($_GET['kw_type']=='order_no'){
                $order_id = get_order_id($_GET['keywords']);
				$where['order_id'] = $order_id;
            }
			if($_GET['kw_type']=='order_id'){
				$where['order_id'] = trim(intval($_GET['keywords']));
			}
			if($_GET['kw_type']=='mobile'){
				$where['mobile'] = trim($_GET['keywords']);
			}
        }

        if(isset($_GET['begin_time'])){
            $where['begin_time'] = $_GET['begin_time'];
            if( !$where['begin_time'] ){
                unset($where['begin_time']);
            }
        }

		$limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
		$additional['page'] = intval($_GET['page']);
		$additional['size'] = intval($limit);
        
        // 查询
        $count = $this->instalment_service->get_count($where);

        $instalment_list = [];
		$multi_createpay = 0;
        if( $count>0 ){
            // 订单分期付款查询
            $instalment_list = $this->instalment_service->get_list($where,$additional);
			$ids = "";
            foreach ($instalment_list as &$item){
				$item['allow_koukuan'] = 1;
                // 格式化状态
                $item['order_no'] 			= get_order_no($item['order_id']);
				$item['status_show'] 		= Instalment::getStatusName($item['status']);
                $item['amount'] 			= Order::priceFormat($item['amount']/100);
				$item['payment_time_show'] 	= $item['payment_time']>0 ? date("Y-m-d H:i:s",$item['payment_time']): '--';
				$item['update_time_show'] 	= $item['update_time']>0 ? date("Y-m-d H:i:s",$item['update_time']): '--';
				$item['allow_koukuan'] 		= $this->instalment_service->allow_withhold($item['id']);
				// 是否解冻
				$item['jiedong_btn'] = false;

				if( isset($item['unfreeze_status']) && $item['unfreeze_status']==0  ){
					$item['jiedong_btn'] = true;
				}
				$item['unfreeze_status'] 	= $this->unfreeze_status[$item['unfreeze_status']];

				if($item['allow_koukuan'] == 1){
					$ids .= $item['id'] . ",";
					$multi_createpay = 1;
				}
			}
        }

        $data_table = array(
            'th' => array(
                'realname' => array('length' => 8,'title' => '用户姓名'),
				'mobile' => array('length' => 8,'title' => '联系电话'),
				'order_no' => array('length' => 10,'title' => '订单号'),
                'term' => array('title' => '分期', 'length' => 8),
                'times' => array('title' => '第几期', 'length' => 5),
                'amount' => array('title' => '应付金额', 'length' => 5),
                'status_show' => array('title' => '状态', 'length' => 5),
				'unfreeze_status' => array('title' => '解冻状态', 'length' => 8),
				'trade_no' => array('title' => '租机交易号', 'length' => 10),
                'out_trade_no' => array('title' => '第三方交易号', 'length' => 10),
				'payment_time_show' => array('title' => '扣款成功时间', 'length' => 10),
            ),
            'record_list' => $instalment_list,
            'pages' => $this->admin_pages($count, $additional['size']),
        );

        // 头部 tab 切换设置
        $tab_list = [];
        $status_list = array_merge(['0'=>'全部'],Instalment::getStatusList());
        foreach( $status_list as $k=>$name ){
            $css = '';
            if ($_GET['status'] == $k){
                $css = 'current';
            }
            $url = self::current_url(array('status'=>$k,'begin_time'=>$_GET['begin_time']));
            $tab_list[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
        }
		$ids = substr($ids,0,strlen($ids)-1);

        $this->load->librarys('View')
            ->assign('tab_list',$tab_list)
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('status', $_GET['status'])
			->assign('multi_createpay', $multi_createpay)
			->assign('ids', $ids)
			->assign('data_table', $data_table)
            ->display('instalment');
    }

	/**
	 * 扣款
	 */
	public function createpay(){
		$this->instalment_service   = $this->load->service('order2/instalment');
		$this->channel_appid = $this->load->service('channel/channel_appid');
        $this->order_service 		= $this->load->service('order2/order');
		$this->member_service 		= $this->load->service('member2/member');
		$this->fund_auth_record_table = $this->load->table('payment/payment_fund_auth_record');
		$id = intval($_GET['id']);
		if (checksubmit('dosubmit')){
			$instalment_id = $_POST['instalment_id'];
			if( $instalment_id<1 ){
				showmessage('参数错误', 'null');
			}
			$remark = trim($_POST['remark']);
			if( mb_strlen($remark)<5 ){
				showmessage('备注最少五个字符', 'null');
			}

			// 判断是否允许扣款
			$allow = $this->instalment_service->allow_withhold($instalment_id);
			if(!$allow){
				showmessage('不允许扣款', 'null');
			}

			// 生成交易码
			$trade_no = \zuji\Business::create_business_no();
			
			// 开启事务
			$b = $this->order_service->startTrans();
			if( !$b ){
				$this->order_service->rollback();
				showmessage('事务开启失败', 'null');
			}
			// 查询分期信息
			$where = [
				'id' => $instalment_id,
			];
			$instalment_info = $this->instalment_service->get_info($where,['lock'=>true]);
			if( !$instalment_info ){
				// 提交事务
				$this->order_service->rollback();
				showmessage('分期查询失败', 'null');
			}
			
			// 状态在支付中或已支付时，直接返回成功
			if( $instalment_info['status'] == Instalment::SUCCESS && $instalment_info['status'] = Instalment::PAYING ){
				// 提交事务
				$this->order_service->rollback();
				showmessage('操作成功', 'null', 1);
			}

			// 只有 未扣款或扣款失败时，才允许扣款操作
			if( $instalment_info['status'] != Instalment::UNPAID && $instalment_info['status'] != Instalment::FAIL ){
				// 提交事务
				$this->order_service->rollback();
				showmessage('禁止扣款操作：分期状态错误', 'null');
			}
			
			// 时间限制，
			// 1）要扣款的分期月份，是当前月或历史月的分期
			// 2）当前操作时间必须是16号开始（包含），才允许扣款操作
			$instalment_ym = intval($instalment_info['term']);
			$current_ym = intval(date('Ym'));
			if( $instalment_ym>$current_ym || ( $instalment_ym==$current_ym && intval(date('d'))<15) ){
				// 提交事务
				$this->order_service->rollback();
				showmessage('禁止扣款操作：未到扣款期限'.$instalment_info['term'].' '.intval(date('Ym')).' '.intval(date('d')), 'null');
			}

			// 扣款交易码
			if( $instalment_info['trade_no']=='' ){
				// 1)记录租机交易码
				$b = $this->instalment_service->set_trade_no($instalment_id, $trade_no);
				if( $b === false ){
					$this->order_service->rollback();
					showmessage('租机交易码保存失败', 'null');
				}
				$instalment_info['trade_no'] = $trade_no;
			}
			$trade_no = $instalment_info['trade_no'];
			
			// 订单
			$order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']]);
			if( !$order_info ){
				$this->order_service->rollback();
				showmessage('订单查询失败', 'null');
			}

			// 查询用户协议
			$user_info =  $this->member_service->get_info(['id'=>$order_info['user_id']]);
			if( !$user_info ){
				$this->order_service->rollback();
				showmessage('用户查询失败', 'null');
			}
			
			// 保存 备注，更新状态
			$data = [
				'remark' => $remark,
				'status' => Instalment::PAYING,// 扣款中
			];
			$result = $this->instalment_service->save($where,$data);
			if(!$result){
				$this->order_service->rollback();
				showmessage("扣款备注保存失败", 'null');
			}

			// 商品
			$subject = $order_info['goods_name'].'-第'.$instalment_info['times'].'期扣款';//'测试商品-6期扣款';
			// 价格
			$amount = $instalment_info['amount']/100;
			if( $amount<0 ){
				$this->order_service->rollback();
				showmessage('扣款价格必须大于0.01', 'null');
			}
            //扣款要发送的短信
            $data_sms =[
                'mobile'=>$order_info['mobile'],
                'orderNo'=>$order_info['order_no'],
                'realName'=>$order_info['realname'],
                'goodsName'=>$order_info['goods_name'],
                'zuJin'=>$amount,
            ];

            //判断支付方式
			if( $order_info['payment_type_id'] == \zuji\Config::MiniAlipay ){
                $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
                //获取订单的芝麻订单编号
                $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find(['lock'=>true]);
                if(!$zhima_order_info){
                    $this->order_service->rollback();
                    showmessage('该订单没有芝麻订单号！','null',0);
                }
				//芝麻小程序下单渠道
				$Withhold = new \zhima\Withhold();
				$params['out_order_no'] = $order_info['order_no'];
				$params['zm_order_no'] = $zhima_order_info['zm_order_no'];
				$params['out_trans_no'] = $trade_no;
				$params['pay_amount'] = $amount;
				$params['remark'] = $remark;
				$b = $Withhold->withhold( $params );
				\zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求",$params);
				//判断请求发送是否成功
                if($b == 'PAY_SUCCESS'){
                    // 提交事务
                    $this->order_service->commit();
                    \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求回执",$b);
                    showmessage('小程序扣款操作成功','null',1);
                }elseif($b =='PAY_FAILED'){
                    $this->order_service->rollback();
                    $this->instalment_failed($instalment_info['fail_num'],$instalment_id,$instalment_info['term'],$data_sms);
                    showmessage("小程序支付失败", 'null');

				}elseif($b == 'PAY_INPROGRESS'){
                    $this->order_service->commit();
                    showmessage("小程序支付处理中请等待", 'null');
                }else{
                    $this->order_service->rollback();
                    showmessage("小程序支付处理失败", 'null');
                }


			}else {

                // 代扣协议编号
                $agreement_no = $user_info['withholding_no'];
                if (!$agreement_no) {
                    $this->order_service->rollback();
                    showmessage('用户代扣协议编号未找到', 'null');
                }
                // 代扣接口
                $withholding = new \alipay\Withholding();
                // 扣款
                $b = $withholding->createPay($agreement_no, $trade_no, $subject, $amount);
                if (!$b) {
                    $this->order_service->rollback();
                    if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                        $this->instalment_failed($instalment_info['fail_num'], $instalment_id, $instalment_info['term'], $data_sms);
                        showmessage("买家余额不足", 'null');
                    } else {
                        showmessage(get_error(), 'null');
                    }
                }
                \zuji\sms\SendSms::instalment_pay($data_sms);
                // 提交事务
                $this->order_service->commit();
                showmessage('操作成功','null',1);
            }

		}else{
			$where = [
				'id' => $id,
			];
			$instalment_info = $this->instalment_service->get_info($where);
			if( !$instalment_info ){
				showmessage('分期查询失败', 'null');
			}

			$fund_auth_record_type 	= $this->fund_auth_record_table->where(['instalment_id'=>$id])->find();
			$fund_auth_record_type 	= $fund_auth_record_type['type'];

			// 代扣交易码
			$trade_no = \zuji\Business::create_business_no();

			// 交易码，如果存在，则使用之前的交易码，如果不存在，则使用新生成的交易码,并写入到数据库
			if( $instalment_info['trade_no'] ){
				$trade_no = $instalment_info['trade_no'];
			}

			// 检查 trade_no 的唯一性
			$n = $this->instalment_service->get_count(['trade_no'=>$trade_no]);
			if( $n>1 ){
				showmessage('租机交易码重复，请求重试', 'null');
			}

			$allow = $this->instalment_service->allow_withhold($id);

			if(!$allow){
				showmessage('不允许扣款', 'null');
			}

			// 状态（已扣款情况下，禁止重复扣款）
			if( $instalment_info['status'] == Instalment::SUCCESS ){
				showmessage('已扣款', 'null');
			}
            $this->order_service 		= $this->load->service('order2/order');

            $order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']]);
            if(!$order_info){
                showmessage('订单查询失败','null');
            }
            if($order_info['payment_type_id'] == \zuji\Config::MiniAlipay){
                $fund_auth_record_type =5;
            }

			
			$this->load->librarys('View')
				->assign('instalment_id', $id)
                ->assign('order_info',$order_info)
				->assign('fund_auth_record_type', $fund_auth_record_type)
				->display('debit');
		}

	}
	public function instalment_failed($fail_num,$instalment_id,$term,$data_sms){
		if ($fail_num == 0) {
			\zuji\debug\Debug::error(Location::L_SMS, "分期失败次数", $fail_num);
			\zuji\sms\SendSms::instalment_pay_failed($data_sms);
		} elseif ($fail_num > 0 && $term == date("Ym")) {
			\zuji\sms\SendSms::instalment_pay_next_failed($data_sms);
		} elseif ($fail_num > 0 && $term <= date("Ym") - 1) {
		   \zuji\sms\SendSms::instalment_pay_more_failed($data_sms);
		}
		$fail_num = intval($fail_num) + 1;
		//修改失败次数
		$this->instalment_table = $this->load->table('order2/order2_instalment');
		$b = $this->instalment_table->save(['fail_num' => $fail_num, 'id' => $instalment_id]);
		\zuji\debug\Debug::error(Location::L_SMS, "更新失败次数", $b);
		return $b;
    }


	/**
	 * 多项扣款
	 */
	public function multi_createpay(){
		ini_set('max_execution_time', '0');
		$this->instalment_service   	= $this->load->service('order2/instalment');
		$this->order_service 			= $this->load->service('order2/order');
		$this->member_service 			= $this->load->service('member2/member');
		$this->fund_auth_record_table 	= $this->load->table('payment/payment_fund_auth_record');


		$ids = $_GET['ids'];
		$ids = explode(',', $ids );
		$begin_time = $_GET['begin_time'];
		if( count($ids) < 1 ){
			showmessage('参数错误', 'null');
		}

		foreach($ids as $instalment_id){

			if( $instalment_id<1 ){
				\zuji\debug\Debug::error(Location::L_Payment, "参数错误", "");
				continue;
			}
			$remark = "代扣多项扣款";

			// 判断是否允许扣款
			$allow = $this->instalment_service->allow_withhold($instalment_id);
			if(!$allow){
				\zuji\debug\Debug::error(Location::L_Payment, "不允许扣款", "");
				continue;
			}

			// 生成交易码
			$trade_no = \zuji\Business::create_business_no();

			// 开启事务
			$b = $this->order_service->startTrans();
			if( !$b ){
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "事务开启失败", "");
				continue;
			}
			// 查询分期信息
			$where = [
				'id' => $instalment_id,
			];
			$instalment_info = $this->instalment_service->get_info($where,['lock'=>true]);
			if( !$instalment_info ){
				// 提交事务
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "分期查询失败", "");
				continue;
			}

			// 状态在支付中或已支付时，直接返回成功
			if( $instalment_info['status'] == Instalment::SUCCESS && $instalment_info['status'] = Instalment::PAYING ){
				// 提交事务
				$this->order_service->rollback();
			}

			// 只有 未扣款或扣款失败时，才允许扣款操作
			if( $instalment_info['status'] != Instalment::UNPAID && $instalment_info['status'] != Instalment::FAIL ){
				// 提交事务
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "分期状态错误", "");
				continue;
			}

			// 时间限制，
			// 1）要扣款的分期月份，是当前月或历史月的分期
			// 2）当前操作时间必须是16号开始（包含），才允许扣款操作
			$instalment_ym = intval($instalment_info['term']);
			$current_ym = intval(date('Ym'));
			if( $instalment_ym>$current_ym || ( $instalment_ym==$current_ym && intval(date('d'))<15) ){
				// 提交事务
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "未到扣款期限", "");
				continue;
			}

			// 扣款交易码
			if( $instalment_info['trade_no']=='' ){
				// 1)记录租机交易码
				$b = $this->instalment_service->set_trade_no($instalment_id, $trade_no);
				if( $b === false ){
					$this->order_service->rollback();
					\zuji\debug\Debug::error(Location::L_Payment, "租机交易码保存失败", "");
					continue;
				}
				$instalment_info['trade_no'] = $trade_no;
			}
			$trade_no = $instalment_info['trade_no'];

			// 订单
			$order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']]);
			if( !$order_info ){
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "订单查询失败", "");
				continue;
			}

			// 查询用户协议
			$user_info =  $this->member_service->get_info(['id'=>$order_info['user_id']]);
			if( !$user_info ){
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "用户查询失败", "");
				continue;
			}

			// 保存 备注，更新状态
			$data = [
				'remark' => $remark,
				'status' => Instalment::PAYING,// 扣款中
			];
			$result = $this->instalment_service->save($where,$data);
			if(!$result){
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "扣款备注保存失败", "");
				continue;
			}

			// 商品
			$subject = $order_info['goods_name'].'-第'.$instalment_info['times'].'期扣款';//'测试商品-6期扣款';
			// 价格
			$amount = $instalment_info['amount']/100;
			if( $amount<0 ){
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "扣款金额错误", "");
				continue;
			}
			//扣款要发送的短信
			$data_sms =[
				'mobile'=>$order_info['mobile'],
				'orderNo'=>$order_info['order_no'],
				'realName'=>$order_info['realname'],
				'goodsName'=>$order_info['goods_name'],
				'zuJin'=>$amount,
			];


			// 代扣协议编号
			$agreement_no = $user_info['withholding_no'];
			if (!$agreement_no) {
				$this->order_service->rollback();
				\zuji\debug\Debug::error(Location::L_Payment, "用户代扣协议编号未找到", "");
				continue;
			}
			// 代扣接口
			$withholding = new \alipay\Withholding();
			// 扣款
			$b = $withholding->createPay($agreement_no, $trade_no, $subject, $amount);
			if (!$b) {
                // 扣款失败
                $this->instalment_service->save($where,['status'=>Instalment::FAIL]);
				if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
					$this->instalment_failed($instalment_info['fail_num'], $instalment_id, $instalment_info['term'], $data_sms);
				}
				\zuji\debug\Debug::error(Location::L_Payment, get_error(), $data_sms);
			}else{
                // 发送短信
                \zuji\sms\SendSms::instalment_pay($data_sms);
            }
            // 提交事务
            $this->order_service->commit();
		}

		$parms = ['status'=>Instalment::UNPAID,'begin_time'=>$begin_time];
		$url = url('index',$parms);
		showmessage('操作成功', $url, 1);

	}


	/**
	 * 解冻 分期指定金额
	 */
	public function unfreeze_fenqi(){
		$this->instalment_service   = $this->load->service('order2/instalment');
        $this->order_service 		= $this->load->service('order2/order');
		$this->fundauth_service		= $this->load->service('payment/fund_auth');

		$instalment_id = trim($_POST['instalment_id']);
		if($instalment_id<1){
			showmessage('参数错误','null');
		}

		$instalment_info = $this->instalment_service->get_info(['id'=>$instalment_id]);
		if(!$instalment_info){
			showmessage('分期查询失败','null');
		}
		if( $instalment_info['status'] != 2 ){ // 2：已扣款
			showmessage('分期状态错误，解冻租金失败','null');
		}
		// 判断，必须是有租金预授权标识的，才进行解冻
		if( !isset($instalment_info['unfreeze_status']) || $instalment_info['unfreeze_status'] != 0 ){
			showmessage('无需解冻','null');
		}
		
		$order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']]);
		if(!$order_info){
			showmessage('订单查询失败','null');
		}
		
		// 生成交易码
		$trade_no = \zuji\Business::create_business_no();

		// 开启事务
		$b = $this->order_service->startTrans();
		if( !$b ){
			$this->order_service->rollback();
			showmessage('事务开启失败', 'null');
		}
		
		// 解冻 分期对应的金额
		$data = [
			'order_id'=>$instalment_info['order_id'],
			'instalment_id'=>$instalment_id,
			'amount'=>$instalment_info['amount'],
			'trade_no'=>$trade_no,
			'subject'=>$order_info['goods_name'],// 商品名称
		];
		$b = $this->fundauth_service->unfreeze_fenqi($data);
		if(!$b){
			// 事务回滚
			$this->order_service->rollback();
			showmessage('解冻失败：'. get_error(),'null');
//			// 记录失败次数
//			$fail_num =  intval($instalment_info['fail_num'])+1;
//			// 失败超过三次 转支付
//			if($fail_num >= 3){
//				$this->fund_auth_record_table->where([
//					'instalment_id'=>$instalment_id
//				])->save([
//					'type'=>FundAuth::FenqiToPay
//				]);
//			}
//			// 修改失败次数
//			$this->instalment_service->save(['id'=>$instalment_id],['fail'=>$fail_num]);
//			showmessage('解冻错误','null');
		}

		// 事务提交
		$this->order_service->commit();
		showmessage('操作成功','null', 1);
	}

	/**
	 * 分期转支付
	 */
	public function unfreeze_to_pay_fenqi(){
		$this->instalment_service   = $this->load->service('order2/instalment');
        $this->order_service 		= $this->load->service('order2/order');
		$this->fundauth_service		= $this->load->service('payment/fund_auth');

		$instalment_id = trim($_GET['instalment_id']);

		$instalment_info = $this->instalment_service->get_info(['id'=>$instalment_id]);

		if(!$instalment_info){
			showmessage('参数错误','null');
		}
        // 生成交易码
        $trade_no = \zuji\Business::create_business_no();

		// 解冻金额
		$data = [
				'order_id'=>$instalment_info['order_id'],
				'instalment_id'=>$instalment_id,
				'amount'=>$instalment_info['amount'],// 分
				'trade_no'=>$trade_no,
		];

        // 开启事务
        $b = $this->order_service->startTrans();
        if( !$b ){
            $this->order_service->rollback();
            showmessage('事务开启失败', 'null');
        }
		
		$b = $this->fundauth_service->unfreeze_to_pay_fenqi($data);

		if(!$b){
            $this->order_service->rollback();
			showmessage('转支付失败:'.get_error(),'null');
		}
        $this->order_service->commit();
		showmessage('操作成功','null', 1);
	}

	//异步请求 代扣状态
	public function ajax_instalment_status(){
		$this->instalment_service   = $this->load->service('order2/instalment');
		
		$instalment_id = trim($_POST['instalment_id']);
		if($instalment_id < 0){
			showmessage('参数错误', 'null');
		}
		
		// 查询分期信息
		$where = [
			'id' => $instalment_id,
		];
		$instalment_info = $this->instalment_service->get_info($where,['lock'=>true]);
		if( !$instalment_info ){
			showmessage('分期查询失败', 'null');
		}
            showmessage(Instalment::getStatusName($instalment_info['status']), 'null', 1, ['status'=>$instalment_info['status'],'unfreeze_status'=>$instalment_info['unfreeze_status']]);


	}

	//备注
	public function remark_record(){
		$this->instalment_remark_service 	= $this->load->service('payment/instalment_remark');
		$this->order_service = $this->load->service('order2/order');
		$this->instalment_service   = $this->load->service('order2/instalment');

		if(checksubmit('dosubmit')){

			$instalment_id = $_POST['instalment_id'];

			$remark_info  = $this->instalment_service->get_info(['id'=>$instalment_id]);
			if(!$remark_info){
				showmessage('操作失败:参数错误','null',0);
			}
			$data = [
				'instalment_id' =>$instalment_id,
				'contact_status' =>$_POST['contact_status'],
				'remark' =>$_POST['remark'],
				'create_time' =>time(),
			];
			$b = $this->instalment_remark_service->create($data);
			if($b){
				showmessage('操作成功','',1);
			}
		}

		$instalment_id = intval($_GET['id']);

		$this->load->librarys('View')
			->assign('instalment_id',$instalment_id)
			->assign('url','payment/instalment/remark_record')
			->display('remark_record');
	}


	/**
	 *    联系日历
	 */
	public function contact_record(){
		$this->instalment_remark_service 	= $this->load->service('payment/instalment_remark');

		$id = intval($_GET['id']);
		$where['instalment_id']  = $id;

		$limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
		$additional['page'] = intval($_GET['page']);
		$additional['size'] = intval($limit);


		$count  = $this->instalment_remark_service->get_count($where, $additional);

		$list 	= [];
		if($count > 0){
			$list = $this->instalment_remark_service->get_list($where, $additional);
		}

		foreach ($list as &$item){
			$item['create_time'] 	= $item['create_time']>0 ? date("Y-m-d H:i:s",$item['create_time']): '--';
			$item['contact_status'] = $item['contact_status'] == 1 ? "是" : '否';
		}

		$lists = array(
			'th' => array(
				'create_time' => array('length' => 30,'title' => '时间'),
				'contact_status' => array('length' => 30,'title' => '是否联系成功'),
				'remark' => array('length' => 40,'title' => '备注内容'),
			),
			'lists' => $list,
			'pages' => $this->admin_pages($count, $additional['size']),
		);


		$this->load->librarys('View')
			->assign('lists', $lists)
			->display('contact_record');


	}
	/**
	 * 代扣分期订单导出【参照后台页面：支付->代扣分期（控制器：payment->instalment->index）】
	 */
    public function instalment_order_export(){
        // 不限制超时时间
        set_time_limit(0);
        // 内存很大
        ini_set('memory_limit', 200*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'代扣分期单统计'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');
        $header_data = array('用户姓名','联系电话','订单号','分期','第几期','应付金额','状态','解冻状态','租机交易号','第三方交易号','扣款成功时间',);
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);
		
		//-+--------------------------------------------------------------------
		// | 查找列表数据
		//-+--------------------------------------------------------------------
    	$this->instalment_service   = $this->load->service('order2/instalment');
        $where = [];
        // 查询条件
        if(isset($_GET['status'])){
            $where['status'] = intval($_GET['status']);
            if( $where['status'] == 0){
                unset($where['status']);
            }
        }
        if(isset($_GET['begin_time'])){
            $where['begin_time'] = $_GET['begin_time'];
            if( !$where['begin_time'] ){
                unset($where['begin_time']);
            }
        }
        // 查询
        $instalment_count = $this->instalment_service->get_count($where);
        $instalment_list = [];//初始化代扣分期单列表数组
		
        $additional['page'] = 1;
        $additional['size'] = 100;
        while( $instalment_count>0 ){
            // 订单分期付款查询100条查询一次
            $instalment_list = $this->instalment_service->get_list($where,$additional);

            foreach ($instalment_list as &$item){
				$item['allow_koukuan'] = 1;
                // 格式化状态
                $item['order_no'] 			= get_order_no($item['order_id']);
				$item['status_show'] 		= Instalment::getStatusName($item['status']);
                $item['amount'] 			= Order::priceFormat($item['amount']/100);
				$item['payment_time_show'] 	= $item['payment_time']>0 ? date("Y-m-d H:i:s",$item['payment_time']): '--';
				$item['update_time_show'] 	= $item['update_time']>0 ? date("Y-m-d H:i:s",$item['update_time']): '--';
				$item['allow_koukuan'] 		= $this->instalment_service->allow_withhold($item['id']);
				// 是否解冻
				$item['jiedong_btn'] = false;

				if( isset($item['unfreeze_status']) && $item['unfreeze_status']==0  ){
					$item['jiedong_btn'] = true;
				}
				$item['unfreeze_status'] 	= $this->unfreeze_status[$item['unfreeze_status']];
				
                $body_data = [
                    "\t" . $item['realname'],//用户姓名
                    "\t" . $item['mobile'],//联系电话
                    "\t" . $item['order_no'],//订单号
                    "\t" . $item['term'],//分期
                    "\t" . $item['times'],//第几期
                    "\t" . $item['amount'],//应付金额
                    "\t" . $item['status_show'],//状态
                    "\t" . $item['unfreeze_status'],//解冻状态
                    "\t" . $item['trade_no'],//租机交易号
                    "\t" . $item['out_trade_no'],//第三方交易号
                    "\t" . $item['payment_time_show'],//扣款成功时间
                ];
                $this->export_csv_wirter_row($handle, $body_data);
                unset($body_data);
			}
            $additional['page'] = $additional['page'] + 1;
            $instalment_count = $instalment_count - $additional['size'];
        }

    }
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
            $row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
}