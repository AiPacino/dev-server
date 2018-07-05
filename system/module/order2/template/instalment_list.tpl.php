<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
	<style>
		.content{
			min-width: 400px;
		}
	</style>
<div class="content <?php if(!$inner){echo 'padding-big have-fixed-nav';}?>">
    <!--支付详情-->
    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
        <tbody>
        <tr class="bg-gray-white line-height-40 border-bottom">
            <th class="text-left padding-big-left">分期付款详情</th>
        </tr>

        <tr class="border">
            <td>
                <div class="table resize-table clearfix">
                    <div class="tr">
                            <span class="th" data-width="10">
                                <span class="td-con">分期</span>
                            </span>

                            <span class="th" data-width="10">
                                <span class="td-con">第几期</span>
                            </span>
                         <span class="th" data-width="10">
                                <span class="td-con">应付金额</span>
                            </span>

                            <span class="th" data-width="10">
                                <span class="td-con">状态</span>
                            </span>
                         <span class="th" data-width="15">
                                <span class="td-con">租机交易号</span>
                            </span>

                            <span class="th" data-width="25">
                                <span class="td-con">第三方交易号</span>
                            </span>
                            <span class="th" data-width="10">
                                <span class="td-con">扣款时间</span>
                            </span>
                            <span class="th" data-width="10">
                                <span class="td-con">最后更新</span>
                            </span>


                    </div>
                    <?php foreach ($instalment_list as $k => $item) : ?>
                        <div class="tr">
                            <span class="td">
                                <span class="td-con"><?php echo $item['term']; ?></span>
                            </span>

                             <span class="td" >
                                <span class="td-con"><?php echo $item['times']; ?></span>
                            </span>
                            <span class="td">
                                <span class="td-con"><?php echo $item['amount']; ?></span>
                            </span>

                             <span class="td">
                                <span class="td-con"><?php echo $item['status_show']; ?></span>
                            </span>
                            <span class="td">
                                <span class="td-con"><?php echo $item['trade_no']; ?></span>
                            </span>

                             <span class="td">
                                <span class="td-con"><?php echo $item['out_trade_no']; ?></span>
                            </span>
                             <span class="td">
                                <span class="td-con"><?php echo $item['payment_time_show']; ?></span>
                            </span>
                             <span class="td">
                                <span class="td-con"><?php echo $item['update_time_show']; ?></span>
                            </span>

                        </div>
                    <?php endforeach; ?>


                </div>
            </td>
        </tr>

        </tbody>
    </table>

</div>
<?php include template('footer', 'admin'); ?>
<script>
    $('.table').resizableColumns();
</script>
