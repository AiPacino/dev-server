<?php
class zmfk_service extends service {

    public function _initialize() {
        /* 实例化数据层 */
//        $this->order2_table = $this->load->table('order2/order2');
    }

    /**
     * 拼接一条数据
     */
    public function set_info($row){
        $row_info = [
            'records'=>[
                'biz_date'=>date('Y-m-d'),
                'user_credentials_type'=>0,
                'user_credentials_no'=>$row['cert_no'],//身份证号
                'user_name'=>$row['realname'],
                'order_no'=>$row['order_no'],
                'phone_no'=>$row['mobile'],
                'create_amt'=>'',//评估用户最高的可透支额度,最长11个字符;
                'order_start_date'=>'',//订单的开始日期 date('Y-m-d',$row['create_time'])
                'order_end_date'=>date('Y-m-d',$row['end_time']),//订单服务结束时间
                'remind_status'=>'',//提醒状态
                'order_status'=>'',//当前业务状态
                'bill_no'=>'',//账单号
                '',//账单月份
            ]
        ];
        return $row_info;
    }

    /**
     * 生成json文件
     */
    public function create_json($json_str){
        $date = date('Y-m-d');
        file_put_contents('zmxy'.$date.'.json',$json_str);
    }
}
