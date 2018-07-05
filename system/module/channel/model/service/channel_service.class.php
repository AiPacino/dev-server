<?php
/**
 * 商品渠道服务层
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/18 0018
 * Time: 上午 11:50
 */

class channel_service extends service
{

    const ENUM_ALONE_GOODS_YES = 1;
    const ENUM_ALONE_GOODS_NO = 0;

    public $enum_alone_goods = [
        self::ENUM_ALONE_GOODS_YES => '是',
        self::ENUM_ALONE_GOODS_NO => '否',
    ];

    public function _initialize() {
        $this->channel_db = $this->load->table('channel/channel');
    }

    /**
     * [get_lists 获取商品渠道列表]
     * @return [type] [description]
     */
    public function get_list($where=[],$options = []){
        $result = $this->channel_db->get_list($where, $options);
        return $result;
    }

    /**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap = array()){
        $result = $this->channel_db->where($sqlmap)->count('id');
        if($result === false){
            $this->error = $this->channel_db->getError();
            return false;
        }
        return $result;
    }

    /**
     * 添加
     * @param array $params
     * @return bool
     */
    public function add_channel($params = array()){
        $result = $this->channel_db->update($params);
        if($result === FALSE){
            $this->error = $this->channel_db->getError();
            return FALSE;
        }
        return $result;
    }

    /**
     * 编辑
     * @param $params
     * @return bool
     */
    public function edit_channel($params){
        runhook('before_edit_channel', $params);
        if((int)$params['id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->channel_db->update($params);
        if($result === FALSE){
            $this->error = $this->channel_db->getError();
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
        $result = $this->channel_db->where($sqlmap)->delete();
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
        $result = $this->channel_db->where(['id' => $id])->find($options);
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
        $result = $this->channel_db->where(array('id' => $id))->save($data);
        if(!$result){
            $this->error = lang('_operation_fail_');
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
        $result = $this->channel_db->where(array('id'=>$params['id']))->save($params);
        if($result === false){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * [search_brand 关键字查找渠道]
     * @param  [type] $keyword [description]
     * @return [type]          [description]
     */
    public function ajax_channel($keyword){
        $sqlmap = array();
        if($keyword){
            $sqlmap = array('name'=>array('LIKE','%'.$keyword.'%'));
        }
        $result = $this->channel_db->where($sqlmap)->getField('id,name',TRUE);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }
}