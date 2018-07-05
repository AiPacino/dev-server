<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/4 0004-下午 3:07
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('base', 'offline_store_api');
class user_control extends base_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('channel/channel_member');
        $this->member = $this->service->get_user();

        $request = api_request();
        $this->appid = $request->getAppid();
        $appid_info = model('channel/channel_appid')->get_info($this->appid);
        if(empty($appid_info)){
            $respone = api_resopnse( [],ApiStatus::CODE_10103,"appid错误");
            $respone->flush();
            die;
        }

        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
        if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            api_resopnse('',  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请登录')->flush();
            exit;
        }
    }
}