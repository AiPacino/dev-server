<?php
/**
 * 支付宝代扣协议 服务层
 */
use zuji\Config;
use zuji\payment\FundAuth;
use zuji\payment\Withhold;
use zuji\payment\Withhold_notify;

class withhold_service extends service {

    public function _initialize() {
        $this->withhold_table = $this->load->table('payment/withholding_alipay');
        $this->fund_auth_notify_table = $this->load->table('payment/payment_fund_auth_notify');
        $this->order_service    = $this->load->service('order2/order');
    }

    
    private function _parse_where( $param=[] ){

        // 参数过滤
        $param = filter_array($param, [
            'id' => 'required',
            'user_id' => 'required',
            'partner_id' => 'required',
            'alipay_user_id' => 'required',
            'agreement_no' => 'required',
            'status' => 'required',
            'sign_time' => 'required',
            'invalid_time' => 'required',
            'unsign_time' => 'required',
        ]);
//
        // 结束时间（可选），默认为为当前时间
        if( !isset($param['unsign_time']) ){
            $param['unsign_time'] = date("Y-m-d");//time();
        }
        // 开始时间（可选）
        if( isset($param['sign_time'])){
            if( strtotime($param['sign_time']) > strtotime($param['unsign_time']) ){
                return false;
            }
            $where['sign_time'] = ['between',[$param['sign_time'], $param['unsign_time']]];
        }

        // user_id 支持多个
        if( isset($param['user_id']) ){
            if(is_string($param['user_id']) ){
                $param['user_id'] = explode(',',$param['user_id']);
                if( !is_array($param['user_id']) ){
                    return false;
                }
            }elseif(is_int($param['user_id'])){
                $where['user_id'] = $param['user_id'];
            }
            if(count($param['user_id'])==1 ){
                $where['user_id'] = $param['user_id'][0];
            }
            if(count($param['user_id'])>1 ){
                $where['user_id'] = ['IN',$param['user_id']];
            }
        }
        if( isset($param['status']) ){
            $where['status'] = intval($param['status']);
        }
        // agreement_no 请求协议号查询，使用前缀模糊查询
        if( isset($param['agreement_no']) ){
            $where['agreement_no'] = ['LIKE', $param['agreement_no'] . '%'];
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
        return $this->withhold_table->get_count($where);
    }

     public function get_list($where=[],$additional=[]){
    
        // 参数过滤
        $where = $this->_parse_where($where);
        if( $where===false ){
            return [];
        }

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
        return $this->withhold_table->get_list($where,$additional);
    }

    // 详情
    public function get_info($where){

        return $this->withhold_table->get_info($where);
    }

    // 修改
    public function save($where, $data){
        return $this->withhold_table->where($where)->save($data);
    }


  
}
