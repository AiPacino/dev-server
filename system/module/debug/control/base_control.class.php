<?php
/**
 * 订单控制器 基类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 *
 */
// 加载 goods 模块中的 init_control
hd_core::load_class('init', 'admin');
class base_control extends init_control {


    /**
     *
     * @var \debug_service
     */
    protected $debug_service = null;

    public function _initialize() {
	parent::_initialize();
	$this->debug_service = $this->load->service('debug/debug');
    }
    
    public function index(){
	
    }

}