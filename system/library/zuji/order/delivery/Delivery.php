<?php
namespace zuji\order\delivery;

use zuji\Configurable;
use zuji\Business;
/**
 * 发货单 基类
 * @abstract
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
abstract class Delivery extends Configurable {

    //-+------------------------------------------------------------------------
    // | 基本信息
    //-+------------------------------------------------------------------------
    /**
     * 发货单ID
     * @var int 
     */
    protected $delivery_id = 0;
    protected $create_time = 0;
    protected $update_time = 0;
    
    // 业务类型
    protected $business_key = 0;
    
    //-+------------------------------------------------------------------------
    // | 管理员ID
    //-+------------------------------------------------------------------------
    protected $admin_id = 0;
    
    //-+------------------------------------------------------------------------
    // | 关联订单
    //-+------------------------------------------------------------------------
    protected $order_id = 0;
    protected $order_no = 0;
    
    //-+------------------------------------------------------------------------
    // | 关联商品
    //-+------------------------------------------------------------------------
    protected $goods_id = 0;
    
    //-+------------------------------------------------------------------------
    // | 关联发货地址
    //-+------------------------------------------------------------------------
    protected $address_id = 0;
    
    //-+------------------------------------------------------------------------
    // | 发货单状态
    //-+------------------------------------------------------------------------
    protected $delivery_status = 0;
    protected $delivery_time = 0;
    protected $delivery_remark = null;
    
    //-+------------------------------------------------------------------------
    // | 确认收货
    //-+------------------------------------------------------------------------
    // 确认收货时间
    protected $confirm_time = 0;
    // 确认收货备注
    protected $confirm_remark = null;
    // 确认收货管理员ID（后台替用户确认收货的操作人ID）
    protected $confirm_admin_id = 0;
    
    //-+------------------------------------------------------------------------
    // | 物流相关
    //-+------------------------------------------------------------------------
    protected $wuliu_channel_id = 0;
    protected $wuliu_no = null;
    
    // 租机协议编号（应该放入订单表中）
    protected $protocol_no = null;
    
    /**
     * 构造函数
     * @param array $data
     */
    public function __construct( Array $data=[] ) {
	// $throwException=null 忽略未定义的属性
	$this->config($data,$throwException=null);
    }
    
    
    /**
     * 是否允许 发货
     * @return bool true：允许；false：不允许
     */
    abstract public function allow_to_deliver();
    /**
     * 是否允许 取消发货
     * @return bool true：允许；false：不允许
     */
    abstract public function allow_to_cancel_delivery();
    
    /**
     * 是否允许 确认收货
     * @return bool true：允许；false：不允许
     */
    abstract public function allow_to_confirm_delivery();
    
    /**
     * 是否允许 拒绝签收
     * @return bool true：允许；false：不允许
     */
    abstract public function allow_to_refuse_sign();

    /**
     * 是否允许 生成租机协议 操作
     * @return boolean
     */
    abstract public function allow_to_create_protocol();
    
    /**
     * 创建检测单
     * @param type $data
     * @return Delivery
     * @throws \Exception
     */
    public static function createDelivery( $data ){
	if( $data['business_key'] == Business::BUSINESS_ZUJI ){
	    return new ZujiDelivery($data);
	}elseif( $data['business_key'] == Business::BUSINESS_HUIJI ){
	    return new HuijiDelivery($data);
	}elseif( $data['business_key'] == Business::BUSINESS_HUANHUO ){
	    return new HuanhuoDelivery($data);
	}else{
        return new NullDelivery();
    }
    }
    
}
