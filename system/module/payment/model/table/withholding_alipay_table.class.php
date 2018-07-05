<?php
/**
 * 支付宝代扣协议 数据层
 */
class withholding_alipay_table extends table {

    protected $fields = [
        'id',
        'user_id',
        'partner_id',
        'alipay_user_id',
        'agreement_no',
        'status',
        'sign_time',
        'valid_time',
        'invalid_time',
        'unsign_time',
    ];

    /**
     * 支付宝代扣协议表
     * @param type $data
     */
    public function create($data){
        return $this->add($data);
    }

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $this->where($where)->count('id');
    }

    /**
     * 查询列表
     * @return int  符合查询条件
     */
    public function get_list($where,$options) {
        $withhold_list = $this->field($this->fields)->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();
        if(!is_array($withhold_list)){
            return [];
        }
        
        return $withhold_list;
    }

    /**
     * 根据协议码，查询详情
     * @param string $where
	 * []
     */
    public function get_info( $where,$additional=[] ){
        $options = [];
        if( isset($additional['lock']) ){
            $options['lock'] = $additional['lock'];
        }
        return $this->where($where)->limit(1)->find($options);
    }
}