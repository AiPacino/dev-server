<?php
/**
 * 门店会员API
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/2 0002-下午 6:18
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('base', 'offline_store_api');
class store_member_control extends base_control
{

    public function _initialize() {
        parent::_initialize();

        $this->service = $this->load->service('channel/channel_member');
    }

    /**
     * 门店登录，支持用户名密码，手机号验证码两种登录方式
     */
    public function login(){

        //返回数据格式
        $result = [
            'auth_token'=> $this->auth_token,
            'user_info'=>[],
        ];

        //获取会员信息
        /*$user_info = $this->service->get_user();
        $user_info['appid'] = $user_info['relation_id'];
        if( $user_info ){
            $result['user_info'] = filter_array($user_info, [
                'id' => 'required',
                'type' => 'required',
                'username' => 'required',
                'email' => 'required',
                'encrypt' => 'required',
                'appid' => 'required',
                'session_cache_time' => $_SERVER['SESSION_GC_MAXLIFETIME']
            ]);
            api_resopnse( $result, ApiStatus::CODE_0,'登录成功', '','登录成功' );
            return;
        }*/

        //获取参数
        $params = $this->params;

        //登录操作
        $login_info = $this->service->login($params, 1);

        if( !$login_info ){
            $error = $this->service->error;
            api_resopnse( [$params], $error['code'], $error['msg'], $error['subCode'], $error['subMsg'] );
            return;
        }

        // 获取用户基本信息
        $user_info = $this->service->user;
        $user_info['appid'] = $user_info['relation_id'];
        $user_info = filter_array($user_info, [
            'id' => 'required',
            'type' => 'required',
            'username' => 'required',
            'email' => 'required',
            'encrypt' => 'required',
            'appid' => 'required',
            'session_cache_time' => 'required',
        ]);

        // 保存回话
        $this->service->set_user($user_info);

        // 清除登录的业务数据
        unset($_SESSION['_login_mobile_']);
        unset($_SESSION['_login_sm_code_']);
        unset($_SESSION['_login_time_']);
        unset($_SESSION['_login_expiry_']);
        $user_info ['session_cache_time'] = $_SERVER['SESSION_GC_MAXLIFETIME'];
        $result['user_info'] = $user_info;
        //返回用户信息
        api_resopnse($result, ApiStatus::CODE_0,'登录成功', '','登录成功' );
        return;

    }

    /**
     * 门店店员账户密码修改
     */
    public function edit_password(){
        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
        $this->member = $this->service->get_user();
        if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            api_resopnse('',  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录');
            exit;
        }

        //获取参数
        $params = $this->params;
        $params['id'] = $this->member['id'];
        //参数过滤
        $params = filter_array($params, [
            'id' => 'required|is_id',
            'account' => 'required',
            'old_password' => 'required',
            'new_password' => 'required',
            'verify_password' => 'required'
        ]);

        if(count($params) < 4){
            api_resopnse([$params], ApiStatus::CODE_20001,'参数错误', ApiSubCode::Params_Error, '' );
            return;
        }

        //修改密码处理
        $result = $this->service->edit_password($params);
        if(!$result){
            $error = $this->service->error;
            api_resopnse( [$params], $error['code'], $error['msg'], $error['subCode'], $error['subMsg'] );
            return;
        }

        api_resopnse([], ApiStatus::CODE_0,'修改成功', '','修改成功' );
        return;
    }
}