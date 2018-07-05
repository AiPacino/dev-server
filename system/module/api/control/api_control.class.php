<?php
/**
 * API模块 基类控制器
 */
hd_core::load_class('base', 'api');
class api_control extends base_control
{

    public function _initialize() {
	
        //-+--------------------------------------------------------------------
        // | IP黑名单
        //-+--------------------------------------------------------------------
		$b = \zuji\Limited::inIpBlackList(get_client_ip() );
		if( $b ){
            api_resopnse('',  ApiStatus::CODE_40003,'权限拒绝', 'IP access denied', '禁止操作')->flush();
            exit;
		}
		
        // 如果不是 api请求，则执行 父类的 _initialize()
        if( IS_API == false ){
            parent::_initialize();
            return ;
        }

        $request = api_request();
        $this->appid = $request->getAppid();

        if(!intval($this->appid)){
            $respone = api_resopnse( [],ApiStatus::CODE_10103,"appid错误");
            $respone->flush();
            die;
        }

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
        $this->member = $this->get_user();
        $this->params = api_params();
		
        //-+--------------------------------------------------------------------
        // | 用户黑名单
        //-+--------------------------------------------------------------------
		if( $this->member && $this->member['id']>0 ){
			$b = \zuji\Limited::inUserBlackList( intval($this->member['id']) );
			if( $b ){
				api_resopnse('',  ApiStatus::CODE_40003,'权限拒绝', 'in black list', '禁止操作')->flush();
				exit;
			}
		}
    }
}