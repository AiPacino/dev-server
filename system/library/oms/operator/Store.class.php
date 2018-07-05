<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oms\operator;

/**
 * Store 
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Store implements Operator {
    
    private $id = null;
    private $username = null;
    
    public function __construct( $id, $username ) {
	$this->id = $id;
	$this->username = $username;
    }
    
    public function get_id() {
	return $this->id;
    }

    public function get_type() {
	return self::Type_Store;
    }

    public function get_username() {
	return $this->username;
    }

}
