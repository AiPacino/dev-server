<?php
/**
 * 分期提前还款表
 */
class instalment_prepayment_table extends table {

    protected $fields = [
        'prepayment_id',
        'order_id',
        'order_no',
        'instalment_id',
        'user_id',
        'mobile',
        'term',
        'create_time',
        'update_time',
        'prepayment_time',
        'refund_time',
        'trade_no',
        'out_trade_no',
        'prepayment_status',
        'payment_channel_id',
        'payment_amount',
        'payment_account',
    ];

    protected $pk = 'prepayment_id';

    /**
     * 过滤
     * @return array
     */
    public function filter($where) {
        $table = $this->getTableName();
        // 开始时间（可选）

        if( isset($where['begin_time'])){

            $new_where[$table.'.term'] = intval(date("Ym",strtotime($where['begin_time'])));
        }

        if( isset($where['prepayment_status']) ){
            $new_where[$table.'.prepayment_status'] = $where['prepayment_status'];
        }

        if( isset($where['order_id']) ){
            $new_where[$table.'.order_id'] = $where['order_id'];
        }

        if( isset($where['order_no']) ){
            $new_where[$table.'.order_no'] = $where['order_no'];
        }

        if( isset($where['prepayment_id']) ){
            $new_where[$table.'.prepayment_id'] = $where['prepayment_id'];
        }

        if( isset($where['prepayment_status']) ){
            $new_where[$table.'.prepayment_status'] = $where['prepayment_status'];
        }

        if( isset($where['prepayment_time']) ){
            $new_where[$table.'.prepayment_time'] = ['LT', $where['prepayment_time']];
        }

        if( isset($where['instalment_id']) ){
            $new_where[$table.'.instalment_id'] = $where['instalment_id'];
        }

        if( isset($where['mobile']) ){
            $new_where[$table.'.mobile'] = $where['mobile'];
        }

        return $new_where;
    }

    /**
     * 创建
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

        $where = $this->filter($where);

        $table = $this->getTableName();
        $num = $this->where($where)->join(config("DB_PREFIX")."member AS M ON ".$table.".user_id=M.id")->count($table.'.prepayment_id');
        return $num;
    }

    /**
     * 查询列表
     * @return int  符合查询条件
     */
    public function get_list($where,$options) {

        $where = $this->filter($where);

        $table = $this->getTableName();
        $fields = $table.".*,M.realname";

        $lists = $this->field($fields)->join(config("DB_PREFIX")."member AS M ON ".$table.".user_id=M.id")->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();

        if(!is_array($lists)){
            return [];
        }

        return $lists;
    }


    public function  get_info($where)
    {
        return $this->field($this->fields)->where($where)->find();
    }

}