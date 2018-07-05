<?php
/**商品规格模型层*/

class spec_table extends table {

    //默认字段
    private static $_field = 'id,name,value,img,status,sort';
    //默认排序
    private static $_order = 'id desc';

    //获取所有规格数据
    public function get_list($where="",$fields='', $order='',$limit=0){
        $fields = $fields?$fields:self::$_field;
        $order = $order?$order:self::$_order;
        return $this->where($where)->field($fields)->limit($limit)->select();
    }
}