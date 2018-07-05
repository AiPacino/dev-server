<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
    <?php if(!$inner){?>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">退款单详情</li>
	    <li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
    </div>
    <?php }?>
    <div class="content <?php if(!$inner){echo 'padding-big have-fixed-nav';}?>">
	<!--退款详情-->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">退款详情</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left">退款状态：<?php echo $refund_info['refund_status_show']; ?></th>
				    <th class="text-left">订单金额：<?php echo $refund_info['payment_amount_show']; ?></th>
				    
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">客服操作人员：<?php echo $refund_info['should_admin_name']; ?></td>
				    <td class="text-left">应退金额：   <?php echo $refund_info['should_amount_show']; ?></td>
                      <td class="text-left">应退备注：   <?php echo $refund_info['should_remark']; ?></td>
				</tr>
				<tr class="line-height-40">
				<th class="text-left">财务操作人员：<?php echo $refund_info['admin_name']; ?></th>
				    <td class="text-left">退款金额：<?php echo $refund_info['refund_amount_show']; ?></td>
				    <td class="text-left">退款备注：   <?php echo $refund_info['refund_remark']; ?></td>
				    
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">创建时间：<?php echo $refund_info['create_time_show']; ?></td>
				    <td class="text-left">退款时间：<?php echo $refund_info['refund_time_show']; ?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
	    </tbody>
	</table>
	
    </div>
<?php include template('footer', 'admin'); ?>
    <script>
	$('.table').resizableColumns();
    </script>
