<?php

namespace oms\observer;

/**
 * OrderObservable  订单观察者 主题接口声明
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderObservable {
	
	/**
	 *
	 * @var boolean
	 */
	private $status = False;
	
	/**
	 *
	 * @var \oms\Order 
	 */
	private $Order = null;
	private $Observers = array();

	public function __construct(\oms\Order $Order) {
		$this->Order = $Order;
		$this->Order->set_observable($this);
	}

	/**
	 * 设置观察主题状态
	 * @param boolean
	 * @return $this
	 */
	public function set_status($status){
		$this->status = $status;
		return $this;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function get_status(){
		return $this->status;
	}
	
	/**
	 * 
	 * 注册观察者
	 * @param \oms\observer\OrderObserver $Observer
	 * @return \oms\observer\OrderObservable
	 */
	public function attach(OrderObserver $Observer) {
		$this->Observers[$Observer->get_id()] = $Observer;
		return $this;
	}

	/**
	 * 注销观察者
	 * @param \oms\observer\OrderObserver $Observer
	 * @return \oms\observer\OrderObservable
	 */
	public function detach(OrderObserver $Observer) {
		if (isset($this->Observers[$Observer->get_id()])) {
			unset($this->Observers[$Observer->get_id()]);
		}
		return $this;
	}

	/**
	 * 主题发生变化，更新
	 * @param \oms\observer\OrderObserver $Observer
	 * @return null
	 */
	public function notify() {
		foreach ($this->Observers as $Observer) {
			$Observer->update();
		}
	}

	/**
	 * 获取订单对象
	 * @return \oms\Order
	 */
	public function get_order() {
		return $this->Order;
	}

}
