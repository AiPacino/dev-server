<?php
/**
 *		商品数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */

class goods_spu_table extends table {
    protected $result = array();
    protected $_validate = array(
        array('name','require','{goods/goods_name_require}',table::MUST_VALIDATE),
        array('catid','number','{goods/classify_id_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('brand_id',0,'{goods/brand_id_require}',table::EXISTS_VALIDATE, 'notequal', table:: MODEL_BOTH),
        array('channel_id','number','{goods/channel_id_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('warn_number','number','{goods/stock_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('status','number','{goods/state_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('sort','number','{goods/sort_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
    );
    protected $_auto = array(
    );

    //分渠道存放的商品列表
    public $hash_key = 'goods:channel:spu:';

    protected $redis = null;
    public function __construct() {
        parent::__construct();
        $this->redis = \zuji\cache\Redis::getInstans();
    }

    /**
     * 用于维护
     * @param $data
     * @param $options
     */
    protected function _after_insert($data,$options) {
        parent::_after_insert($data,$options);
        $set_key = $this->hash_key . $data['channel_id'];
        $this->redis->del($set_key);
    }

    protected function _after_update($data,$options) {
        parent::_after_update($data,$options);
        $info = $this->modelId($data['id']);
        $set_key = $this->hash_key . $info['channel_id'];
        $this->redis->del($set_key);

        //编辑商品时，删除广告位的缓存
        $ads_model = model('ads/adv');
        $ads_list = $ads_model->where(['content_id' => $data['id'], 'filag' => 'spu'])->select();
        if(!empty($ads_list)){
            foreach ($ads_list as $item){
                $ads_key = 'zuji_pos_appid_' . $item['position_id'] . '_' . $item['channel_id'];
                $this->redis->del($ads_key);
            }
        }

    }

    protected function _after_delete($data,$options) {
        parent::_after_delete($data,$options);
        foreach ($data['id'][1] as $id){
            $info = $this->modelId($id);
            $set_key = $this->hash_key . $info['channel_id'];
            $this->redis->del($set_key);
        }
    }


    /**
     * 获取一条记录
     * @param $id
     * @return mixed|null
     */
    public function modelId($id){
        $id = intval($id);
        if($id){
            $key = $this->trueTableName.':id:'.$id;
            $data = $this->redis->get($key);
            if($data !== false){
                return unserialize($data);
            }

            return $this->find($id);
        }
        else{
            return NULL;
        }
    }

//默认字段
    private $field = 'id,name,sn,subtitle,style,catid,brand_id,keyword,description,content,imgs,thumb,min_price,max_price,
    status,specs,sku_total,give_point,warn_number,sort,spec_id,type_id,weight,volume,delivery_template_id,yiwaixian,yiwaixian_cost,start_rents,start_month,min_month,max_month,channel_id,peijian,machine_id,contract_id';
    //默认排序
    private $order = 'id desc';

    //查询单条商品数据
    public function get_info($id, $field='', $order=''){
        return $this->where(array('id'=>$id))->order($order)->field($field)->find();
    }

    //查询多条商品数据
    public function get_list_info(){
        return $this->limit(10)->select();
    }
    public function status($status){
        if(is_null($status)){
            return $this;
        }
        $this->where(array('status'=>$status));
        return $this;
    }

    public function category($catid){
        if(!$catid){
            return $this;
        }
        $this->where(array('catid'=>array('IN',$catid)));
        return $this;
    }

    public function brand($brand_id){
        if(!$brand_id){
            return $this;
        }
       $this->where(array('brand_id'=>$brand_id));
        return $this;
    }

    public function channel($channel_id){
        if(!$channel_id){
            return $this;
        }
        $this->where(array('_id'=>$channel_id));
        return $this;
    }

    public function channel2($channel_id){
        if(!$channel_id){
            return $this;
        }
        $this->where(array('channel_id'=>$channel_id));
        return $this;
    }

    public function keyword($keyword){
        if(!$keyword){
            return $this;
        }
        $this->where(array('name'=>array('LIKE','%'.$keyword.'%')));
        return $this;
    }




    protected function _after_find(&$result,$options) {
        return $result = $this->_output($result);
    }

    /**
     * 获取拓展品牌
     * @param  array $spu SPU数组
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_extra_brand($spu) {
        $spu_id = (int) $spu['id'];
        $brand_id = (int) $spu['brand_id'];
        if($spu_id > 0 && $brand_id > 0) {
            return $this->load->table('goods/brand')->find($spu['brand_id']);
        }
        return false;
    }

    /**
     * 获取拓展品牌
     * @param  array $spu SPU数组
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_extra_channel($spu) {
        $spu_id = (int) $spu['id'];
        $channel_id = (int) $spu['channel_id'];
        if($spu_id > 0 && $channel_id > 0) {
            return $this->load->table('goods/channel')->find($channel_id);
        }
        return false;
    }

    /**
     * 获取拓展品牌
     * @param  array $spu SPU数组
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_extra_machine($spu) {
        $spu_id = (int) $spu['id'];
        $machine_id = (int) $spu['machine_id'];
        if($spu_id > 0 && $machine_id > 0) {
            return $this->load->table('goods/goods_machine_model')->find($machine_id);
        }
        return false;
    }

    /**
     * 获取拓展分类
     * @param  array $spu SPU数组
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_extra_category($spu) {
        $catid = (int) $spu['catid'];
        if($catid > 0) {
            return $this->load->service('goods/goods_category')->get_category_by_id($spu['catid'],false);
        }
        return false;
    }

    /**
     * 获取拓展SKU列表
     * @param  array $spu SPU数组
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_extra_sku($spu) {
        $spu_id = (int) $spu['id'];
        if($spu_id > 0) {
            return $this->load->service('goods/goods_sku')->get_sku($spu_id);
        }
        return false;
    }


    public function get_extra_type($spu) {
        $spu_id = (int) $spu['id'];
        if($spu_id > 0) {
            return $this->load->service('goods/type')->get_type_by_goods_id($spu_id);
        }
        return false;
    }


    protected function _output($result) {
        /* 默认主图 */
        $result['imgs'] = json_decode($result['imgs'],true);
        if($result['specs']) {
            $specs = json_decode($result['specs'], true);
            foreach ($specs as $id => $spec) {
                $specs[$id]['value'] = explode(",", $spec['value']);
                $specs[$id]['img'] = explode(",", $spec['img']);
                // $specs[$id]['md5'] = md5($spec['name'].':'.$spec['value']);
            }
            $result['specs'] = $specs;
        }

        return $result;
    }
}