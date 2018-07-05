<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oms\observer;

/**
 * OrderObserver 订单观察者接口声明
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
abstract class OrderObserver {
    
    /**
     * 观察主题对象
     * @var \oms\observer\OrderObservable
     */
    protected $Observable = null;
    
    public function __construct(\oms\observer\OrderObservable $Observable ){
	$Observable->attach($this);
	$this->Observable = $Observable;
    }
    
    public function set_observable( \oms\observer\OrderObservable $Observable ){
	$this->Observable = $Observable;
    }
    
    /**
     * 观察者 唯一标识
     */
    abstract public function get_id();
    
    /**
     * 主题更新时执行
     */
    abstract public function update();
    
}
