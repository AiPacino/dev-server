<?php

/**
 * 		物流服务层
 */
class wuliu_service extends service {

    const WULIU_SUNFENG = 1;

    public function _initialize() {
        /* 实例化数据层 */
        /* 实例化服务层 */
    }
    public function get_lists(){

        $list = array(
                    'channel_code'    => "顺丰",
                    'channel_name'    => "顺丰物流"
                );
        return $list;

    }
}
