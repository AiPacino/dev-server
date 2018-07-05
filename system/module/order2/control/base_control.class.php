<?php
/**
 * 订单控制器 基类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 *
 */
use zuji\Business;
// 加载 goods 模块中的 init_control
hd_core::load_class('init', 'admin');
class base_control extends init_control {


    /**
     *
     * @var \debug_service
     */
    protected $debug_service = null;


    /**
     * @var array  支付渠道列表
     */
    protected $pay_channel_list = [
        '0' => '全部',
        '1' => '支付宝',
    ];

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'order_id' => '订单ID',
    ];
    

    public function _initialize() {
	parent::_initialize();
	$this->debug_service = $this->load->service('debug/debug');
	$this->load->librarys('View')->assign('business_list', array_merge( ['0'=>'全部'], Business::getList() ) );
	$this->service_order_log = $this->load->service('order2/order_log');
    }

}