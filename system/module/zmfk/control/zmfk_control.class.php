<?php
/**
 * 芝麻信用 数据反馈
 */
class zmfk_control extends control{


    public function _initialize() {
        parent::_initialize();
        /* 服务层 */
//        $this->spu_service = $this->load->service('goods/goods_spu');
    }

    public function index()
    {
        //验证IP
//        if ($IP=xxxxxxxxx){
//        }

        $fankui = new alipay\ZhimaFankui('300001198');
        $data = [
            'biz_date'=>'2017-12-26',//生成本条数据的实际日期
            'user_credentials_type'=>'0',//证件类型 0身份证
            'user_credentials_no'=>'130633198912010013',
            'user_name'=>'王金霖',
            'order_no'=>'20171219000144',
            'phone_no'=>'18600598865',
            'create_amt'=>'100',//最高透支额度 按照免押的金额提供
            'order_start_date'=>'2017-12-26',//订单开始日期
            'order_end_date'=>'2018-12-26',//订单(或服务)结束日期
            'remind_status'=>'0',
            'order_status'=>'3',//业务状态
            'bill_no'=>'',
            'bill_installment'=>'',
            'bill_desc'=>'',
            'bill_type'=>'',
            'bill_amt'=>'',
            'bill_last_date'=>'',
            'bill_status'=>'',
            'bill_payoff_date'=>'',
            'bill_type_ovd_amt'=>'',
            'bill_type_ovd_date'=>'',
            'memo'=>'',
        ];
        $info = [
            'BizExtParams'=>'{"order_no":"20171219000144"}',
            'Data'=>json_encode($data),
        ];
        $fankui->ZhimaDataSingleFeedback($info);
    }

//    public function index()
//    {
//        //验证IP
////        if ($IP=xxxxxxxxx){
////        }
//        $fankui = new alipay\ZhimaFankui('300001198');
//        $data = [
//            'phone_no'=>'18600598865',
//            'create_amt'=>'1000',
//            'remind_status'=>'0',
//            'order_status'=>'0',
//            'bill_no'=>'20171219000144',
//            'bill_installment'=>'201712',
//            'bill_desc'=>'iPhone 8',
//            'bill_type'=>'200',
//            'bill_amt'=>'100',
//            'bill_last_date'=>'2018-12-26',
//            'bill_status'=>'0',
//            'bill_payoff_date'=>'2018-12-26',
//            'bill_type_ovd_amt'=>'100',
//            'bill_type_ovd_date'=>'2018-12-27',
//
//            'user_credentials_type'=>'0',
//            'user_credentials_no'=>'130633198912010013',
//            'user_name'=>'王金霖',
//            'order_no'=>'20171219000144',
//            'order_status_date'=>'0',
//            'user_credentials_type'=>'0',
//            'user_credentials_type'=>'0',
//            'user_credentials_type'=>'0',
//            'user_credentials_type'=>'0',
//        ];
//        $info = [
//            'BizExtParams'=>'{"bill_no":"20171219000144"}',
//            'Data'=>json_encode($data),
//        ];
//        $fankui->ZhimaDataSingleFeedback($info);
//    }

}