<?php
namespace oms;

/**
 * 订单日志
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderLog {
    
    private $id = 0;
    private $user_type = 0; // 操作人 类型
    private $user_id = 0;   // 操作人 ID
    private $username = ''; // 操作人 用户名
    private $create_time = 0;// 操作时间
    private $old_status = 0;// 操作前状态
    private $new_status = 0;// 操作后状态
    private $name = '';	    // 操作名称
    private $remark = '';   // 操作备注
    
    public function __construct( $data ) {
	$this->user_type = $data['user_type'];
    }
    
}
