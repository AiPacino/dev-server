<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/21 0021
 * Time: 下午 4:14
 */

class goods_machine_model_table extends table
{

    protected $_validate = array(
        array('name','require','{channel/channel_name_require}',table::MUST_VALIDATE),
        array('status','number','{goods/state_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
    );

    protected $_field = 'id, name, brand_id, status';


    /**
     * 获取商品列表
     * @param $where
     * @param $options
     * @return array
     */
    public function get_list($where, $options){
        $lists = $this->field($this->_field)
            ->where($where)
            ->page($options['page'])
            ->limit($options['limit'])
            ->order($options['orderby'])
            ->select();
        if(!is_array($lists)){
            return [];
        }
        return $lists;
    }

    protected function _after_find(&$result, $options) {

        $brand = $this->load->table('goods/brand')->detail($result['brand_id'], 'name')->output();
        $result['brand_name'] = $brand['name'];
        return $result;
    }
    protected function _after_select(&$result, $options) {
        foreach ($result as &$record) {
            $this->_after_find($record, $options);
        }
        return $result;
    }

    protected function _after_update($data,$options) {
        $spu_model = model('goods_spu');
        $spu_model->where(['machine_id' => $data['id']])->save(['brand_id' => $data['brand_id']]);
    }
}