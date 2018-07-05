<?php
namespace zhima;

use zuji\debug\Debug;
use zuji\debug\Location;
/**
 * 支付宝芝麻小程序(代扣) 发送请求
 *
 * @author zhangjinhui
 */
class Withhold {
    //取消
    private $CANCEL = 'CANCEL';
    //完结
    private $FINISH = 'FINISH';
    //分期扣款
    private $INSTALLMENT = 'INSTALLMENT';

    private $error = '';
    /*
     * 错误信息
     */
    public function getError( ){
        return $this->error;
    }
    /*
     * 订单关闭（代扣）发送请求
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'out_trans_no'=>'',//资金交易号
     *      'pay_amount'=>'',//支付金额
     *      'remark'=>'',//订单操作说明
     * ]
     */
    public function withhold( $params ){
        $params['order_operate_type'] = $this->INSTALLMENT;
        $ZhimaWithholding = new \alipay\ZhimaWithholding(\zuji\Config::ZHIMA_MINI_APP_ID);
        $b = $ZhimaWithholding->withholdingCancelClose($params);
        if($b == false){
            $this->error = $ZhimaWithholding->getError();
            return false;
        }
        $result = $ZhimaWithholding->getResult();
        Debug::error(Location::L_Order,'发送扣款请求',['request'=>$params,'response'=>$result]);
        //扣款加锁
        \zuji\OrderLocker::lock($params['out_order_no'],\zuji\OrderLocker::Withholding);
        //返回字符串
        return $result['zhima_merchant_order_credit_pay_response']['pay_status'];
    }

    /*
     * 订单关闭 发送请求
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'remark'=>'',//订单操作说明
     * ]
     */
    public function OrderClose( $params ){
        $ZhimaWithholding = new \alipay\ZhimaWithholding(\zuji\Config::ZHIMA_MINI_APP_ID);
        $params['order_operate_type'] = $this->FINISH;
        $params['out_trans_no'] = $params['out_trans_no']?$params['out_trans_no']:'132456';
        $params['pay_amount'] = $params['pay_amount']?$params['pay_amount']:'0';
        $b = $ZhimaWithholding->withholdingCancelClose($params);
        if($b === false){
            $this->error = $ZhimaWithholding->getError();
            return false;
        }
        //将订单加锁（关闭）
        \zuji\OrderLocker::lock($params['out_order_no'],\zuji\OrderLocker::Closeing);
        $result = $ZhimaWithholding->getResult();
        //Debug::error(Location::L_Order,'发送关闭订单请求',['request'=>$params,'response'=>$result]);
        return $result;
    }


    /*
     * 订单取消
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     * ]
     */
    public function OrderCancel( $params ){
        $params['order_operate_type'] = $this->CANCEL;
        $ZhimaWithholding = new \alipay\ZhimaWithholding(\zuji\Config::ZHIMA_MINI_APP_ID);
        $b = $ZhimaWithholding->withholdingCancelClose($params);
        if($b === false){
            $this->error = $ZhimaWithholding->getError();
            return false;
        }
        //将订单加锁
        \zuji\OrderLocker::lock($params['out_order_no'],\zuji\OrderLocker::Canceling);
        $result = $ZhimaWithholding->getResult();
        Debug::error(Location::L_Order,'发送取消订单请求',['request'=>$params,'response'=>$result]);
        return $result;
    }



}