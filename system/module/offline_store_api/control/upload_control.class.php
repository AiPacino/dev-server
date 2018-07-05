<?php

/**
 * 文件上传控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
hd_core::load_class('user', 'offline_store_api');

class upload_control extends base_control {

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('upload/upload');
    }
    //图片上传
    public function images(){
        $params = $this->params;
        $data = filter_array($params,[
            'file_name' => 'required',
            'new_name' => 'required',
            'file_src' => 'required'
        ]);
        if(!$data['file_name']){
            api_resopnse( [], ApiStatus::CODE_20001,"file_name必须");
            return;
        }
        if(!$data['file_src']){
            api_resopnse( [], ApiStatus::CODE_20001,"file_src必须");
            return;
        }
        if(empty($data['new_name'])){
            $data['new_name'] = true;
        }
        $result = $this->service->api_upload($data);
        if($result['ret']==0){
            api_resopnse( $result['img'], ApiStatus::CODE_0);
            return;
        }
        api_resopnse($result, ApiStatus::CODE_50000,"上传出错");
        return;
    }
}
