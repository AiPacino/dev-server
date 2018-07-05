<?php
/**
 * 		订单日志服务层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class order_log_service extends service {

	public function _initialize() {
		$this->table = $this->load->table('order2/order2_log');
	}

	/**
	 * 写入订单日志
	 * @param $params 日志相关参数
	 * @return [boolean]
	 */
	public function add($params = array(),$extra = FALSE) {
		$params = array_filter($params);
		if (empty($params)) {
			$this->error = lang('order_log_empty','order/language');
			return FALSE;
		}
		$params['system_time'] = time();
		$result = $this->table->update($params);
		if (!$result) {
			$this->error = $this->table->getError();
			return FALSE;
		}
		return $result;
	}

	/**
	 * 根据子订单号获取日志
	 * @param $order_no : 订单号(默认空)
	 * @param $order  : 排序(默认主键升序)
	 * @return [result]
	 */
	public function get_by_order_no($order_no = '' , $order = 'id ASC') {
		$order_no = (string) remove_xss($order_no);
		if (!$order_no) {
			$this->error = lang('order_no_not_null','order/language');
			return FALSE;
		}
		$order = (string) remove_xss($order);
		$sqlmap = array();
		$sqlmap['order_no'] = $order_no;
		return $this->table->where($sqlmap)->order($order)->select();
	}
}