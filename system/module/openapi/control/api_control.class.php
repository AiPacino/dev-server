<?php
/**
 * API模块 基类控制器
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2017/12/26 0026-下午 5:42
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('base', 'api');
class api_control extends base_control
{

    public function _initialize() {

        // 如果不是 openapi请求，则执行 父类的 _initialize()
        if( IS_OPEN_API == false ){
            parent::_initialize();
            return ;
        }

        $request = api_request();
        $this->appid = $request->getAppid();
        $params = json_encode(api_params());
        $sign = $request->getSign();
        //签名验证
        $verify_result = ApiUtil::rsa_verify($params, $sign);
        if(!$verify_result){
            $respone = api_resopnse( [],ApiStatus::CODE_10109,"签名错误");
            $respone->flush();
            die;
        }

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

    }
}