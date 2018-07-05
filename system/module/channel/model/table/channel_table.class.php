<?php
/**
 * 商品渠道数据层
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/18 0018
 * Time: 上午 11:50
 */

class channel_table extends table {

    protected $_validate = array(
        array('name','require','{channel/channel_name_require}',table::MUST_VALIDATE,'regex',table:: MODEL_BOTH),
        array('contacts','require','{channel/channel_contacts_require}',table::MUST_VALIDATE,'regex',table:: MODEL_BOTH),
        array('phone','require','{channel/channel_phone_require}',table::MUST_VALIDATE,'regex',table:: MODEL_BOTH),
        array('phone','mobile','{channel/mobile_format}',table::VALUE_VALIDATE,'regex',table:: MODEL_BOTH),
        array('status','number','{goods/state_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('sort','number','{goods/sort_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('alone_goods','number','{channel/alone_goods_number}',table::VALUE_VALIDATE,'regex',table:: MODEL_INSERT),
    );

    protected $_field = 'id, name, contacts, phone, alone_goods, desc, sort, status';


    /**
     * 获取商品渠道列表
     * @param $where
     * @param $options
     * @return array
     */
	public function get_list($where, $options){
        $lists = $this->field($this->_field)
            ->where($where)
            ->page($options['page'])
            ->limit($options['size'])
            ->order($options['orderby'])
            ->select();
        if(!is_array($lists)){
            return [];
        }
        return $lists;
    }

}