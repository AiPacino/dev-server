<?php
namespace oms\state;

/**
 * StateTransition 状态转换
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class StateTransition {
    
    private $name = '';
    private $old_state = 0;
    private $new_state = 0;
    
    /**
     * 
     * @param string $name
     * @param int $old_state
     * @param int $new_state
     */
    public function __construct(string $name, int $old_state, int $new_state) {
	$this->name = $name;
	$this->old_state = $old_state;
	$this->new_state = $new_state;
    }
    
    
    public function get_name() {
	return $this->name;
    }

    public function get_old_state() {
	return $this->old_state;
    }

    public function get_new_state() {
	return $this->new_state;
    }


    
    
}
