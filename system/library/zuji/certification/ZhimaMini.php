<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/3/29 0029-上午 11:02
 * @copyright (c) 2017, Huishoubao
 */

namespace zuji\certification;

class ZhimaMini
{

    /**
     * 获取芝麻订单确认结果
     * @param $appid
     * @param $order_no //芝麻信用订单号
     * @param $transaction_id //一笔请求的唯一标志
     */
    public function getOrderConfirmResult($appid, $out_order_no, $order_no, $transaction_id){
        ////请求芝麻订单确认接口，保存结果
        $params = [
            'order_no' => $order_no,
            'transaction_id' => $transaction_id
        ];
        $zhima = new \alipay\ZhimaMini($appid);
        $data = $zhima->orderConfirm($params);
        if($data === false){
            return false;
        }

        // 人脸识别标志
        $data['zm_face'] = $data['zm_face']=='Y'?1:0;
        $data['zm_risk'] = $data['zm_risk']=='Y'?1:0;
        $data['order_no'] = $order_no;
        $data['out_order_no'] = $out_order_no;
        $data['trade_no'] = $transaction_id;
        // 记录订单查询返回信息
        $load = \hd_load::getInstance();
        $zhima_certification_table = $load->service('order2/zhima_certification');
        $id = $zhima_certification_table->update($data);
        if($id === false){
            set_error('保存订单确认结果失败');
            return false;
        }

        return $data;
    }
}