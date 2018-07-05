<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
    <?php if(!$inner){?>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">发货单详情</li>
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
		    <th class="text-left padding-big-left">发货详情</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left">业务类型：<?php echo $delivery_info['business']; ?></th>
				    <th class="text-left">发货状态：<?php echo $delivery_info['delivery_status_show']; ?></th>
				    <th class="text-left">操作人员：<?php echo $delivery_info['admin_name']; ?></th>
				    <td class="text-left">创建时间：<?php echo $delivery_info['create_time_show']; ?></td>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">收货人：<?php echo $delivery_info['name']; ?></td>
				    <td class="text-left">收货人手机：   <?php echo $delivery_info['mobile']; ?></td>
				    <td class="text-left">收货地址：<?php echo $delivery_info['address']; ?></td>
				</tr>
				<tr class="line-height-40">			    
				    <td class="text-left">物流名称：<?php echo $delivery_info['wuliu_channel']; ?></td>
				    <td class="text-left">物流单号：<?php echo $delivery_info['wuliu_no']; ?></td>
                    <td class="text-left">发货时间：<?php echo $delivery_info['delivery_time_show']; ?></td>
				  
				</tr>
				<tr class="line-height-40">

				    <td class="text-left">收货时间：<?php echo $delivery_info['confirm_time_show']; ?></td>
                    <td class="text-left">收货备注：<?php echo $delivery_info['confirm_remark']; ?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <td class="text-left">IMEI1：<?php echo $order_info['goods_info']['imei1']; ?></td>
				    <td class="text-left">IMEI2：<?php echo $order_info['goods_info']['imei2'];?></td>
				    <td class="text-left">IMEI3：<?php echo $order_info['goods_info']['imei3']; ?></td>
				    <td class="text-left">IOS序列号：<?php echo $order_info['goods_info']['serial_number']; ?></td>
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
