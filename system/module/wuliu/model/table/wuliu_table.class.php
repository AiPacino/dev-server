<?php
/**
 * 		物流模型
 */
class wuliu_table extends table {

    //验证
	protected $_validate = array(
        /* array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间]), */
    );

    //自动完成
    protected $_auto = array(
    	// array(完成字段1,完成规则,[完成条件,附加规则]),
    );

    //自动加载
    public function _initialize() {
        // $this->service_sub = model('order/order_sub','service');
    }

    //select添加内容
    public function _after_select($orders, $options) {
        //
    }

}