<?php
namespace oms;

use oms\order_creater\CreditComponnet;
use \oms\order_creater\OrderCreaterComponnet;
use \oms\order_creater\UserComponnet;
use \oms\order_creater\SkuComponnet;
use oms\order_creater\YidunComponnet;

/**
 * OrderCreater  订单创建器
 * 
 * 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderCreater implements OrderCreaterComponnet {
    
    /**
     * 订单类型
     * @var string 
     */
    private $order_no = null;
    /**
     * 订单编号
     * @var string 
     */
    private $business_key = null;
    /**
     * 订单ID
     * @var int 
     */
    private $order_id = null;
    
    /** 
	 * Sku组件
     * @var \oms\order_creater\SkuComponnet
     */
    private $sku_componnet = null;
    /**
     * Credit组件
     * @var \oms\order_creater\SkuComponnet
     */
    private $credit_componnet = null;
    /** 
	 * 用户组件
     * @var \oms\order_creater\UserComponnet
     */
    private $user_componnet = null;
	/**
	 * 错误提示
	 * @var string
	 */
    private $error = '';
	/**
	 * 错误码
	 * @var int
	 */
    private $errno = 0;
    
	/**
	 * 免押金状态 0：不免押金；1：全免押金
	 * @var int
	 */
	private $mianya_status = 0;
	
    
    /**
     * 构造器
     * @param OrderCreaterComponnet $componnet  订单创建器组件对象
     */
    public function __construct( int $business_key, $order_no=null ) {
		$this->business_key = $business_key;
		$this->order_no = $order_no;
    }
	
    /**
	 * 获取 订单编号
	 * @return string
	 */
    public function get_order_no(): string {
        return $this->order_no;
    }
	
	/**
	 * 获取订单ID
	 * @return int
	 */
    public function get_order_id(): int {
        return $this->order_id;
    }
    /*
     * 获取租机业务类型
     * @return int
     */
    public function get_business_key():int{
        return  $this->business_key;
    }

    
	/**
	 * 
	 * 设置 User组件
	 * @param \oms\order_creater\UserComponnet $user_componnet
	 * @return \oms\OrderCreater
	 */
    public function set_user_componnet(UserComponnet $user_componnet){
        $this->user_componnet = $user_componnet;
        return $this;
    }
	/**
	 * 获取 User组件
	 * @return \oms\order_creater\UserComponnet
	 */
    public function get_user_componnet(){
        return $this->user_componnet;
    }

    /**
	 * 设置 Sku组件
	 * @param \oms\order_creater\SkuComponnet $sku_componnet
	 * @return \oms\OrderCreater
	 */
    public function set_sku_componnet(SkuComponnet $sku_componnet){
        $this->sku_componnet = $sku_componnet;
        return $this;
    }
    /**
     * 获取 Sku组件
     * @return \oms\order_creater\SkuComponnet
     */
    public function get_sku_componnet(): SkuComponnet{
        return $this->sku_componnet;
    }
    /**
	 * 获取 订单创建器
	 * @return \oms\OrderCreater
	 */
    public function get_order_creater(): OrderCreater {
        return $this;
    }

    /**
     * 设置 错误提示
     * @param string $error  错误提示信息
     * @return \oms\OrderCreater
     */
    public function set_error( string $error ): OrderCreater {
        $this->error = $error;
        return $this;
    }
	/**
	 * 获取 错误提示
	 * @return string
	 */
    public function get_error(): string{
        return $this->error;
    }
    
    /**
     * 设置 错误码
     * @param int $errno	错误码
     * @return \oms\OrderCreater
     */
    public function set_errno( $errno ): OrderCreater {
        $this->errno = $errno;
        return $this;
    }
	/**
	 * 获取 错误码
	 * @return int
	 */
    public function get_errno(): int{
        return $this->errno;
    }
	
	/**
	 * 设置免押状态
	 * @param int $status
	 * @return \oms\OrderCreater
	 */
	public function set_mianya_status( int $status ): OrderCreater{
		if( !in_array($status, [0,1]) ){
			throw new Exception('免押状态值设置异常');
		}
		$this->mianya_status = $status;
		return $this;
	}
	/**
	 * 获取免押状态
	 * @return int
	 */
	public function get_mianya_status(): int{
		return $this->mianya_status;
	}

		/**
     * 过滤
     * @return bool
     */
    public function filter():bool{
        $b = $this->user_componnet->filter();
        if( !$b ){
            return false;
        }
        $b = $this->sku_componnet->filter();
        if( !$b ){
            return false;
        }
        return true;
    }
	
	public function get_data_schema(): array{
		$user_schema = $this->user_componnet->get_data_schema();
		$sku_schema = $this->sku_componnet->get_data_schema();
		return array_merge(['order'=>[
			'business_key' => $this->business_key,
			'order_no'=>$this->order_no
		]],$user_schema,$sku_schema);
	}

    /**
     * 创建订单
     * @return bool
     */
    public function create():bool{
        
        //var_dump('创建订单...');
		// 创建订单
		$order_data = [
			'order_status' => \zuji\order\OrderStatus::OrderCreated, // 订单已创建
			'business_key' => $this->business_key,        // 业务类型值
			'order_no' => $this->order_no,  // 编号
			'status' => \oms\state\State::OrderCreated,  // 状态
			'create_time' => time(),
		];
        $order2_table = \hd_load::getInstance()->table('order2/order2');
		$order_id = $order2_table->add($order_data);
		if( !$order_id ){
			$this->set_error('保存订单失败');
			return false;
		}
		$this->order_id = $order_id;

        $follow_table = \hd_load::getInstance()->table('order2/order2_follow');
        $follow_data =[
            'order_id'=>$order_id,
            'old_status'=>0,
            'new_status'=>1,
            'create_time'=>time(),
        ];
        $follow_table->add($follow_data);


		// 执行 User组件
        $b = $this->user_componnet->create();
        if( !$b ){
            return false;
        }
		// 执行 Sku组件
        $b = $this->sku_componnet->create();
        if( !$b ){
            return false;
        }
        return $b;
    }
    
}
