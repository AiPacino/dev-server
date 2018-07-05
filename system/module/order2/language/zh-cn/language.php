<?php
/**
 *      订单语言包
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
return array(
	// -----   订单
	'order_not_exist'                 => '该订单不存在',
	
	'order_submit_success'            => '订单提交成功',
	'order_submit_error'              => '订单提交失败',
	
	'order_no_not_null'               => '订单号不能为空',
	'order_not_empty'				  => '主订单号不能为空',	
	'order_no_error'                  => '订单号有误',
	'order_no_already_exist'          => '订单号已存在',
	
	'order_status_not_null'           => '订单状态不能为空',
	'order_status_error'              => '订单状态只能为正整数',
	
	'order_source_not_null'           => '订单来源不能为空',
	'order_source_error'              => '订单来源只能为正整数',
	
	'order_member_id_not_null'        => '会员ID不能为空',
	'order_member_id_error'           => '会员ID只能为正整数',
	
	'order_delivery_method_not_null'  => '配送方式不能为空',
	'order_delivery_method_error'     => '配送方式只能为正整数',
	
	'order_pay_status_not_null'       => '支付状态不能为空',
	'order_pay_status_error'          => '支付状态只能为正整数',
	
	'order_delivery_status_not_null'  => '配送状态不能为空',
	'order_delivery_status_error'     => '配送状态只能为正整数',
	
	'order_payable_amount_not_null'   => '商品总额不能为空',
	'order_payable_amount_error'      => '商品总额只能为实数(保留两位小数)',
	
	'order_real_amount_not_null'      => '应付总额不能为空',
	'order_real_amount_error'         => '应付总额只能为实数(保留两位小数)',
	
	'order_address_name_not_null'     => '收货人姓名不能为空',
	'order_address_mobile_not_null'   => '收货人手机不能为空',
	
	'order_address_district_not_null' => '收货人地区ID不能为空',
	'order_address_district_error'    => '收货人地区ID只能为正整数',
	
	'order_address_address_not_null'  => '收货人详细地址不能为空',
	'delivery_template_error'		  => '您所选择的收货地址暂时无法配送',

    'order_pay_sn_not_null'            => '订单支付号不能为空',
    'order_pay_sn_already_exist'      => '订单支付号已经存在',
    'order_pay_money_error'            => '订单支付总额只能为实数(保留两位小数)',
	
	// ---------- 购物车
	'cart_add_success'    => '购物车添加成功',
	'cart_update_success' => '购物车修改成功',
	'cart_delete_success' => '购物车删除成功',
	'cart_clear_success'  => '购物车清空成功',
	'cart_key_error'      => '购物车加密Key为32位',
	
	// ---------- 当前状态
	'create'              => '创建订单',
	'pay'                 => '已付款',
	'confirm'             => '已确认',
	'delivery'            => '已发货',
	'completion'          => '已完成',
	'cancel'              => '已取消',
	'recycle'             => '已回收',
	'delete'              => '已删除',
	'return'              => '已退货',
	'refund'              => '已退款',
	
	// ---------- 待操作状态
	'wait_pay'            => '待付款',
	'wait_confirm'        => '待确认',
	'wait_delivery'       => '待发货',
	'wait_completion'     => '待确认完成',

	'cancellation_order_success'   	=> '订单作废操作成功',
	'order_create_success'     		=> '订单创建成功',
	'operate_type_empty'			=> '订单操作类型不能为空',
	'pay_type_require'				=> '订单支付类型不能为空',
	'pay_type_error'				=> '订单支付类型有误',
	'pay_status_require'			=> '支付状态必须为布尔值',
	'confirm_status'				=> '确认状态必须为布尔值',
	'delivery_status_require'		=> '发货状态必须为布尔值',
	'finish_status_require'			=> '完成状态必须为布尔值',
	'sku_amount_require'			=> '商品总额不能为空',
	'sku_amount_currency'			=> '商品总额有误',	
	'delivery_amount_currency'		=> '配送总额只能为实数(保留两位小数)',
	'balance_amount_currency'		=> '余额付款总额只能为实数(保留两位小数)',
	'address_detail_require'		=> '收货人详细地址不能为空',
	'type_not_empty' 				=> '操作类型不能为空',
	'operator_type_not_null'		=> '操作者类型不能为空',
	'operator_type_numbre'			=> '操作者类型为大于零的正整数',
	'user_id_not_empty'				=> '操作者ID不能为空',
	'user_name_not_null'			=> '操作者名称不能为空',
	'user_id_require'     			=> '操作者ID为大于零的正整数',
	'order_parame_empty'			=> '订单参数不能为空',
	'order_logistics_not_exist'		=> '订单物流信息不存在',
	'order_log_empty'				=> '订单日志信息不能为空',
	'parent_order_no_not_exist'		=> '主订单信息不存在',
	'order_require'					=> '主订单号必须存在',
	'order_sub_require'				=> '子订单号必须存在',
	'child_order_no_empty'			=> '子订单号不能为空',
	'order_no_pay'     				=> '该订单未支付',
	'order_dont_operate'			=> '该订单不能执行该操作',
	'order_goods_not_exist'			=> '该订单商品不存在',
	'cancel_order_success'     		=> '取消订单成功',
	'delete_order_error'			=> '删除订单失败',
	'order_goods_empty'				=> '请选择订单商品',
	'order_not_pay_status'     		=> '抱歉，该订单当前不是支付状态',
	'no_promission_view'     		=> '抱歉，您无法查看此订单',
	'dont_edit_order_amount'		=> '当前状态不能修改订单应付总额',
	'submit_parameters_error'     	=> '提交参数有误',
	'express_identifying_no_exist' 	=> '物流标识图片不存在',
	'deliver_identify_not_exist'	=> '物流标识不存在',
	'logistics_no_msg'				=> '物流单暂无结果',
	'logistics_name_not_empty'		=> '物流名称不能为空',
	'logistics_not_exist'			=> '物流单号不能为空',
	'logistics_identifi_not_empty'	=> '物流标识不能为空',
	'logistics_name_exist'			=> '物流名称已存在',
	'logistics_insurance_amount_empty'=> '请填写物流保价金额',
	'logistics_id_empty'			=> '物流ID不能为空',
	'logistics_id_require'			=> '物流ID必须为正整数',
	'logistics_empty'				=> '请选择配送物流',
	'logistics_not_exist'			=> '该物流不存在',
	'delete_logistics_error'		=> '删除物流失败',
	'delete_logistics_id_empty'		=> '要删除的物流ID不能为空',
	'clearing_goods_no_exist'     	=> '结算商品不存在',
	'pay_deal_sn_empty' 			=> '请填写支付交易号',
	'pay_success'     				=> '确认付款成功',
	'record_no_exist'     			=> '该记录不存在',
	'parameter_empty'     			=> '参数不能为空',
	'delete_parame_error'			=> '要删除的参数有误',
	'merchant_id_empty'				=> '商家ID不能为空',
	'area_not_exist'				=> '地区不存在',
	'request_parame_error'			=> '请求的参数有误！',
	'edit_field_empty'				=> '要更改的字段不能为空',
	'edit_value_empty'				=> '要更改的值不能为空',
	'shipment_sn_id_not_exist'		=> '发货单ID不能为空',
	'waybill_sn_not_exist'			=> '运单号不存在',
	'waybill_sn_empty'				=> '运单号不能为空',
	'inquire_error'					=> '查询失败，请稍候重试',
	'connector_error'				=> '接口出现异常',
	'buy_number_require'			=> '购买数量必须为正整数',
	'goods_not_exist'				=> '该购物车商品不存在',
	'delete_parame_empty'			=> '要删除的参数不能为空',
	'shopping_cart_empty'			=> '购物车已为空',
	'refund_money_require'			=> '退款金额必须大于0',
	'return_cause_empty'			=> '请选择退货原因',
	'repeat_submit'					=> '您已申请售后，请勿重复提交',
	'operate_type_error'			=> '操作类型有误',	
	'operate_record_not_exist'		=> '要操作的记录不存在',
	'record_ban_operate'			=> '该记录禁止该操作',
	'shipping_address_empty'		=> '请选择收货地址',
	'invoice_head_empty'			=> '请填写发票抬头',
	'pay_way_empty'					=> '请选择支付方式',	
	'pay_ebanks_error'				=> '选择的支付网银有误',	
	'model_id_require'				=> '发货单模版ID必须为正整数',
	'no_found_data'					=> '未查询到相关数据',
	'insure_money_require'			=> '保价金额必须为数字',
	'sort_require'					=> '排序必须为正整数',

	'thsi_operator_a'				=> '当前操作配送中',
	'thsi_operator_b'				=> '当前操作配送失败',
	'thsi_operator_c'				=> '当前操作配送成功',
	'thsi_operator_d'				=> '当前操作待配送',
	'buyer_id_not_null'				=> '买家ID不能为空',
	'buyer_id_error'				=> '买家ID有误',
	'amount_require'				=> '退款总额只能为实数(保留两位小数)',
	'sku_id_not_null'				=> '产品ID不能为空',
	'sku_id_require'				=> '产品ID必须是正整数',
	'buy_nums_require'				=> '购买数量不能为空',
	'buy_nums_number'				=> '购买数量必须是正整数',
	'content_require' 				=> '发货单模版内容不能为空',
	'msg_require'					=> '订单跟踪内容不能为空',

	'name_empty'					=> '运费模板名称不能为空',
	'template_type_error'			=> '请选择运费模板计费类型',
	'template_empty'				=> '地区模板信息不能为空',
	'delivery_template_not_exist' 	=> '运费模板不存在或者未开启',
	'delivery_default_cannot_delete' => '默认运费模板不能删除'
	
);