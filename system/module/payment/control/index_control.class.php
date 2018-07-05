<?php
class index_control extends control {
	public function _initialize() {
		parent::_initialize();
		$this->service = $this->load->service('payment/payment');
		$this->service_payment = $this->load->service('payment/payment');
	}

	public function create_pay(){
		
		$agreement_no = '20180118456078336770';
		$trade_no = 'T2018011800016';//'T'.\zuji\Business::create_business_no();
		$subject = '测试商品-6期扣款';
		$amount = '0.01';
		
		$withholding = new \alipay\Withholding();
		
		$b = $withholding->createPay($agreement_no, $trade_no, $subject, $amount);
		
		if( !$b ){
			var_dump(get_error());
			exit;
		}
		var_dump( $b );exit;
		
	}
	
}