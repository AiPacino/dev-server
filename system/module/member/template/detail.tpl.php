<?php include template('header', 'admin'); ?>

<style>
	.order-edit-btn a, .order-edit-btn .a { float: left; display: inline-block; margin: 5px; padding: 6px 20px; height: 30px; line-height: 18px; font-family: "微软雅黑"; border: 0; border-radius: 3px; color: #fff; font-size: 12px; text-align: center; cursor: pointer; }
</style>
<body>
<div class="fixed-nav layout">
	<ul>
		<li class="first">用户详情</li>
		<li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
	<!--用户概况-->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
		<tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
			<th class="text-left padding-big-left">
				用户概况
				<?php if($create_delivery){?>
					<div class="order-edit-btn fr">
						<a class="bg-main btn" href ='<?php echo url('order2/delivery/create',array('order_id' => $order['order_id'])); ?>' data-iframe="true" data-iframe-width="300">确认订单</a>
					</div>
				<?php }?>
			</th>
		</tr>

		<tr class="border">
			<td class="padding-big-left padding-big-right">
				<!-- 人 -->
				<table cellpadding="0" cellspacing="0" class="layout">
					<tbody>
					<tr class="line-height-40">
						<th class="text-left" colspan="1">
							用户ID：<?php echo ($user_info['id']); ?>
						</th>
						<th class="text-left" colspan="1">
							手机号：<?php echo ($user_info['mobile']); ?>
						</th>

						<th class="text-left" colspan="1">
							用户下单解封：
                            <span class="td-con">

                                <?php if($user_info['block'] ==0){?>
									未封&emsp;|
								<?php }else{?>
									<a onclick="order_action.dialog({width:350,url:'<?php echo url('member/member/deblocking',array('id' => $user_info['id'])); ?>'});" href="javascript:;"><font color="red">解封</font></a>&emsp;|
								<?php }?>
								&emsp;<a href="<?php echo url('member/member/deblocking_record',array('id' => $user_info['id'])); ?>" data-iframe="true" data-iframe-width="600">解封记录</a>

                            </span>


						</th>


					</tr>
					<tr class="line-height-40">
						<td class="text-left">认证平台：<?php echo $user_info['certified_platform_name']; ?></td>
						<td class="text-left">信用分：<?php echo $user_info['credit']; ?></td>
						<td class="text-left">姓名：<?php echo $user_info['realname']; ?></td>
						<td class="text-left">身份证：<?php echo $user_info['cert_no']; ?></td>
					</tr>
					<tr class="line-height-40">
						<td class="text-left">是否通过人脸识别：<?php echo $user_info['face']; ?></td>
						<td class="text-left">芝麻风控产品集联合结果：<?php echo $user_info['risk']; ?></td>
						<td class="text-left">信用分获取时间：<?php echo $user_info['credit_time']; ?></td>
						<td class="text-left">是否锁定：<?php echo $user_info['islock']; ?></td>
					</tr>

					<tr class="line-height-40">
						<td class="text-left">无法下单原因：<?php echo $user_info['order_remark']; ?></td>
						<td class="text-left">签约中的代扣协议码：<?php echo $user_info['withholding_no']; ?></td>
						<td class="text-left">租机渠道：<?php echo $user_info['appid']; ?></td>
					</tr>


					<tr class="line-height-40">
						<td class="text-left">会员等级：<?php echo $user_info['group_name']; ?></td>
						<td class="text-left">可用积分：<?php echo $user_info['integral']; ?></td>
						<td class="text-left">可用余额：<?php echo $user_info['money']; ?></td>
						<td class="text-left">冻结资金：<?php echo $user_info['frozen_money']; ?></td>

					</tr>
					<tr class="line-height-40">
						<td class="text-left">经验值：<?php echo $user_info['exp']; ?></td>
						<td class="text-left">注册IP：<?php echo $user_info['register_ip']; ?></td>
						<td class="text-left">电子邮件：<?php echo $user_info['email']; ?></td>
					</tr>

					<tr class="line-height-40">
						<td class="text-left">登录次数：<?php echo $user_info['login_num']; ?></td>
						<td class="text-left">注册时间：<?php echo $user_info['register_time']; ?></td>
						<td class="text-left">最后登录时间：<?php echo $user_info['login_time']; ?></td>
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

		</tr>

		<?php foreach($address_list as $val){?>
			<tr class="border">
				<td class="padding-big-left padding-big-right">
					<table cellpadding="0" cellspacing="0" class="layout">
						<tbody>
						<tr class="line-height-40">
							<td class="text-left w25">收货人姓名：<?php echo $val['name']; ?></td>
							<td class="text-left w25">电话号码：<?php echo $val['mobile']; ?></td>
							<td class="text-left w50">详细地址：<?php echo $val['address']; ?></td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
		<?php }?>

		</tbody>
	</table>


	<div class="padding-tb">
		<input class="button margin-left bg-gray border-none" id="closebtn" type="button" value="返回" />
	</div>
</div>
<?php include template('footer', 'admin'); ?>
<script>
	$(function(){
		try {
			var dialog = top.dialog.get(window);
		} catch (e) {
			return;
		}
		var $val=$("textarea").first().text();
		$("textarea").first().focus().text($val);
		dialog.title('用户详情');
		dialog.reset();     // 重置对话框位置


		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
<script>
	$(".detail_iframe").load(function(){
		var mainheight = $(this).contents().find("body").height()+20;
		$(this).height(mainheight);
	});
</script>