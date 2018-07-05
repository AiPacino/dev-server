<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/3/27 0027-上午 11:08
 * @copyright (c) 2017, Huishoubao
 */

namespace alipay;

require_once __DIR__ . '/aop/request/ZhimaMerchantOrderConfirmRequest.php';
require_once __DIR__ . '/aop/request/ZhimaMerchantOrderCreditPayRequest.php';

class ZhimaMini extends BaseApi
{

    public function __construct($appid)
    {
        parent::__construct($appid);
    }


    /**
     * 信用套餐产品订单确认接口
     * @param $params
     * @return bool|mixed
     */
    public function orderConfirm( $params ){

        $params = filter_array($params, [
            'order_no' => 'required',		    // 【必须】芝麻信用订单号
            'transaction_id' => 'required'		// 【必须】一笔请求的唯一标志
        ]);

        if( count($params)!=2 ){
            set_error('业务参数错误');
            return false;
        }

        //请求业务参数
        $biz_content['order_no'] = $params['order_no'];
        $biz_content['transaction_id'] = $params['transaction_id'];

        $request = new \ZhimaMerchantOrderConfirmRequest();
        $request->setBizContent(json_encode($biz_content));
        $result = $this->execute ( $request);
        $debug_data = [
            'request' => $biz_content,
            'response' => json_decode(json_encode($result),true),
        ];
        //\zuji\debug\Debug::error(\zuji\debug\Location::L_AlipayMini, '芝麻订单确认接口', $debug_data);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $result = json_decode(json_encode($result),true);
            return $result['zhima_merchant_order_confirm_response'];
        } else {
            $msg = $result->zhima_merchant_order_confirm_response->sub_msg;
            set_error($msg);
            return false;
        }
    }

    /**
     * 芝麻信用支付
     * @param $params
     * @return bool|mixed
     */
    public function orderCreditPay( $params ){

        $params = filter_array($params, [
            'order_operate_type' => 'required', // 【必须】订单操作类型，包括取消(CANCEL)、完结(FINISH)
            'out_order_no' => 'required',		// 【必须】外部订单号【租机订单号】
            'zm_order_no' => 'required',        // 【必须】芝麻订单号
            'out_trans_no' => 'required',       // 【可选】外部资金订单号
            'pay_amount' => 'required',         // 【可选】支付总金额，单位为元
            'remark' => 'required'              // 【可选】订单操作说明
        ]);

        set_default_value($params['out_trans_no'], '');
        set_default_value($params['pay_amount'], '');
        set_default_value($params['remark'], '');
        if( count($params)!=6 ){
            set_error('业务参数错误');
            return false;
        }

        //请求业务参数
        $biz_content['order_operate_type'] = $params['order_operate_type'];
        $biz_content['out_order_no'] = $params['out_order_no'];
        $biz_content['zm_order_no'] = $params['zm_order_no'];
        $biz_content['out_trans_no'] = $params['out_trans_no'];
        $biz_content['pay_amount'] = $params['pay_amount'];
        $biz_content['remark'] = $params['remark'];

        $request = new \ZhimaMerchantOrderCreditPayRequest();
        $request->setBizContent(json_encode($biz_content));
        $response = $this->execute ( $request);

        $result = json_decode(json_encode($response),true);
        //var_dump( $result );
        $debug_data = [
            'request' => $biz_content,
            'response' => $result,
        ];
        if(!isset($result['zhima_merchant_order_credit_pay_response'])){
            set_error('芝麻信用支付接口请求失败');
            \zuji\debug\Debug::error(\zuji\debug\Location::L_AlipayMini, '芝麻信用支付接口，返回值错误', $debug_data);
            return false;
        }
        if( $result['zhima_merchant_order_credit_pay_response']['code']!=10000 ){
            $msg = $result['zhima_merchant_order_credit_pay_response']['sub_msg'];
            set_error($msg);
            \zuji\debug\Debug::error(\zuji\debug\Location::L_AlipayMini, '芝麻信用支付接口：'.$msg, $debug_data);
            return false;
        }
        return $result;
    }
}