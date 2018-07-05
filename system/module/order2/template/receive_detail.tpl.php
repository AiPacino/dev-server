<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
    <?php if(!$inner){?>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">收货单详情</li>
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
		    <th class="text-left padding-big-left">收货详情</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left">收货状态：<?php echo $receive_info['receive_status_show']; ?></th>
				    <th class="text-left" colspan="1">操作人员：<?php echo $receive_info['admin_name']; ?></th>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">收货地址：   <?php echo $receive_info['address_show']; ?></td>

				</tr>
				<tr class="line-height-40">			    
				    <td class="text-left">物流名称：<?php echo $receive_info['wuliu_channel']; ?></td>
				    <td class="text-left">物流单号：<?php echo $receive_info['wuliu_no']; ?></td>
				    <td class="text-left">收货条码：   <?php echo $receive_info['bar_code']; ?></td>
				    
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">创建时间：<?php echo $receive_info['create_time_show']; ?></td>
				    <td class="text-left">收货时间：<?php echo $receive_info['receive_time_show']; ?></td>
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
