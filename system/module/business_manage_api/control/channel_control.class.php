<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/16 0016-下午 3:27
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('user', 'offline_store_api');
class channel_control extends user_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('channel/channel');
    }

    /**
     * 渠道信息查询
     */
    public function query() {
        $request = api_request();
        $appid = (int)$request->getAppid();
        $data = [];
        $info = $this->service->get_info($appid);
        if($info){
            $data = [
                'channel_id' => $info['id'],
                'channel_name' => $info['name'],
                'contacts' => $info['contacts'],
                'phone' => $info['phone'],
                'alone_goods' => $info['alone_goods'],
                'desc' => $info['desc']
            ];
        }

        api_resopnse($data, ApiStatus::CODE_0);
    }
}