<?php
/**
 * 信用管理表
 */
class credit_table extends table {

    protected $fields = [
	'id',
	'credit_name',
	'min_credit_score',
	'max_credit_score',
	'is_open',
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
    public function getFields($field = '',$sqlmap = array()) {
        return $this->where($sqlmap)->getfield($field);
    }

}