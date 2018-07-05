<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2017/12/28 0028-下午 5:14
 * @copyright (c) 2017, Huishoubao
 */

class channel_member_table extends table
{

    protected $_validate = array(
        array('username','','{admin/user_name_exist}',0,'unique',1),
        array('username', 'require', '{admin/username_not_exist}', table::MUST_VALIDATE),
        array('password', 'require', '{admin/password_not_standard}', table::VALUE_VALIDATE,'', table::MODEL_BOTH),
    );

    protected function _after_find(&$result, $options) {
        $service = model('channel_member', 'service');
        if($result['type'] == $service::TYPE_STORE){
            $table = 'channel_appid';
        }elseif ($result['type'] == $service::TYPE_CHANNEL){
            $table = 'channel';
        }else{
            $table = '';
        }
        $result['relation_name'] = model($table)->where(array('id'=>$result['relation_id']))->getField('name');
        $member_mobile_model = model('channel_member_mobile')->where(['user_id' => $result['id']])->select();
        if($member_mobile_model){
            $mobile_arr = array_column($member_mobile_model, 'mobile');
            $result['mobile'] = implode(',', $mobile_arr);
        }
        return $result;
    }
    protected function _after_select(&$result, $options) {
        foreach ($result as &$record) {
            $this->_after_find($record, $options);
        }
        return $result;
    }

    protected function _after_delete($data,$options) {
        foreach ($data['id'][1] as $id){
            $result = model('channel_member_mobile')->where(['user_id' => $id])->delete();
            if($result === false){
                return false;
            }
        }
        return true;
    }
}