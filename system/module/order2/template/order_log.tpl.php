<?php

use zuji\order\ReturnStatus;

include template('header', 'admin');
?>

<body>
    <div class="content">

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
				    <td class="text-left" style="width: 5%">
					<?php
					if ($log['operator_type'] == 2) {
					    echo '买家';
					} elseif ($log['operator_type'] == 1) {
					    echo '卖家';
					} elseif ($log['operator_type'] == 3) {
                        echo '系统';
                    } elseif ($log['operator_type'] == 4) {
                        echo '门店';
                    }
					?>
				    </td>
				    <td class="text-left" style="width: 5%"><?php echo $log['operator_name'] ?></td>
				    <td class="text-left" style="width: 5%">于</td>
				    <td class="text-left" style="width: 15%"><?php echo date('Y-m-d H:i:s', $log['system_time']); ?></td>
				    <td class="text-left" style="width: 15%">「<?php echo $log['action']; ?>」</td>
				    <td class="text-left" style="width: 60%;word-break:break-all">操作备注：<?php echo $log['msg'];?></td>
    				</tr>
				<?php endforeach; ?>
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
