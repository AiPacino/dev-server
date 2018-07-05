<?php
/**
 *	  用户统计数
 */
hd_core::load_class('api', 'api');
class user_sum_control extends api_control {

    public function _initialize() {
	    parent::_initialize();
	    $this->service = $this->load->table('member2/member');
    }

    /**
     * 总数查询
     */
    public function query() {
        $count = $this->service->get_count([]);
        $data = [
            'count'=>$count+2000,
        ];
        api_resopnse( $data, ApiStatus::CODE_0 );
    }

}
