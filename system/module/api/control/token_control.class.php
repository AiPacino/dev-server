<?php
hd_core::load_class('api', 'api');
/**
 * 授权码 API
 * @access public （访问修饰符）
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class token_control extends api_control {

    public function _initialize() {
	    parent::_initialize();
	    $this->service = $this->load->service('member2/auth_code');
        $this->user_service = $this->load->service('member2/member');
    }
    /**
    * 获取token
    * @return $address
    * @author limin
    */
    public function get(){
        $params   = $this->params;
        $params = filter_array($params, [
            'auth_code' => 'required',  //授权码
        ]);
        if(empty($params['auth_code'])){
            api_resopnse([], ApiStatus::CODE_20001,"auth_code必须" );
            return;
        }
        $where = ['code'=>$params['auth_code']];
        $info = $this->service->get_info($where);
        if(!$info){
            api_resopnse([], ApiStatus::CODE_50000,"获取失败" );
            return;
        }
        if($info['status']!=1){
            api_resopnse([], ApiStatus::CODE_50000,"code已失效" );
            return;
        }
        if(time()-intval($info['create_time'])>30){
            api_resopnse([], ApiStatus::CODE_50000,"获取超时" );
            return;
        }
        $data = [
            'code' => $info['code']
        ];
        $this->service->update_status($data);

        $user = $this->user_service->get_info(['mobile'=>$info['username']]);
        if(!$user){
            api_resopnse([], ApiStatus::CODE_50000,"用户不存在" );
            return;
        }
        $plat_list = zuji\certification\Certification::getPlatformList();
        $user_info = array(
            'id' => $user['id'],
            'username' => $user['mobile'],
            'mobile' => $user['mobile'],
            'certified' => $user['certified'],
            'certified_platform' => $user['certified_platform'],
            'certified_platform_name' => $plat_list[$user['certified_platform']],
            'credit' =>  $user['credit'],
            'credit_time' => $user['credit_time'],
            'session_cache_time' => $_SERVER['SESSION_GC_MAXLIFETIME'],
            'token' =>$info['token']
        );

        api_resopnse($user_info, ApiStatus::CODE_0 );
        return;
    }


}
