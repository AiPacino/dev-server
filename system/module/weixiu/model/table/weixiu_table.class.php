<?php

/**
 * 		维修服务数据层
 *
 */
class weixiu_table extends table
{
    //默认字段
    protected $fields = [
        'id',
        'order_id',
        'order_no',
        'user_id',
        'service_name',
        'reason_name',
        'weixiu_info',
        'pictures',
        'user_remark',
        'guest_remark',
        'user_wuliu_no',
        'weixin_wuliu_no',
        'repair_time',
        'status',
        'create_time',
        'update_time',
    ];
    protected $pk = "id";

    /**
     * 添加维修订单
     * @params array $data    维修单信息
     * [
     *     'order_no'   => '',        //【必须】string;订单编号
     *       'weixiu_content' => '',      //【必须】string;维修内容
     *     'weixiu_cause' => '',     //【必须】string;维修原因
     *     'pictures' => '',        //【必须】string;图片
     *     'user_remark' => '',     //【必须】string;用户备注
     * ]
     */
    public function create_table($data)
    {
        $result = $this->add($data);
        if ($result)
            return $result;
        else
            return false;
    }

    /**
     * 通过条件 获取维修记录单条
     * @return array
     */
    public function get_info($where, $fields)
    {
        $field = isset($fields) ? $fields : $this->fields;
        return $this->field($field)->where($where)->find();
    }

    /**
     * 通过条件 获取维修记录多条
     * @return array
     */
    public function get_info_All($where)
    {
        return $this->field($this->fields)->where($where)->select();
    }

    /**
     * 通过条件 获取记录
     * @return array
     */
    public function get_list($where, $options)
    {
        // 字段替换
        $where = replace_field($where, [
            'id' => 'id',
            'order_no' => 'order_no',
            'status' => 'status',
        ]);
        $where['_logic'] = "AND";
        $order_list = $this->field($this->fields)->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();
        if (!is_array($order_list)) {
            return [];
        }
        return $order_list;
    }

    /**
     * 修改单条记录
     * @return mixed array
     */
    public function update_table($data)
    {
        $result = $this->save($data);
        return $result;
    }
    /**
     *
     * 根据条件查询总条数

     */
    public function get_count($where=[]){
        $result = $this->where($where)->count();
        if($result === false){
            return 0;
        }
        return $result;
    }
}