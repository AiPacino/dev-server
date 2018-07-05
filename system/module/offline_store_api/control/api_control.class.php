<?php
/**
 * API模块 基类控制器
 */
hd_core::load_class('base', 'api');
class api_control extends base_control
{

    public function _initialize() {
	
        // 如果不是 api请求，则执行 父类的 _initialize()
        if( IS_API == false ){
            parent::_initialize();
            return ;
        }

        $request = api_request();
        $this->appid = $request->getAppid();
        $appid_info = model('channel/channel_appid')->get_info($this->appid);
        if(empty($appid_info)){
            $respone = api_resopnse( [],ApiStatus::CODE_10103,"appid错误");
            $respone->flush();
            die;
        }

        $authToken = $request->getAuthToken();
    //	var_dump( $authToken );exit;
        //$authToken = '64vpef9r8duqegto5plmajfrr6';
        if( $this->session_valid_id($authToken) ){
            session_id( $authToken );
        }
        session_start();
        $this->auth_token = session_id();
        $this->params = api_params();
	
    }
}