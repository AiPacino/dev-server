<?php
/**
 * 资金授权 服务层
 */
use zuji\Config;
use zuji\payment\FundAuth;

class createpay_notify_service extends service {

    public function _initialize() {

        $this->createpay_notify_table = $this->load->table('payment/payment_createpay_notify');
    }

    /**
     * 创建 资金授权 记录
     * @return mixed	false：失败；int：成功，返回主键ID
     */
    public function create( $data ){
        $data = filter_array($data, [
            'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
            'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
            'notify_id' => 'required',	    // 通知校验ID
            'sign_type' => 'required',
            'sign' => 'required',
            'notify_action_type' => 'required',
            'out_trade_no' => 'required',   // 租机交易号
            'trade_no' => 'required',	    // 支付宝交易流水号
            'trade_status' => 'required',
            'subject' => 'required',
            'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
            'gmt_payment' => 'required',
            'gmt_close' => 'required',
            'seller_email' => 'required',
            'seller_id' => 'required',
            'buyer_id' => 'required',
            'buyer_email' => 'required',
            'total_fee' => 'required',
            'price' => 'required',
            'quantity' => 'required',
            'refund_fee' => 'required',
        ]);


        // 保存通知记录
        $notify_id = $this->createpay_notify_table->create( $data );
        if( !$notify_id ){
            set_error('保存资金预授权通知失败');
            return false;
        }

        return true;
    }

}
