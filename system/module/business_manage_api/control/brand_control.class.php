<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/16 0016-下午 3:27
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('user', 'offline_store_api');
class brand_control extends user_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('goods2/brand');
    }

    /**
     * 品牌信息查询
     */
    public function query() {
        $data = $this->service->api_get_list(['status'=>1],"id,name");

        api_resopnse($data, ApiStatus::CODE_0);
    }
}