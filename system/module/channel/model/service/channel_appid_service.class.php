<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19 0019
 * Time: 上午 11:39
 */

class channel_appid_service extends service
{
    public $hash_key = 'channel:appid';

    const TYPE_H5 = 1;
    const TYPE_API = 2;
    const TYPE_STORE = 3;   //线下门店
    const TYPE_ALI_ZHIMA = 4;   //支付宝小程序

    public $enum_type = [
        self::TYPE_H5 => 'H5',
        self::TYPE_API => 'openapi',
        self::TYPE_STORE => '线下门店',
        self::TYPE_ALI_ZHIMA => '支付宝小程序'
    ];

    public function _initialize() {
        $this->db = $this->load->table('channel/channel_appid');
    }

    /**
     * [get_lists 获取列表]
     * @return [type] [description]
     */
    public function get_list($where = [], $options = []){
        $result = $this->db->get_list($where, $options);
        return $result;
    }

    /**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap = array()){
        $result = $this->db->where($sqlmap)->count('id');
        if($result === false){
            $this->error = $this->db->getError();
            return false;
        }
        return $result;
    }

    /**
     * 添加
     * @param array $params
     * @return bool
     */
    public function add_appid($params = array()){
        $result = $this->db->update($params);
        if($result === FALSE){
            $this->error = $this->db->getError();
            return FALSE;
        }


        return $result;
    }

    /**
     * 编辑
     * @param $params
     * @return bool
     */
    public function edit_appid($params){
        runhook('before_edit_appid', $params);
        if((int)$params['id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->db->update($params);
        if($result === FALSE){
            $this->error = $this->db->getError();
            return FALSE;
        }
        return $result;
    }

    /**
     * [delete 删除渠道，可批量删除]
     * @param  [fixed] $params [类型id]
     * @return [boolean]         [返回删除结果]
     */
    public function delete($params){
        $params = (array) $params;
        if(empty($params)){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $sqlmap = array();
        $sqlmap['id'] = array('IN',$params);
        $result = $this->db->where($sqlmap)->delete();
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * 获取一条数据
     * @param $id
     * @param $options
     * @return mixed
     */
    public function get_info($id, $extra='channel'){
        $id = (int) $id;
        $result = array();
        if($id < 1) {
            $this->error = '参数错误';
            return false;
        }
        $appid = $this->db->find($id);
        if(empty($appid)) {
            $this->error = '不存在';
            return false;
        }
        $result['appid'] = $appid;

        /* 返回值 */
        if($extra) {
            $extra = explode(",", $extra);
            foreach ($extra AS $val) {
                $method = "get_extra_".$val;
                if(method_exists($this->db,$method)) {
                    $result['_'.$val] = $this->db->$method($appid);
                }
            }
        }
        return $result;
    }

    /**
     * [change_info 更改渠道信息]
     * @param  [array] $params [id和排序数组]
     * @return [boolean]     [返回更改结果]
     */
    public function change_info($params){
        if((int)$params['id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->db->where(array('id'=>$params['id']))->save($params);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * [search_brand 关键字查找渠道]
     * @param  [type] $keyword [description]
     * @return [type]          [description]
     */
    public function ajax_appid($keyword){
        $sqlmap = array();
        if($keyword){
            $sqlmap = array('name'=>array('LIKE','%'.$keyword.'%'));
        }
        $result = $this->db->where($sqlmap)->getField('id,name',TRUE);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * [change_status 改变状态]
     * @param  [int] $id [id]
     * @return [boolean]     [返回更改结果]
     */
    public function change_status($id){
        if((int)$id < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $data = array();
        $data['status']=array('exp',' 1-status ');
        $result = $this->db->where(array('id' => $id))->save($data);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }
}