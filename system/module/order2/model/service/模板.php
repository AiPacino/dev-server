<?php

/**
 * 
 *
 * @author Administrator
 */
class ClassName {
    
    
    /**
     * 查询 XXX单 列表
     * @param array $where	    【可选】查询条件
     * array(
     *	    'id'	        => '',  【可选】int|string|array	int：主键ID；string：多个主键ID，逗号','分隔；array：主键ID数组
     *	    'status'	    => '',  【可选】int|string|array	int：状态值；string：多个状态值，逗号','分隔；array：状态值数组
     *	    'begin_time'    => '',  【可选】int	开始时间戳
     *	    'end_time'	    => '',  【可选】int	结束时间戳（包含）
     *	    'business_key'  => '',  【可选】int|string|array	int：业务类型值；string：多个也只，逗号',分隔；array：业务类型值数组
     * )
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	【可选】int 分页码，默认为1
     *	    'size'	=> '',	【可选】int 每页大小，默认20
     *	    'orderby'	=> '',	【可选】string	排序；time.DESC：时间倒序；time_ASC：时间顺序；
     * )
     * @return array
     * array(
     *	    array(
     *		'id'		=> '',	【必须】int XXX单主键ID
     *		'order_id'	=> '',	【必须】int 订单ID
     *		'business_key'	=> '',	【必须】int 业务类型值
     *		'create_time'	=> '',	【必须】int 创建时间
     *		'update_time'	=> '',	【必须】int 更新时间
     *		'status'	=> '',	【必须】int 状态值；0：aaa；1：bbb；2：ccc
     *	    )
     * )
     */
    public function get_list($where=[],$additional=[]){
	// 处理可选参数的默认值
	
	// 执行查询
	
	// 返回结果
	return [
	    [
		'id' => '1',
		'order_id' => '1',
		'business_key' => '1',
		'create_time' => '1510340741',
		'update_time' => '1510340741',
		'status' => '0',
	    ]
	];
    }
    /**
     * 根据条件，查询 XXX单列表
     * @param array $where		查看 get_list() 定义
     * @param array $additional		查看 get_list() 定义
     * @return int  符合条件的记录总数
     */
    public function count($where=[],$additional=[]){
	// 处理可选参数的默认值
	
	// 执行查询
	
	// 返回结果
	return [
	    [
		'id' => '1',
		'order_id' => '1',
		'business_key' => '1',
		'create_time' => '1510340741',
		'update_time' => '1510340741',
		'status' => '0',
	    ]
	];
    }
    
    
    /**
     * 根据 XXX单 主键，查询基本信息
     * @param type $xx_id	XX单主键ID
     * @return mixed	false: 失败； array：查询到结果
     * [
     *	    'id' => '1',
     *	    'order_id' => '1',
     *      'business_key' => '1',
     *	    'create_time' => '1510340741',
     *	    'update_time' => '1510340741',
     *	    'status' => '0',
     * ]
     */
    public function get_info( $xx_id ){
	substr();
	return [
	    'id' => '1',
	    'order_id' => '1',
	    'business_key' => '1',
	    'create_time' => '1510340741',
	    'update_time' => '1510340741',
	    'status' => '0',
	];
    }
    
    
    /**
     * 创建订单
     * @param array $user_info	    【必须】用户ID+手机号
     * [
     *	    'user_id' => '',	    【必须】int	用户ID
     *	    'mobile' => '',	    【必须】string 手机号
     * ]
     * @param array $sku_info	    【必须】
     * [
     *	    'sku_id' => '',	    【必须】int SKU ID
     *	    'spu_id' => '',	    【必须】int SPU ID
     *	    'brand_id' => '',	    【必须】int 品牌ID
     *	    'category_id' => '',    【必须】int 分类ID
     *	    'sku_name' => '',	    【必须】string  商品名称
     *	    'zuqi' => '',	    【必须】int	租期； 12:12期；6:6期；3:3期
     *	    'shop_price' => '',	    【必须】int	月租金额（元）
     * 	    '保险' => '',	    【必须】int	意外保险金（元）
     * 	    'yajin' => '',	    【必须】int	押金（元）
     * 	    'yajin_free' => '',	    【必须】int	免押金额（元）
     *  
     * ]
     * @param array $address_info   【必须】收货地址信息
     * [
     *	    'name' => '',	【必须】int 
     *	    'mobile' => '',	【必须】stromg 
     *	    'address' => '',	【必须】string	详细地址信息
     * 	    'province_id' => '',【必须】int	省份ID
     * 	    'city_id' => '',	【必须】int	城市ID
     * 	    'country_id' => '',【必须】int	区县ID
     * 
     * ]
     * @param int $pay_channel_id   【必须】支付渠道ID
     * @return int  false：创建订单失败； int：订单创建成功，返回订单ID
     */
    public function create( $user_info, $sku_info, $address_info, $pay_channel_id=0 ){
	// 参数过滤
	
	try {
	    // 开启事务
	} catch (\Exception $exc) {
	    // 关闭事务
	}
	// 提交事务

	// 记录订单日志

	return 1;
    }
    
    
    
}
