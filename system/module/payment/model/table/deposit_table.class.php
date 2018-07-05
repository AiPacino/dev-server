<?php
/**
 * 押金配置表
 */
class deposit_table extends table {

    protected $fields = [
        'id',
        'deposit_name',
        'payment_style_id',
        'is_open',
        'create_time',
        'update_time',
        'admin_id',

    ];

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $this->where($where)->count('id');
    }
    
    public function get_list($where,$options) {
        $list = $this->field($this->fields)->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();
        if(!is_array($list)){
            return [];
        }
        return $list;
    }

    public function  get_info($id, $additional=[])
    {
        return $this->field($this->fields)->where(['id' => $id])->find($additional);
    }

}