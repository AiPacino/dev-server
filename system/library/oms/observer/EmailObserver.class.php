<?php
namespace oms\observer;

/**
 * EmailObserver  邮件
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class EmailObserver extends OrderObserver {
        
    public function get_id() {
	return 'order-email-observer';
    }

    public function update() {
	var_dump('邮件通知--开始');
	var_dump('邮件通知中...');
	var_dump('邮件通知--结束');
    }

    
}
