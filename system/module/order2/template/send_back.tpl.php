<?php include template('header','admin');?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order_action.js"></script>
<script type="text/javascript">
	var order = <?php echo json_encode($order); ?>;
	$(document).ready(function(){
		order_action.init();
	});
</script>

<body>
	<div class="fixed-nav layout">
		<ul>
			<li class="first">订单详情</li>
			<li class="spacer-gray"></li>
		</ul>
		<div class="hr-gray"></div>
	</div>
	<div class="content padding-big have-fixed-nav">
		<!--订单概况-->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-left padding-big-left">
						订单概况
						<div class="order-edit-btn fr">

							<!-- 确认收货 -->
							<button <?php if ($order['pay_type'] == 1 && $order['_status']['now'] == 'pay' || $order['pay_type'] == 2 && $order['_status']['now'] == 'create') : ?>class="bg-main" onclick="order_action.confirm('<?php echo url("order/admin_order/confirm",array("sub_sn" => $order['sub_sn'])); ?>');"<?php else:?>class="bg-gray"<?php endif; ?>>确认订单</button>
							<!-- 取消订单 -->
							<button <?php if($order['delivery_status'] == 0 && $order['status'] == 1): ?>class="bg-main" onclick="order_action.order(2,'<?php echo url("order/admin_order/cancel",array("sub_sn" => $order['sub_sn'])); ?>');"<?php else: ?>class="bg-gray"<?php endif; ?>>取消订单</button>

						</div>
					</th>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<tr class="line-height-40">
									<th class="text-left">
										订单号：<?php echo $order['order_no'];?>&emsp;
										<?php if($order['source']==1) { ?>
											<i class="ico_order_mobile"></i>
										<?php }elseif($order['source']==2) { ?>
											<i class="ico_order_wechat"></i>
										<?php }else { ?>
											<i class="ico_order"></i>
										<?php } ?>
									</th>
									<th class="text-left">支付方式：<?php echo ($order['pay_type']==2) ? '货到付款' : '在线支付'?></th>
									<th class="text-left">订单状态：<?php echo ch_status($order['_status']['now']);?></th>
									<th class="text-left">订单总金额：<?php echo '2000';?></th>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<!--订单详情-->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-left padding-big-left">订单详情</th>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<tr class="line-height-40">
									<td class="text-left">会员账号：<?php echo $order['_member']['username']; ?></td>
									<td class="text-left">支付类型：<?php echo ($order['_main']['pay_method']) ? $order['_main']['pay_method'] : '--';?></td>
									<td class="text-left">支付账号：<?php echo $order['_member']['username']; ?></td>
									<td class="text-left">下单时间：<?php echo date('Y-m-d H:i:s',$order['system_time']); ?></td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left">支付时间：<?php echo ($order['pay_time']) ? date('Y-m-d H:i:s',$order['pay_time']) : '--' ?></td>
									<td class="text-left">是否同时支付押金：<?php echo ($order['pay_time']) ? date('Y-m-d H:i:s',$order['pay_time']) : '--' ?></td>
									<td class="text-left">发货时间：<?php echo ($order['delivery_time']) ? date('Y-m-d H:i:s',$order['delivery_time']) : '--' ?></td>
									<td class="text-left">收货时间：<?php echo ($order['finish_time']) ? date('Y-m-d H:i:s',$order['finish_time']) : '--' ?></td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left">租期开始时间：<?php echo ($order['pay_time']) ? date('Y-m-d H:i:s',$order['pay_time']) : '--' ?></td>
									<td class="text-left">租期结束时间：<?php echo ($order['pay_time']) ? date('Y-m-d H:i:s',$order['pay_time']) : '--' ?></td>
									<td class="text-left">租期：<?php echo '12月';?></td>
									<td class="text-left">剩余租借天数：<?php echo '100天';?></td>

								</tr>
								<tr class="line-height-40">
									<td class="text-left">租期结束后用户选择：<?php echo ($order['pay_time']) ? date('Y-m-d H:i:s',$order['pay_time']) : '--' ?></td>
									<td class="text-left">买断支付时间：<?php echo ($order['pay_time']) ? date('Y-m-d H:i:s',$order['pay_time']) : '--' ?></td>
									<td class="text-left">用户协议编号：<?php echo '0099191jdehh';?></td>
									<td class="text-left">收到用户协议时间：<?php echo ($order['finish_time']) ? date('Y-m-d H:i:s',$order['finish_time']) : '--' ?></td>
								</tr>
								<?php runhook('admin_order_send_time',$order['send_time']);?>
							</tbody>
						</table>
					</td>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<tr class="line-height-40">
									<th class="text-left" colspan="3">
										订单总额：￥<?php echo ($order['real_price']);?>

									</th>
								</tr>
								<tr class="line-height-40">
									<td class="text-left">租金总额：￥<?php echo $order['sku_price'];?></td>
									<td class="text-left">每月租金：￥<?php echo $order['delivery_price'];?></td>
									<td class="text-left">配送费用：￥<?php echo $order['delivery_price'];?></td>
									<td class="text-left">发票税额：<?php echo ($order['_main']['invoice_tax']) ? '￥'.$order['_main']['invoice_tax'] : '-';?></td>
								</tr>

								<!-- <tr class="line-height-40">
									<td class="text-left">商品折扣：￥<?php echo $order['discount'];?></td>
									<td class="text-left">优惠券减免：￥<?php echo $order['coupons'];?></td>
									<td class="text-left"></td>
								</tr> -->
							</tbody>
						</table>
					</td>
				</tr>

				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
							<tr class="line-height-40">
								<th class="text-left" colspan="3">
									实收押金：￥<?php echo ($order['real_price']);?>

								</th>
							</tr>
							<tr class="line-height-40">
								<td class="text-left">应收押金：￥<?php echo $order['sku_price'];?></td>
								<td class="text-left">是否需要租金：￥<?php echo '是';?></td>

							</tr>

							<!-- <tr class="line-height-40">
									<td class="text-left">商品折扣：￥<?php echo $order['discount'];?></td>
									<td class="text-left">优惠券减免：￥<?php echo $order['coupons'];?></td>
									<td class="text-left"></td>
								</tr> -->
							</tbody>
						</table>
					</td>
				</tr>

				<tr class="border">
					<td class="padding-big-left padding-big-right line-height-40">
						<span class="text-main">订单备注：</span><?php echo  '没事撑得，呵呵！'; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<!--收货人信息-->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-left padding-big-left">租机人信息</th>
					<th class="text-right padding-big-right">
						<?php if ($status !== 1): ?>
			              <a id="add-address" class="bg-gray-edit" href="<?php echo url('address_edit',array("order_no"=>$order['order_no']));?>" data-iframe="true" data-iframe-width="680">编辑</a>
			            <?php endif; ?>
			        </th>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<tr class="line-height-40">
									<td class="text-left w25">姓名：<?php echo $order['_main']['address_name'];?></td>
									<td class="text-left w25">会员账号：<?php echo $order['_main']['address_mobile'];?></td>
									<td class="text-left w50">身份证号：<?php echo $order['_main']['address_detail']; ?></td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left w25">发票抬头：<?php echo ($order['_main']['invoice_title']) ? $order['_main']['invoice_title'] : '-';?></td>
									<td class="text-left w25">发票内容：<?php echo ($order['_main']['invoice_content']) ? $order['_main']['invoice_content'] : '-';?></td>
									<td class="text-left w50">&nbsp;</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>

		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
			<tr class="bg-gray-white line-height-40 border-bottom">
				<th class="text-left padding-big-left">收货人信息</th>
				<th class="text-right padding-big-right">
					<?php if ($status !== 1): ?>
						<a id="add-address" class="bg-gray-edit" href="<?php echo url('address_edit',array("order_no"=>$order['order_no']));?>" data-iframe="true" data-iframe-width="680">编辑</a>
					<?php endif; ?>
				</th>
			</tr>
			<tr class="border">
				<td class="padding-big-left padding-big-right">
					<table cellpadding="0" cellspacing="0" class="layout">
						<tbody>
						<tr class="line-height-40">
							<td class="text-left w25">收货人姓名：<?php echo $order['_main']['address_name'];?></td>
							<td class="text-left w25">电话号码：<?php echo $order['_main']['address_mobile'];?></td>
							<td class="text-left w50">详细地址：<?php echo $order['_main']['address_detail']; ?></td>
						</tr>
						<tr class="line-height-40">
							<td class="text-left w25">发票抬头：<?php echo ($order['_main']['invoice_title']) ? $order['_main']['invoice_title'] : '-';?></td>
							<td class="text-left w25">发票内容：<?php echo ($order['_main']['invoice_content']) ? $order['_main']['invoice_content'] : '-';?></td>
							<td class="text-left w50">&nbsp;</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
			</tbody>
		</table>

		<!-- 商品信息 -->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-left padding-big-left">商品信息</th>
				</tr>
				<tr class="border">
					<td>
						<div class="table resize-table high-table clearfix">
							<div class="tr">
								<span class="th" data-width="20">
									<span class="td-con">商品信息</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">商品价格</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">成交金额</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">优惠券</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">实付金额</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">手机信息</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">租期</span>
								</span>
								<span class="th" data-width="8">
									<span class="td-con">成色</span>
								</span>
								<span class="th" data-width="8" data-min="20">
									<span class="td-con">操作</span>
								</span>
							</div>
							<?php foreach ($order['_skus'] as $delivery_id => $skus) : ?>
								<div class="order-detail-merge layout">
									<?php foreach ($skus as $key => $sku): ?>
										<div class="tr">
											<div class="td">
												<div class="td-con td-pic text-left">
													<span class="pic"><img src="<?php echo $sku['sku_thumb'] ?>"></span>
													<span class="title text-ellipsis txt margin-none padding-small-top"><a href="<?php echo url('goods/index/detail',array('sku_id' => $sku['sku_id'])) ?>" target="_blank"><?php echo $sku['sku_name'] ?></a></span>
													<span class="icon">
														<?php foreach ($sku['sku_spec'] as $spec): ?>
															<em class="text-main"><?php echo $spec['name'] ?>：</em><?php echo $spec['value'] ?>&nbsp;
														<?php endforeach ?>
														<!-- <em class="text-main">处理时间：2015-05-03 10:12:12</em> -->
														<!-- <a href="javascript:;">查看详情</a> -->
														<br/>
														<?php if ($sku['is_give'] == 1) : ?>
															<span class="bg-blue text-white padding-small-left padding-small-right fl margin-small-top text-lh-little">赠品</span>
														<?php endif; ?>
														<?php if ($sku['promotion']) : ?>
															<p class="text-gray text-ellipsis"><span class="bg-red text-white padding-small-left padding-small-right fl margin-small-top text-lh-little margin-small-right"><?php echo ch_prom($sku['promotion']['type']); ?></span><?php echo $sku['promotion']['title']; ?></p>
														<?php endif; ?>
													</span>
													<!-- <i class="return-ico"><img src="../images/ico_returning.png" height="60"></i> -->
												</div>
											</div>
											<div class="td"><span class="td-con">￥<?php echo $sku['sku_price'];?></span></div>
											<div class="td"><span class="td-con">￥<?php echo $sku['real_price'];?></span></div>
											<div class="td"><span class="td-con"><?php echo $sku['buy_nums'] ?></span></div>
											<div class="td"><span class="td-con"><?php echo $sku['delivery_template_name'] ? $sku['delivery_template_name'] : '-' ?></span></div>
										
											<div class="td detail-logistics" <?php if ($sku['_is_delivery'] == 'true'): ?>style="padding: 15px 15px 0px 0px;"<?php endif ?>>
												<?php if ($delivery_id > 0): ?>
													<div class="order-edit-btn fr">
														<?php if ($sku['delivery_status'] == 1): ?>
														<button class="bg-main look-log" data-did="<?php echo $delivery_id; ?>">查看物流</button>
														<button class="bg-main" onclick="order_action.delivery('<?php echo url("order/admin_order/delivery_edit",array("sub_sn" => $sku["sub_sn"],"delivery_id"=>$delivery_id)); ?>');">修改物流信息</button>
														<?php else: ?>
														<a class="button bg-sub text-ellipsis look-log" href="javascript:;" data-did="<?php echo $delivery_id; ?>">查看物流</a>
														<?php endif ?>
													</div>
												<?php else: ?>
													<a class="button bg-gray text-ellipsis" href="javascript:;">暂未发货</a>
												<?php endif ?>
											</div>
										</div>
									<?php endforeach ?>
								</div>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- 订单日志 -->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>	
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-left padding-big-left">订单日志</th>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<?php foreach ($order_logs as $k => $log) : ?>
									<tr class="line-height-40">
										<td class="text-left">
											<?php if($log['operator_type']==1){echo '系统';} elseif($log['operator_type']==2){echo '买家';} ?>&emsp;
											<?php echo $log['operator_name'] ?>&emsp;于&emsp;
											<?php echo date('Y-m-d H:i:s' ,$log['system_time']); ?>&emsp;
											「<?php echo $log['action']; ?>」&emsp;
											<?php if ($log['msg']) : ?>操作备注：<?php echo $log['msg']; endif;?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>

