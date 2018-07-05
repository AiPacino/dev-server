
<?php
namespace oms\observer;

/**
 * ShortMessageObserver  短信
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ShortMessageObserver extends OrderObserver {

    public function __construct(\oms\observer\OrderObservable $Observable ){
        parent::__construct($Observable);
    }
    
    public function get_id() {
	return 'order-sm-observer';
    }

    public function update() {
	var_dump('短信通知--开始');
	var_dump('短信通知中...');
	var_dump('短信通知--结束');
    }

    
}
