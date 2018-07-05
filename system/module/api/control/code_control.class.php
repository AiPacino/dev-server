<?php
hd_core::load_class('api', 'api');
/**
 * 授权码 API
 * @access public （访问修饰符）
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class code_control extends user_control {

    public function _initialize() {
	    parent::_initialize();
	    $this->service = $this->load->service('member2/auth_code');
    }
    /**
    * 获取code
    * @return $address
    * @author limin
    */
    public function get(){
        $data['code'] = guid();
        $data['token'] = $this->auth_token;
        $data['appid'] = $this->appid;
        $data['username'] = $this->member['username'];
        $data['create_time'] = time();
        $data['status'] = 1;
        $ret = $this->service->create($data);
        if(!$ret){
            api_resopnse([], ApiStatus::CODE_50000,"获取异常" );
            return;
        }
        api_resopnse(['code'=>$data['code']], ApiStatus::CODE_0 );
        return;
    }


}
