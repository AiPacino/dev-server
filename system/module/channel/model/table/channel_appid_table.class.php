<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19 0019
 * Time: 上午 11:40
 */

class channel_appid_table extends table
{

    protected $_validate = array(
        array('name','require','{channel/appid_name_require}',table::MUST_VALIDATE),
        array(['channel_id','type'],'check_number','{channel/channel_type_require}',table::MUST_VALIDATE, 'callback'),
        array('status','number','{goods/state_require}',table::EXISTS_VALIDATE,'regex',table:: MODEL_BOTH),
        array('platform_public_key','0,1024','{channel/public_key_length}',table::VALUE_VALIDATE,'length',table:: MODEL_BOTH),
        array('client_public_key','0,1024','{channel/client_public_key_length}',table::VALUE_VALIDATE,'length',table:: MODEL_BOTH),
        array(['platform_public_key', 'platform_private_key', 'client_public_key', 'type'],'check_type','{channel/channel_type_api_check}',table::MUST_VALIDATE, 'callback',table:: MODEL_BOTH),
        array(['address', 'mobile', 'type'],'check_store_type','{channel/channel_type_store_check}',table::MUST_VALIDATE, 'callback',table:: MODEL_BOTH),
        array('mobile','mobile','{channel/mobile_format}',table::MUST_VALIDATE, 'regex',table:: MODEL_BOTH),
    );

    public $hash_key = 'channel:appid';

    public $_field = 'id, name, type, address, mobile, is_upload_idcard, platform_public_key, platform_private_key, client_public_key, channel_id, status';

    public function check_number($arg){
        if($arg['channel_id']<=0 || $arg['type'] <= 0){
            return false;
        }
        return true;
    }

    public function check_type($arg){
        $appid_service = $this->load->service('channel/channel_appid');
        if($arg['type'] == $appid_service::TYPE_API){
            if(empty($arg['platform_public_key']) || empty($arg['platform_private_key']) || empty($arg['client_public_key'])){
                return false;
            }
        }
        return true;
    }

    public function check_store_type($arg){
        $appid_service = $this->load->service('channel/channel_appid');
        if ($arg['type'] == $appid_service::TYPE_STORE){
            if(empty($arg['address']) || empty($arg['mobile'])){
                return false;
            }
        }
        return true;
    }

    /**
     * 获取列表
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

    /**
     * 获取一条数据
     * @param $id
     * @param $options
     * @return mixed
     */
    public function get_info($id, $options=[]){
        $result = $this->where(['id' => $id])->find($options);
        return $result;
    }

    /**
     * 获取拓展渠道
     * @param  array $spu SPU数组
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_extra_channel($info) {
        $id = (int) $info['id'];
        $channel_id = (int) $info['channel_id'];
        if($id > 0 && $channel_id > 0) {
            return $this->load->table('goods/channel')->find($info['channel_id']);
        }
        return false;
    }

    protected function _after_insert($data,$options) {
        $Redis = \zuji\cache\Redis::getInstans();
        $Redis->hset($this->hash_key, $data['id'], json_encode($data) );
    }

    protected function _after_update($data,$options) {
        $info = $this->find($data['id']);
        $Redis = \zuji\cache\Redis::getInstans();
        $Redis->hset($this->hash_key, $data['id'], json_encode($info) );
    }

    protected function _after_delete($data,$options) {
        $Redis = \zuji\cache\Redis::getInstans();
        foreach ($data['id'][1] as $id){
            $Redis->hdel($this->hash_key, $id);
        }
    }


}