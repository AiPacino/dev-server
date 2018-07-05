<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/16 0016-下午 3:28
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('base', 'offline_store_api');
class channel_member_control extends base_control
{

    public function _initialize() {
        parent::_initialize();

        $this->service = $this->load->service('channel/channel_member');
    }

    public function login(){
        //返回数据格式
        $result = [
            'auth_token'=> $this->auth_token,
            'user_info'=>[],
        ];

        //获取参数
        $params = $this->params;

        //登录操作
        $login_info = $this->service->login($params, 2);

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
        $this->channel = $this->load->service('channel/channel');
        $info = $this->channel->get_info($user_info['appid']);
        $user_info['channel_name'] =  $info['name'];
        $user_info['channel_id'] = $info['id'];

        // 保存回话
        $this->service->set_user($user_info);

        // 清除登录的业务数据
        $user_info ['session_cache_time'] = $_SERVER['SESSION_GC_MAXLIFETIME'];
        $result['user_info'] = $user_info;
        //返回用户信息
        api_resopnse($result, ApiStatus::CODE_0,'登录成功', '','登录成功' );
        return;

    }

    public function logout(){
        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
        $this->member = $this->service->get_user();
        if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            api_resopnse('',  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录');
            exit;
        }

        unset($_SESSION['__MEMBER_INFO__']);
        //返回用户信息
        api_resopnse([], ApiStatus::CODE_0,'退出成功', '','退出成功' );
        return;
    }
}