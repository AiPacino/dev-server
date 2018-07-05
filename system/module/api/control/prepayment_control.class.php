<?php

use zuji\order\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\Config;
use zuji\order\RefundStatus;
use zuji\order\delivery\Delivery;
use zuji\Time;
use zuji\order\Service;
use zuji\email\EmailConfig;
use zuji\cache\Redis;

hd_core::load_class('api', 'api');
/**
 * 提前还款控制器
 * @access public
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */
class prepayment_control extends api_control {

    protected $trade_status = [
        'WAIT_BUYER_PAY'    =>1,    //交易创建，等待买家付款
        'TRADE_CLOSED'      =>2,    //未付款交易超时关闭，或支付完成后全额退款
        'TRADE_SUCCESS'     =>3,    //交易支付成功
        'TRADE_FINISHED'    =>4,    //交易结束，不可退款
    ];

    public function _initialize() {
        parent::_initialize();
    }

    // 提前还款-退款成功回调
    public function refund_notify(){
       //  $_POST = [
       //    'gmt_create' => '2018-03-22 11:55:17',
       //    'charset' => 'UTF-8',
       //    'seller_email' => 'shentiyang@huishoubao.com.cn',
       //    'subject' => 'iPhone8 Plus',
       //    'sign' => 'QUFIl8SK30AvzIUm1VmZZBC+QJPmbRp1Mde5AWA5HTHAeYHbzAVR5iShQFPx70qiHsEeqEItcFrCGHZC1xj7NKPL46fRW6XQxk3zWjRvvtnyAG90GBQrz+qs2A3edtGHjZ7nnLSaX1EgEbLHEeHXW1ebzsoc9p/4HcE/KlrCd2WJBdx2D1N6onU0eOtwy/x/agBQf20PdSWYEC3MJg1cO//J0STEygeqc1pYxtm0x8nBlMNK+iLAduRLG9jGqPmfg7WtId/ZQUMuLudxszjL2gNdu4Tf9ag6qk5t4xnP0EZ/u6p3yOXvYtTUxQzt78NalW6cpzSYfXkpW/fWdIvEwA==',
       //    'buyer_id' => '2088702999441562',
       //    'invoice_amount' => '1.00',
       //    'notify_id' => 'd002ec637d688e8b58c09129d518471kbp',
       //    'fund_bill_list' => '[{"amount":"1.00","fundChannel":"ALIPAYACCOUNT"}]',
       //    'notify_type' => 'trade_status_sync',
       //    'trade_status' => 'TRADE_SUCCESS',
       //    'receipt_amount' => '1.00',
       //    'buyer_pay_amount' => '1.00',
       //    'app_id' => '2017101309291418',
       //    'sign_type' => 'RSA2',
       //    'seller_id' => '2088821442906884',
       //    'gmt_payment' => '2018-03-22 11:55:17',
       //    'notify_time' => '2018-03-22 12:09:28',
       //    'version' => '1.0',
       //    'out_trade_no' => 'Dev20180322000206',
       //    'total_amount' => '1.00',
       //    'trade_no' => '2018032221001004560547355770',
       //    'auth_app_id' => '2017101309291418',
       //    'buyer_logon_id' => '136****5804',
       //    'point_amount' => '0.00',
       // ];

        //file_put_contents('./prepayment_refund_url-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

        

        if( !is_array($_POST) || count($_POST)==0 ){
            echo '提前还款-退款失败';
            exit;
        }

        $notify_info = filter_array($_POST, [
            'notify_type' => 'required',        // 通知类型；固定值：trade_status_sync
            'notify_id' => 'required',	        // 通知校验ID
            'app_id' => 'required',             // 支付宝分配给开发者的应用Id
            'charset' => 'required',            // 编码格式
            'version' => 'required',            // 接口版本
            'sign_type' => 'required',	        // 签名类型
            'sign' => 'required',               // 签名
            'trade_no' => 'required',           // 支付宝交易号
            'out_trade_no' => 'required',       // 商户订单号
            'buyer_id' => 'required',	        // 买家支付宝用户号
            'buyer_logon_id' => 'required',	    // 买家支付宝账号
            'seller_id' => 'required',	        // 卖家支付宝用户号
            'seller_email' => 'required',	    // 卖家支付宝账号
            'trade_status' => 'required',		// 交易状态
            'total_amount' => 'required',		// 订单金额
            'receipt_amount' => 'required',		// 实收金额
            'invoice_amount' => 'required',     // 开票金额
            'buyer_pay_amount' => 'required',	//【可选】付款金额
            'point_amount' => 'required',	    //【可选】集分宝金额
            'subject' => 'required',	        //【可选】订单标题
            'gmt_create' => 'required',		    // 交易创建时间
            'gmt_payment' => 'required',		// 交易付款时间
            'fund_bill_list' => 'required',	    //【可选】支付金额信息
        ]);

        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
        $_no = $notify_info['out_trade_no'];
        $notify_info['out_trade_no'] = $notify_info['trade_no'];
        $notify_info['trade_no'] = $_no;

        // 通知发送时间
        $notify_info['notify_time'] = $notify_info['gmt_create'];
        //状态值转换
        $notify_info['trade_status'] = $this->trade_status[$notify_info['trade_status']];
        // 开启事务
        $this->order_service = $this->load->service('order2/order');
        //开启事务
        $this->order_service->startTrans();


        //-+--------------------------------------------------------------------
        // | 保存 退款异步通知
        //-+--------------------------------------------------------------------
        // 异步通知记录表
        $instalment_prepayment_refund_notify_table = $this->load->table('payment/instalment_prepayment_refund_notify');
        $_POST['create_time'] = time();
        // 执行sql
        $refund_notify_id = $instalment_prepayment_refund_notify_table->create( $notify_info );

        if( !$refund_notify_id ){
            $this->order_service->rollback();
            Debug::error(Location::L_Payment,'[提前还款-退款-异步通知]提前还款-退款错误', $_POST);
            echo '提前还款-退款错误';
            exit;
        }

        // 交易成功修改状态
        if($notify_info['trade_status'] == $this->trade_status['TRADE_SUCCESS']){
            //修改 提前还款单状态
            $data = [
                'prepayment_status'=>2,
                'refund_time'=>time(),
            ];

            $prepayment_service   = $this->load->service('payment/prepayment');
            $b = $prepayment_service->save(['trade_no'=>$_no],$data);
            if( !$b ){
                $this->order_service->rollback();
                \zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth,'提前还款-退款',get_error());
                echo get_error();
                exit ;
            }
        }
        $this->order_service->commit();

        echo 'success';
        exit;

    }

}
