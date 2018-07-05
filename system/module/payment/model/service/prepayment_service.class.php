<?php
/**
 * 支付宝代扣协议 服务层
 */
use zuji\Config;
use zuji\payment\Instalment;
use zuji\coupon\CouponStatus;
use zuji\payment\Withhold;


class prepayment_service extends service {

    public function _initialize() {
        $this->prepayment_table = $this->load->table('payment/instalment_prepayment');
    }


    private function _parse_where( $param=[] ){
        // 参数过滤
        $param = filter_array($param, [
            'order_id' => 'required',
            'order_no' => 'required',
            'instalment_id' => 'required',
            'user_id' => 'required',
            'mobile' => 'required',
            'prepayment_status' => 'required',
            'begin_time' => 'required',
        ]);

        if( isset($param['order_id']) ){
            $where['order_id'] = $param['order_id'];
        }

        if( isset($param['order_no']) ){
            $where['order_no'] = $param['order_no'];
        }

        if( isset($param['instalment_id']) ){
            $where['instalment_id'] = $param['instalment_id'];
        }

        if( isset($param['prepayment_status']) ){
            $where['prepayment_status'] = $param['prepayment_status'];
        }

        if( isset($param['mobile']) ){
            $where['mobile'] = $param['mobile'];
        }

        // 开始时间（可选）
        if( isset($param['begin_time'])){
            $where['term'] = intval(date("Ym",strtotime($param['begin_time'])));
        }

        return $where;
    }




    /**
     * 获取符合条件的记录数
     * @param   array	$where
     * @return int 查询总数
     */
    public function get_count($where=[]){
        // 参数过滤
        if( $where===false ) {
            return 0;
        }
        return $this->prepayment_table->get_count($where);
    }

    /**
     * 获取符合条件的列表
     * @param   array	$where
     * @return array
     */
    public function get_list($where=[],$additional=[]){

        // 参数过滤
        //$where = $this->_parse_where($where);
//        if( $where===false ){
//            return [];
//        }

        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required|is_int',
            'orderby' => 'required',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = Config::Page_Size;
        }
        $additional['size'] = min( $additional['size'], Config::Page_Size );

        if( !isset($additional['orderby']) ){   // 排序默认值
            $additional['orderby']='time_ASC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }

        // 列表查询
        return $this->prepayment_table->get_list($where,$additional);
    }

    /**
     *
     * @param   array	$where
     * @return int 查询详情
     */
    public function get_info($where=[]){
        return $this->prepayment_table->get_info($where);
    }

    /**
     * @param   array	$where
     * @return int 查询总数
     */
    public function save($where, $data = []){
        return $this->prepayment_table->where($where)->save($data);
    }
}
