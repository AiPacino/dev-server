<?php
/**
 *		商品数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */

class goods_spu_table extends model {

    //默认字段
    private static $_field = 'id,name,sn,subtitle,style,catid,brand_id,keyword,description,content,imgs,thumb,min_price,max_price,status,specs,sku_total,give_point,warn_number,sort,spec_id,type_id,weight,volume,delivery_template_id,yiwaixian,start_rents,start_month,min_month,max_month,min_zuqi_type,max_zuqi_type,channel_id,peijian,machine_id,contract_id';
    //默认排序
    private static $_order = 'id desc';

    //分渠道存放的商品列表
    public $hash_key = 'goods:channel:spu:';

    /**
     * 重写基类模型方法，以实现能找到对应的table
     * @return bool|string
     */
    public function getModelName() {
        if(empty($this->name))
            $this->name =   substr(get_class($this),0,-6);
        return $this->name;
    }

    /**
     * 用于维护
     * @param $data
     * @param $options
     */
    protected function _after_insert($data,$options) {
        parent::_after_insert($data,$options);
        $set_key = $this->hash_key . $data['channel'];
        $this->redis->del($set_key);
    }

    protected function _after_update($data,$options) {
        parent::_after_update($data,$options);
        $info = $this->modelId($data['id']);
        $set_key = $this->hash_key . $info['channel'];
        $this->redis->del($set_key);
    }

    protected function _after_delete($data,$options) {
        parent::_after_delete($data,$options);
        foreach ($data['id'][1] as $id){
            $info = $this->modelId($id);
            $set_key = $this->hash_key . $info['channel'];
            $this->redis->del($set_key);
        }
    }

    //库存+1
    public function add_number($spu_id){
        $data['id'] =$spu_id;
        $data['sku_total'] = ['exp','sku_total+1'];
        return $this->save($data);
    }
    //库存-1
    public function minus_number($spu_id){
        $data['id'] =$spu_id;
        $data['sku_total'] = ['exp','sku_total-1'];
        return $this->save($data);
    }
    //查询单条商品数据
    public function get_info($id=0,$field=''){
        $fields = $field?$field:self::$_field;
        return $this->where(array('id'=>$id))->field($fields)->find();
    }
    //查询多条商品数据
    public function get_list($where='',$fields='', $order='',$limit=0){
        $fields = $fields?$fields:self::$_field;
        $order = $order?$order:self::$_order;
        return $this->where($where)->field($fields)->limit($limit)->order($order)->select();
    }

    /**
     * 获取对应渠道的列表数据
     * @param $channel_id
     * @return array
     */
    public function get_hash_list_by_channel_id($channel_id){
        $hash_key = $this->hash_key.$channel_id;
        $list = $this->redis->hGet($hash_key, $channel_id);
        $result = [];
        if($list){
            $result = json_decode($list, true);
        }
        return $result;
    }

    /**
     * 设置
     * @param $info
     * @return bool|int
     */
    public function set_hash_by_spu($channel_id, array $list){
        $hash_key = $this->hash_key.$channel_id;
        return $this->redis->hset($hash_key, $channel_id, json_encode($list) );
    }
}