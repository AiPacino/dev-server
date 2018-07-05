<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19 0019
 * Time: 下午 3:32
 */

class channel_address_service extends service
{

    const TYPE_HUIJI = 1;
    const TYPE_YOUJI = 2;

    public $enum_type = [
        self::TYPE_HUIJI => '回寄',
        self::TYPE_YOUJI => '邮寄',
    ];

    public function _initialize() {
        $this->db = $this->load->table('channel/channel_address');
    }

    /**
     * [get_lists 获取列表]
     * @return [type] [description]
     */
    public function get_list($where,$options = []){
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
     * 编辑
     * @param $params
     * @return bool
     */
    public function edit_channel_address($params){
        runhook('before_edit_channel_address', $params);
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
    public function get_info($id, $options=[]){
        $result = $this->db->where(['id' => $id])->find($options);
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
}