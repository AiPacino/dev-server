<?php
/**
 * 		物流控制器
 */

// 加载 goods 模块中的 init_control
hd_core::load_class('init','goods');
class wuliu_control extends init_control {

	public function _initialize() {
		parent::_initialize();
		$this->service = $this->load->service('wuliu/wuliu');
	}

}