<!--保险信息-->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
			<tr class="bg-gray-white line-height-40 border-bottom">
				<th class="text-left padding-big-left">保险信息</th>
			</tr>
			<tr class="bg-gray-white line-height-30 border-bottom">
				<th class="text-left padding-big-left">保险剩余次数:</th>
				<th class="text-left padding-big-left">维修次数:</th>
			</tr>
			<tr class="border">
				<td>
					<div class="table resize-table high-table clearfix">
						<div class="tr">
								<span class="th" data-width="30">
									<span class="td-con">保险适用记录</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">用户申请时间</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">维修收到时间</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">维修邮回时间</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">用户收到时间</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">维修状态</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">维修内容</span>
								</span>
								<span class="th" data-width="10">
									<span class="td-con">维修适用额度</span>
								</span>

						</div>
						<?php foreach ($order['_skus'] as $delivery_id => $skus) : ?>
							<div class="order-detail-merge layout">
								<?php foreach ($skus as $key => $sku): ?>
									<div class="tr">
										<div class="td">
											<div class="td-con td-pic text-left">
												<span class="pic"><img src="<?php echo $sku['sku_thumb'] ?>"></span>
												<span class="title text-ellipsis txt margin-none padding-small-top"><a href="<?php echo url('goods/index/detail',array('sku_id' => $sku['sku_id'])) ?>" target="_blank"><?php echo $sku['sku_name'] ?></a></span>
													<span class="icon">
														<?php foreach ($sku['sku_spec'] as $spec): ?>
															<em class="text-main"><?php echo $spec['name'] ?>：</em><?php echo $spec['value'] ?>&nbsp;
														<?php endforeach ?>
														<!-- <em class="text-main">处理时间：2015-05-03 10:12:12</em> -->
														<!-- <a href="javascript:;">查看详情</a> -->
														<br/>
														<?php if ($sku['is_give'] == 1) : ?>
															<span class="bg-blue text-white padding-small-left padding-small-right fl margin-small-top text-lh-little">赠品</span>
														<?php endif; ?>
														<?php if ($sku['promotion']) : ?>
															<p class="text-gray text-ellipsis"><span class="bg-red text-white padding-small-left padding-small-right fl margin-small-top text-lh-little margin-small-right"><?php echo ch_prom($sku['promotion']['type']); ?></span><?php echo $sku['promotion']['title']; ?></p>
														<?php endif; ?>
													</span>
												<!-- <i class="return-ico"><img src="../images/ico_returning.png" height="60"></i> -->
											</div>
										</div>
										<div class="td"><span class="td-con">￥<?php echo $sku['sku_price'];?></span></div>
										<div class="td"><span class="td-con">￥<?php echo $sku['real_price'];?></span></div>
										<div class="td"><span class="td-con"><?php echo $sku['buy_nums'] ?></span></div>
										<div class="td"><span class="td-con"><?php echo $sku['delivery_template_name'] ? $sku['delivery_template_name'] : '-' ?></span></div>

										<div class="td detail-logistics" <?php if ($sku['_is_delivery'] == 'true'): ?>style="padding: 15px 15px 0px 0px;"<?php endif ?>>
											<?php if ($delivery_id > 0): ?>
												<div class="order-edit-btn fr">
													<?php if ($sku['delivery_status'] == 1): ?>
														<button class="bg-main look-log" data-did="<?php echo $delivery_id; ?>">查看物流</button>
														<button class="bg-main" onclick="order_action.delivery('<?php echo url("order/admin_order/delivery_edit",array("sub_sn" => $sku["sub_sn"],"delivery_id"=>$delivery_id)); ?>');">修改物流信息</button>
													<?php else: ?>
														<a class="button bg-sub text-ellipsis look-log" href="javascript:;" data-did="<?php echo $delivery_id; ?>">查看物流</a>
													<?php endif ?>
												</div>
											<?php else: ?>
												<a class="button bg-gray text-ellipsis" href="javascript:;">暂未发货</a>
											<?php endif ?>
										</div>
									</div>
								<?php endforeach ?>
							</div>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
			</tbody>
		</table>


		<!-- 物流信息 -->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
			<tr class="bg-gray-white line-height-40 border-bottom">
				<th class="text-left padding-big-left">物流信息</th>
			</tr>
			<tr class="border">
				<td class="padding-big-left padding-big-right">
					<table cellpadding="0" cellspacing="0" class="layout">
						<tbody>
						<?php foreach ($order_logs as $k => $log) : ?>
							<tr class="line-height-40">
								<td class="text-left">
									<?php if($log['operator_type']==1){echo '系统';} elseif($log['operator_type']==2){echo '买家';} ?>&emsp;
									<?php echo $log['operator_name'] ?>&emsp;于&emsp;
									<?php echo date('Y-m-d H:i:s' ,$log['system_time']); ?>&emsp;
									「<?php echo $log['action']; ?>」&emsp;
									<?php if ($log['msg']) : ?>操作备注：<?php echo $log['msg']; endif;?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</td>
			</tr>
			</tbody>
		</table>

		<!-- 订单日志 -->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
			<tr class="bg-gray-white line-height-40 border-bottom">
				<th class="text-left padding-big-left">订单日志</th>
			</tr>
			<tr class="border">
				<td class="padding-big-left padding-big-right">
					<table cellpadding="0" cellspacing="0" class="layout">
						<tbody>
						<?php foreach ($order_logs as $k => $log) : ?>
							<tr class="line-height-40">
								<td class="text-left">
									<?php if($log['operator_type']==1){echo '系统';} elseif($log['operator_type']==2){echo '买家';} ?>&emsp;
									<?php echo $log['operator_name'] ?>&emsp;于&emsp;
									<?php echo date('Y-m-d H:i:s' ,$log['system_time']); ?>&emsp;
									「<?php echo $log['action']; ?>」&emsp;
									<?php if ($log['msg']) : ?>操作备注：<?php echo $log['msg']; endif;?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</td>
			</tr>
			</tbody>
		</table>






		<div class="padding-tb">
			<input class="button margin-left bg-gray border-none" type="button" value="返回" />
		</div>
	</div>
