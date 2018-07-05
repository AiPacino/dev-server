<?php use zuji\order\EvaluationStatus;use zuji\Business;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
    <?php if(!$inner){?>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">检测单详情</li>
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
		    <th class="text-left padding-big-left">检测单详情</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
                                    <th class="text-left" colspan="1">业务类型：<?php echo Business::getName($evaluation_info['business_key']); ?></th>
                                    <th class="text-left" colspan="2">检测状态：<?php echo EvaluationStatus::getStatusName($evaluation_info['evaluation_status']);?></th>
				</tr>
                                <?php if($evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationFinished){?>
                                    <tr class="line-height-40">
                                        <td class="text-left">检测结果：<?php echo  EvaluationStatus::getResultName($evaluation_info['qualified']); ?></td>
                                        <td class="text-left">检测时间：<?php echo date('Y-m-d H:i:s',$evaluation_info['evaluation_time']); ?></td>
                                        <td class="text-left"></td>
                                        <td class="text-left"></td>
                                    </tr>
                                    <?php if($evaluation_info['qualified'] == EvaluationStatus::ResultUnqualified && $evaluation_info['unqualified_result'] > 0){?>
                                        <tr class="line-height-40">
                                            <td class="text-left">检测处理：<?php echo EvaluationStatus::getUnqualifiedName($evaluation_info['unqualified_result']); ?></td>
                                            <td class="text-left">检测备注：<?php echo $evaluation_info['unqualified_remark_show']; ?></td>
                                            <td class="text-left"></td>
                                            <td class="text-left"></td>
                                        </tr>
                                    <?php }?>
                                <?php }?>
				<tr class="line-height-40">
				    <td class="text-left">创建时间：<?php echo date('Y-m-d H:i:s',$evaluation_info['create_time']); ?></td>
				    <td class="text-left"></td>
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
