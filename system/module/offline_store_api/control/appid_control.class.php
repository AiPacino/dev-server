<?php
/**
 * 门店操作类
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/4 0004-下午 3:14
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('base', 'offline_store_api');
class appid_control extends base_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('channel/channel_appid');
    }

    /**
     * 门店信息查询
     */
    public function query() {
        $request = api_request();
        $appid = (int)$request->getAppid();
        $data = [];
        $appid_info = $this->service->get_info($appid);
        if($appid_info){
            $data = [
                'appid' => $appid_info['appid']['id'],
                'channel_id' => $appid_info['_channel']['id'],
                'channel_name' => $appid_info['_channel']['name'],
                'name' => $appid_info['appid']['name'],
                'mobile' => $appid_info['appid']['mobile'],
                'address' => $appid_info['appid']['address'],
                'is_card' => $appid_info['appid']['is_upload_idcard']
            ];
        }

        api_resopnse($data, ApiStatus::CODE_0);
    }
}