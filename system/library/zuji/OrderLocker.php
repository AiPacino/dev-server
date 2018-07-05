<?php
namespace zuji;

/**
 * 订单锁
 * <p>为订单创建临时操作限制</p>
 * <p>基于 Redis key-value 数据类型实现，Redis原子性操作解决了线程安全问题 </p>
 * <p>重复加锁策略：覆盖之前的锁状态，只保留最后一次加锁状态</p>
 * @author liuhongxing<liuhongxing@huishoubao.com.cn>
 */
class OrderLocker {
		
	/**
	 * 未加锁（通用状态）
	 */
	const Unlocked = 'unlocked';
	/**
	 * 处理中（通用状态）
	 */
	const Processing = 'processing';
	
	/**
	 * 支付中
	 */
	const Paying = 'paying';
	
	/**
	 * 退款中
	 */
	const Refunding = 'refunding';
	/**
     * 预授权中
     */
	const Authorizing = 'authorizing';
    /**
     * 解除预授权中
     */
    const Unauthorizing = 'unauthorizing';
	
	/**
	 * 取消中
	 */
	const Canceling = 'canceling';

	/**
	 * 关闭中
	 */
	const Closeing = 'closeing';

	/**
	 * 扣款中
	 */
	const Withholding = 'withholding';
	
	/**
	 * 芝麻小程序订单用户确认中（用户前端支付中）
	 */
	const ZMminiPaying = 'zm-mini-paying';
	
	/**
	 * 状态名称映射
	 * @var array
	 */
	private static $_nams = [
		self::Unlocked => '',
		self::Processing => '处理中',
		self::Paying => '支付中',
		self::Refunding => '退款中',
        self::Authorizing=>'预授权中',
        self::Unauthorizing=>'解除资金预授权中',
		self::Canceling => '取消中',
		self::ZMminiPaying => '芝麻小程序订单用户确认中',
		self::Closeing => '订单关闭中',
		self::Withholding => '扣款中',
	];
	
	/**
	 * 前缀
	 * @var string
	 */
	private static $_prefx = 'zuji_order_block_';

	/**
	 * 获取阻塞的状态名称
	 * @param string $key
	 * @return string
	 */
	public static function getLockName($key){
		if( !isset(self::$_nams[$key]) ){
			$key = self::Processing;
		}
		return self::$_nams[$key];
	}
	
	/**
	 * 读取锁
	 * @param string $id
	 * @return string
	 */
	public static function getLock( string $id ){
		$state = cache\Redis::getInstans()->get(self::$_prefx.$id);
		if( $state == false ){
			return self::Unlocked;
		}
		return $state;
	}
	
	/**
	 * 判断是否加锁
	 * @param string $id
	 * @param string $state 是指判断是锁
	 * @return boolean  true：已经加锁；false：未加锁
	 */
	public static function isLocked( string $id, string $state=null ){
		$_state = self::getLock($id);
		if( $_state == self::Unlocked ){
			return false;
		}
		if( !is_null($state) && $_state != $state ){
			return false;
		}
		return true;
	}
	
	/**
	 * 加锁
	 * <p>如果已经有锁时，会覆盖之前的锁</p>
	 * @param string $id
	 * @param string $state
	 * @return boolean
	 */
	public static function lock( string $id, string $state ){
		if( !isset(self::$_nams[$state]) ){
			throw new \Exception('状态锁错误');
		}
		return cache\Redis::getInstans()->set(self::$_prefx.$id, $state);
	}
	
	/**
	 * 解锁
	 * @param string $id
	 * @return boolean
	 */
	public static function unlock( string $id ){
		// 如果键不存在，则返回0，如果存在，则删除，所以忽略reids的返回值
		cache\Redis::getInstans()->del(self::$_prefx.$id);
		return true;
	}
	
}
