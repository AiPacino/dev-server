<?php
/**
 * 支付宝代扣协议 服务层
 */
use zuji\Config;
use zuji\payment\FundAuth;

class withhold_service extends service {

    public function _initialize() {
        $this->withhold_notify_table = $this->load->table('payment/withholding_notify_alipay');
    }

    
    private function _parse_where( $param=[] ){
         // 参数过滤
        $param = filter_array($param, [    
            'id' => 'required',
            'external_user_id' => 'required',
            'notify_id' => 'required',
            'notify_time' => 'required',
            'notify_type' => 'required',
            'sign_type' => 'required',
            'sign'   => 'required',
            'partner_id'     => 'required',
            'alipay_user_id'     => 'required',
            'agreement_no'   => 'required',
            'product_code'   => 'required',
            'scene'      => 'required',
            'status'     => 'required',
            'sign_time' => 'required',
            'sign_modify_time'   => 'required',
            'valid_time'     => 'required',
            'invalid_time' => 'required',
            'unsign_time' => 'required'
        ]);

        // user_id 支持多个
        if( isset($param['external_user_id']) ){
           $where['external_user_id'] = $param['external_user_id'];
        }
        if( isset($param['status']) ){
            $where['status'] = intval($param['status']);
        }
        if( isset($param['agreement_no']) ){
            $where['agreement_no'] = $param['agreement_no'];
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
        $where = $this->_parse_where($where);
        if( $where===false ){
            return 0;
        }
        return $this->withhold_notify_table->get_count($where);
    }

    /**
     * 获取符合条件的集合
     * @param   array   $where  
     * @return int 查询总数
     */
    public function get_list($where=[],$additional=[]){
    
        // 参数过滤
        $where = $this->_parse_where($where);
        if( $where===false ){
            return [];
        }
      
        if( !isset($additional['orderby']) ){   // 排序默认值
            $additional['orderby']='time_DESC';
        }
        
        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'sign_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'sign_time ASC';
            }
        }
        // 列表查询
        return $this->withhold_notify_table->get_list($where,$additional);
    }

}
