<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19 0019
 * Time: 下午 3:32
 */

class channel_address_table extends table
{
    protected $field = ['id', 'name', 'channel_id', 'type', 'mobile', 'address', 'province_id', 'city_id', 'country_id', 'zipcode', 'remark'];

    protected $_validate = array(
        //array(field,rule,message,condition,type,when,params)
        array('name','require','{channel/channel_address_name_require}',table::MUST_VALIDATE),
        array(['channel_id','type'],'check_number','{channel/channel_type_require}',table::MUST_VALIDATE, 'callback'),
    );


    /**
     * 获取列表
     * @param $where
     * @param $options
     * @return array
     */
    public function get_list($where, $options){
        $lists = $this->field($this->field)
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

    public function check_number($arg){
        if($arg['channel_id']<=0 || $arg['type'] <= 0){
            return false;
        }
        return true;
    }
}