<?php include template('footer','admin');?>
<script>
$('.table').resizableColumns();

$(".look-log").live('click',function(){
	if($(this).hasClass('bg-gray')) return false;
	$(this).removeClass('bg-sub').addClass('bg-gray').html("加载中...");
	var $this = $(this);
	var txt = '';
	$.getJSON('<?php echo url("order/cart/get_delivery_log") ?>', {o_d_id: $(this).attr('data-did')}, function(ret) {
		if (ret.status == 0) {
			alert(ret.message);
			return false;
		}
		if (ret.result.logs.length > 0) {
			$.each(ret.result.logs,function(k, v) {
				txt += '<p>'+ v.add_time +'&nbsp;&nbsp;&nbsp;&nbsp;'+ v.msg +'</p>';
			});
			top.dialog({
				content: '<div class="logistics-info padding-big bg-white text-small"><p class="border-bottom border-dotted padding-small-bottom margin-small-bottom"><span class="margin-big-right">物流公司：'+ret.result.delivery_name+'</span>&nbsp;&nbsp;物流单号：'+ret.result.delivery_sn+'</p>'+ txt +'</div>',
				title: '查看物流信息',
				width: 680,
				okValue: '确定',
				ok: function(){
					$this.removeClass('bg-gray').addClass('bg-sub').html("查看物流");
				},
				onclose: function(){
					$this.removeClass('bg-gray').addClass('bg-sub').html("查看物流");
				}
			})
			.showModal();
		}
	});
})
</script>
