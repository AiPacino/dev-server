<?php
namespace oms\operation;

use zuji\order\OrderStatus;
use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;

/**
 * 订单支付 操作
 * @author 
 *
 */
class PayOperation implements OrderOperation
{

    private $Order = null;
    private $trade_channel = '';
    private $data = '';
    private $trade_id = '';
    
    public function __construct(\oms\Order $Order, string $trade_channel, array $data, int $trade_id ){
		$this->Order = $Order;
		$this->data = $data;
		$this->trade_channel = $trade_channel;
		$this->trade_id = $trade_id;
		
		
		$time = time();
		$debug = [
			'msg' => '正常',
			'POST' => $this->data,
		];
		$app_id = $this->data['app_id'];
		try {
			// 签名校验
			$WapPay = new \alipay\WapPay( $app_id );
			$b = $WapPay->verify($this->data);
			if( !$b ){
				Debug::error(Location::L_Payment, '支付宝支付异步通知--校验错误', $debug);
				return false;
			}
		} catch (\Exception $exc) {
			$debug['msg'] = $exc->getMessage();
			Debug::error(Location::L_Payment, '支付宝支付异步通知--校验异常', $debug);
			return false;
		}
		
		// 校验
		$failed_list = [];
		$notify_info = filter_array($this->data, [
			'app_id' => 'required',
			'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
			'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
			'notify_id' => 'required',	    // 通知校验ID
			'notify_action_type' => 'required',
			'sign_type' => 'required',
			'sign' => 'required',
			'subject' => 'required',
			'trade_no' => 'required',		// 支付宝交易码
			'out_trade_no' => 'required',	// 原支付请求的商户订单号
			'trade_status' => 'required',   // 交易目前所处的状态
			'total_amount' => 'required',   // 本次交易支付的订单金额，单位为人民币（元）
			'price' => 'required',			// 价格
			'quantity' => 'required',
			'receipt_amount' => 'required', // 商家在交易中实际收到的款项，单位为元
			'buyer_pay_amount' => 'required',// 用户在交易中支付的金额
			'refund_fee' => 'required',	// 退款通知中，返回总退款金额，单位为元，支持两位小数
			'gmt_create' => 'required',	    // 该笔交易创建的时间。格式为yyyy-MM-dd HH:mm:ss
			'gmt_payment' => 'required',    // 该笔交易的买家付款时间。格式为yyyy-MM-dd HH:mm:ss
			'gmt_close' => 'required',		    // 该笔交易结束时间。格式为yyyy-MM-dd HH:mm:ss
			'buyer_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
			'buyer_id' => 'required',	    //【可选】付款方支付宝用户号
			'seller_email' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
			'seller_id' => 'required',	    //【可选】付款方支付宝用户号
		]);
		// 商家在交易中实际收到的款项，单位为元
		set_default_value($notify_info['receipt_amount'], 0);
		// 退款金额
		set_default_value($notify_info['refund_fee'], 0);
		// 该笔交易结束时间
		set_default_value($notify_info['gmt_close'], 0);

		// 支付渠道
		$notify_info['trade_channel'] = $this->trade_channel;
		// * 注意： 
		// * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
		// * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
		$_no = $notify_info['out_trade_no'];
		$notify_info['out_trade_no'] = $notify_info['trade_no'];
		$notify_info['trade_no'] = $_no;

		// 字段替换
		$notify_info = replace_field($notify_info, [
			'buyer_logon_id' => 'buyer_email',
		]);
		
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
		$this->trade_status = $trade_status;
		
		$this->notify_info = $notify_info;
    }
    
	/**
	 * 支付操作
	 * <p>1) 记录支付异步通知</p>
	 * <p>2）更新交易状态</p>
	 * <p>3）更新支付单状态</p>
	 * <p>3）更新订单状态</p>
	 * <p>注意：需要兼容线上订单</p>
	 * <p></p>
	 * @return boolean
	 */
    public function update(){
		
		//-+--------------------------------------------------------------------
		// | 系统类加载器
		//-+--------------------------------------------------------------------
		$load = \hd_load::getInstance();
		
		// 异步通知记录表
		$payment_trade_notify_table = $load->table('payment/payment_trade_notify');
		// 交易表
		$payment_trade_table = $load->table('payment/payment_trade');
		// 支付单表
		$order2_payment_table = $load->table('order2/order2_payment');
		// 订单表
		$order2_table = $load->table('order2/order2');
		
		
		$time = time();
		//-+--------------------------------------------------------------------
		// | 保存 支付异步通知
		//-+--------------------------------------------------------------------
		$data = $this->notify_info;
		$data['create_time'] = $time;
		// 交易表ID
		$data['trade_id'] = $this->trade_id;
		
		// 执行sql
		$trade_notify_id = $payment_trade_notify_table->create( $data );
		if( !$trade_notify_id ){
			set_error('支付异步通知保存失败');
			return false;
		}
		
		// 只同步 交易成功
		if( $this->trade_status==4 ){
			// 交易记录状态更新
			$trade_data = [
				'trade_status' => $this->trade_status,		// 交易状态
				'payment_amount' => $this->notify_info['total_amount'], // 单位：元
				'refund_amount' => $this->notify_info['refund_fee'],	// 单位：元
				'seller_id' => $this->notify_info['seller_id'], 
				'seller_email' => $this->notify_info['seller_email'], 
				'buyer_id' => $this->notify_info['buyer_id'], 
				'buyer_email' => $this->notify_info['buyer_email'], 
				'out_trade_no' => $this->notify_info['out_trade_no'], 
				'trade_channel' => \zuji\payment\Payment::Channel_ALIPAY,// 支付宝渠道
				'subject' => $this->notify_info['subject'],// 商品名称  
				'payment_time' => $time,  
				'update_time' => $time, 
			];
			$b = $payment_trade_table->where(['trade_no'=>$data['trade_no']])->limit(1)->save( $trade_data );
			if( !$b ){
				set_error('交易记录更新失败');
				$payment_trade_notify_table->rollback();
				return false;
			}
			// 支付金额(单位：分)
			$payment_amount = $this->notify_info['total_amount']*100;
			// 支付单状态更新
			$b = $order2_payment_table->where(['payment_id'=> $this->Order->get_payment_id()])->limit(1)->save([
				'payment_amount' => $payment_amount,// 支付金额(单位：分)
				'payment_status'=> \zuji\order\PaymentStatus::PaymentSuccessful, // 支付成功
				'trade_no' => $this->notify_info['trade_no'],
				'payment_time' => $time,// 支付时间
				'update_time' => $time,// 更新时间
			]);
			if( !$b ){
				set_error('支付单更新失败');
				return false;
			}

			// 订单单状态更新
			$b = $order2_table->where(['order_id'=> $this->Order->get_order_id()])->limit(1)->save([
				'payment_amount' => $payment_amount,// 支付金额(单位：分)
                'payment_status'=> \zuji\order\PaymentStatus::PaymentSuccessful, // 支付成功
				'status' => State::PaymentSuccess,	// 支付成功
				'payment_time' => $time,// 支付时间
				'update_time' => $time,// 更新时间
			]);
			if( !$b ){
				set_error('订单更新失败');
				return false;
			}
		}
       return true;
    }
}