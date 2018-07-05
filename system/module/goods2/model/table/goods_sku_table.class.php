<?php

/**
 * 		子商品数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class goods_sku_table extends table {

    //默认字段
    private static $_field =[
        'sku_id',   //子商品id
        'spu_id',   //商品id,取值goods的goods_id
        'sku_name',   //子商品名称
        'subtitle', //副标题
        'style',    //
        'sn',       //商品货号
        'barcode',  //商品条形码
        'spec',     //商品所属规格类型id，取值spec的id
        'imgs',     //商品图册
        'thumb',   //缩略图
        'status',   //状态
        'status_ext',//商品标签状态
        'number',   //商品库存数量
        'market_price', //市场价格（非每月价格）
        'sort', //排序
        'shop_price',//月租金
        'buyout_price',//买断价格
        'keyword',
        'description',
        'content',      //内容
        'show_in_lists',    //是否在列表显示
        'warn_number',
        'prom_type',    //促销类型
        'prom_id',  //促销类型ID
        'up_time',  //上架时间
        'update_time',  //更新时间
        'edition',  //版本号
        'weight',   //体重
        'volume',//体积
        'yajin',    //押金
        'chengse',  //成色
        'zuqi',  //租期（月：3，6，12；天：7，15,30）
        'zuqi_type',  //租期类型（1：天；2：月）
        ];
    //默认排序
    private static $_order = 'sku_id desc';
    //库存+1
    public function add_number($sku_id){
        $data['sku_id'] =$sku_id;
        $data['number'] = ['exp','number+1'];
        return $this->save($data);
    }
    //库存-1
    public function minus_number($sku_id){
        $data['sku_id'] =$sku_id;
         $data['number'] = ['exp','number-1'];
        return $this->save($data);
    }
    
    //查询单条商品数据
    public function get_info($id,$fields=''){
        $fields = $fields?$fields:self::$_field;
        return $this->where(array('sku_id'=>$id))->field($fields)->find();
    }

    //查询多条商品数据
    public function get_list($where='',$fields='', $order='',$limit=0){
        $fields = $fields?$fields:self::$_field;
        $order = $order?$order:self::$_order;
        return $this->where($where)->field($fields)->limit($limit)->select();
    }

}
