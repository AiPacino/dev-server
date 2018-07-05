<?php

/**
 * 		维修服务数据层
 *
 */
class weixiu_service_table extends table {
    //默认字段
    protected $fields =[
        'id',
        'service_name',
        'pid',
        'reason_name',
        'create_time',
    ];
    protected $pk ="id";
    //默认排序
    private static $_order = 'id desc';

    /**
     * 保存维修服务
     * @params array	服务信息
     * [
     *	   'service_name' => '',	  //【必须】string;维修服务名
     *     'pid' => '',              //【必须】int;父类id
     *     'reason_name' =>         //【必须】string;维修原因
     * ]
     */
    public function create_table($data){
        $data['create_time'] = time();
        $result = $this->add($data);
        if($result)
            return $result;
        else
            return false;
    }
    /**
     * 通过条件 获取一条记录
     * @return array
     */
    public function get_info($where) {
        return $this->field($this->fields)->where($where)->find();
    }
    /**
     * 通过条件 获取记录
     * @return array
     */
    public function get_list($where){
        return $this->field($this->fields)->where($where)->select();
    }
    /**
     * 修改单条记录
     * @return mixed array
     */
    public function update_table($data){
        $result = $this->save($data);
        return $result;
    }
}