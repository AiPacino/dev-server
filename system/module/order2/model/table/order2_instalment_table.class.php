<?php
/**
 * 订单分期付款
 * @outhor maxiaoyu
 */
class order2_instalment_table extends table {
    protected $fields = [
        'id',
        'agreement_no',
        'order_id',
        'term',
        'times',
        'amount',
        'status',
        'payment_time',
        'trade_no',
        'out_trade_no',
		'update_time',
        'remark',
        'fail_num',
        'discount_amount',
        'unfreeze_status'
    ];

    /**
     * 订单分期付款
     * @param type $data
     */
    public function create($data){
        return $this->add($data);
    }

    /**
     * 过滤
     * @return array
     */
    public function filter($where) {
        $table = $this->getTableName();
        // 开始时间（可选）

        if( isset($where['term'])){
            $new_where[$table.'.term'] = $where['term'];
        }

        if( isset($where['status']) ){
            $new_where[$table.'.status'] = $where['status'];
        }

        if( isset($where['order_id']) ){
            $new_where[$table.'.order_id'] = $where['order_id'];
        }

        if( isset($where['trade_no']) ){
            $new_where[$table.'trade_no'] = $where['trade_no'];
        }
        
        if( isset($where['mobile']) ){
            $new_where['M.mobile'] = $where['mobile'];
        }

        return $new_where;
    }

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {

        $where = $this->filter($where);

        $table = $this->getTableName();
        $num = $this->where($where)->join(config("DB_PREFIX")."order2 AS O ON ".$table.".order_id=O.order_id")->join(config("DB_PREFIX")."member AS M ON O.user_id=M.id")->count($table.'.id');
        return $num;
    }

    /**
     * 查询列表
     * @return int  符合查询条件
     */
    public function get_list($where,$options) {
        $where = $this->filter($where);

        $table = $this->getTableName();
        $fields = $table.".*,M.realname,M.mobile";


        $instalment_list = $this->field($fields)->join(config("DB_PREFIX")."order2 AS O ON ".$table.".order_id=O.order_id")->join(config("DB_PREFIX")."member AS M ON O.user_id=M.id")->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();

        if(!is_array($instalment_list)){
            return [];
        }

        return $instalment_list;
    }

    /**
     * 查询列表
     * @return int  符合查询条件
     */
    public function get_order_list($where) {
        $instalment_list = $this->field($this->fields)->where($where)->order("times ASC")->select();

        if(!is_array($instalment_list)){
            return [];
        }

        return $instalment_list;
    }

    public function get_info($where,$lock=false) {
        return $this->field($this->fields)->where($where)->find(['lock'=>$lock]);
    }


}