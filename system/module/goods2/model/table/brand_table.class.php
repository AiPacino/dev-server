<?php
/**
 *		品牌数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */

class brand_table extends table {

    //默认字段
    private static $_field = 'id,name,logo,descript,url,status,isrecommend,sort';
    //默认排序
    private static $_order = 'id desc';
    //查询多条品牌数据
    public function get_list($where='',$fields='', $order='',$limit=0){
        $fields = $fields?$fields:self::$_field;
        $order = $order?$order:self::$_order;
        return $this->where($where)->field($fields)->limit($limit)->order($order)->select();
    }
}