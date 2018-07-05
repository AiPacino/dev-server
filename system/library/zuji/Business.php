<?php

namespace zuji;

/**
 * zuji_business 租机业务类型
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Business {

	/**
	 * @var int 租机业务
	 * 业务场景：下单，支付，发货，退货申请，收货，检测，退款
	 */
	const BUSINESS_ZUJI = 1;

	/**
	 * @var int 续机业务
	 * 业务场景：下单，支付
	 */
	const BUSINESS_CONTINUE = 2;

	/**
	 * @var int 买断业务
	 * 业务场景：下单，支付，发货
	 */
	const BUSINESS_BUYOUT = 3;

	/**
	 * @var int 升级业务
	 * 业务场景：下单，支付
	 */
	const BUSINESS_UPGRADE = 4;

	/**
	 * @var int 还机业务
	 * 业务场景：收货，检测，退款（转账）
	 */
	const BUSINESS_GIVEBACK = 5;

	/**
	 * @var int 换机业务
	 * 业务场景1（检测合格）：收货，检测，发货
	 * 业务场景2（检测异常）：收货，检测，异常处理，发货
	 * 注：换机业务涉及到用户买断或赔偿时，走买断流程或赔偿流程
	 */
	const BUSINESS_EXCHANGE = 6;

	/**
	 * @var int 设备回寄
	 * <ul>
	 * <li>检测不合格 退回用户使用</li>
	 * </ul>
	 */
	const BUSINESS_HUIJI = 7;

	/**
	 * @var int 拒签业务
	 * <ul>
	 * <li>客户拒绝签收快递后的业务</li>
	 * </ul>
	 */
	const BUSINESS_REFUSE = 8;

	/**
	 * @var int 换货业务
	 * <ul>
	 * <li>客户确认收货后，进行换货业务 同类商品</li>
	 * </ul>
	 */
	const BUSINESS_HUANHUO = 9;

	/**
	 * @var int 线下门店业务
	 */
	const BUSINESS_STORE = 10;

	/**
	 * 获取业务类型列表
	 */
	public static function get_list() {
		return [
			self::BUSINESS_ZUJI => '租机业务',
			self::BUSINESS_CONTINUE => '续机业务',
			self::BUSINESS_BUYOUT => '买断业务',
			self::BUSINESS_UPGRADE => '升级业务',
			self::BUSINESS_GIVEBACK => '还机业务',
			self::BUSINESS_EXCHANGE => '换机业务',
			self::BUSINESS_HUIJI => '租机回寄',
			self::BUSINESS_REFUSE => '拒签业务',
			self::BUSINESS_HUANHUO => '换货业务',
			self::BUSINESS_STORE => '门店业务',
		];
	}

	public static function getList() {
		return self::get_list();
	}

	/**
	 * 验证业务类型是否存在
	 * @param int $k 业务类型
	 * @return boolean
	 */
	public static function verifyBusinessKey($k) {
		return array_key_exists($k, self::get_list());
	}

	/**
	 * 根据id 获取业务类型
	 * @param int $business_key 【必须】业务类型ID 
	 * @return string 业务名称
	 */
	public static function get_name($business_key) {
		$arr = self::get_list();
		if (!isset($arr[$business_key])) {
			return '';
		}
		return $arr[$business_key];
	}

	public static function getName($business_key) {
		return self::get_name($business_key);
	}

	/**
	 * 创建业务流水号
	 * @param string 业务类型ID
	 * @return string $number   订单号
	 */
	public static function create_business_no($key = 0) {
		static $order2_number = null;
		if (!$order2_number) {
			$load = \hd_load::getInstance();
			$order2_number = $load->table('order2/order2_number');
		}

		$key = sprintf('%02d', $key);
		$time = time();
		$day = date('Ymd', $time);
		//年+月+日+自增数(5位)
		$order2_number->startTrans();
		$max = $order2_number->get_day_max_no($day);
		$no = $max + 1;
		if ($max) {
			$b = $order2_number->increase($day);
		} else {
			$b = $order2_number->create($day, $no);
		}
		// 失败
		if( $b === false ){
			$order2_number->rollback();
			throw new \Exception('订单编号入库失败');
		}else{
			$order2_number->commit();
		}
		

		// 小于
		$n = strlen('' . $no);
		if (strlen('' . $no) < 8) {
			$no = sprintf('%0' . ($n + 1) . 'd', $no);
		}
		$prefix = '';
		if ($_SERVER['ENVIRONMENT'] == 'test') {
			$prefix = config('BUSINESS_NO_PREFIX');
		}
		return $prefix.$day . $key . $no;
	}

//    /**
//     * 创建业务流水号
//     * @return  string  业务流水号	例如：2017 11 10 06 45 05 0001 6980
//     */
//    public static function create_business_no ($business_key){
//	$load = \hd_load::getInstance();
//	$load->get
//	var_dump( $load );exit;
//	// 根据业务码，前面补0，获取4位数字
//	$key = sprintf('%04d',$business_key);
//	// 获取当前时间（微妙）
//	list( $micro, $time ) = explode(' ',microtime());
//	// 格式化微妙值
//	$microsecond = substr($micro, 2, 4);
//	// 生成业务流水号
//	$serial_no = date('YmdHis',$time).$key.$microsecond;
//	return $serial_no;
//    }
//    
	/**
	 * 
	 */
	public static function create_debug_no() {
		// 获取当前时间（微妙）
		list( $micro, $time ) = explode(' ', microtime());
		// 格式化微妙值
		$microsecond = substr($micro, 2, 4);
		// 生成业务流水号
		$main_no = date('Ymd', $time) . $microsecond;
		return [
			'main_no' => date('Ymd', $time),
			'sub_no' => date('Ymd', $time),
		];
	}

}
