<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
    <?php if(!$inner){?>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">退货单详情</li>
	    <li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
    </div>
    <?php }?>
    <div class="content <?php if(!$inner){echo 'padding-big have-fixed-nav';}?>">
	<!--支付详情-->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">退货详情</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left" colspan="1">退货状态：<?php echo $return_info['return_status_show']; ?></th>
				    <th class="text-left" colspan="2">审核员：<?php echo $return_info['admin_name']; ?></th>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">损耗类型：<?php echo $return_info['loss_type_show']; ?></td>
				    <td class="text-left">退货原因：<?php echo $return_info['reason']; ?></td>
				    <td class="text-left">退货备注：<?php echo $return_info['reason_text']; ?></td>
				    <td class="text-left">审核备注：<?php echo $return_info['return_check_remark']; ?></td>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">创建时间：<?php echo $return_info['create_time_show']; ?></td>
				    <td class="text-left">审核时间：<?php echo $return_info['return_check_time_show']; ?></td>
				    <td class="text-left"></td>
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
