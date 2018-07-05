<?php
/**
 * 		买断单
 */

// 加载 goods 模块中的 init_control
hd_core::load_class('init','goods');
class refund_control extends init_control {

	public function _initialize() {
		parent::_initialize();
		$this->service = $this->load->service('order2/payment');
	}

    /**
     * 买断单列表
     *
     */
	public function buyout_list(){

    }

}