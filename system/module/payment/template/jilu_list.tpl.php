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
    <!-- 预授权操作记录列表 -->
    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
        <tbody>
        <tr class="bg-gray-white line-height-40 border-bottom">
            <th class="text-left padding-big-left">预授权操作记录</th>
        </tr>

        <tr class="border">
            <td>
                <div class="table resize-table clearfix">
                    <div class="tr">
						<?php foreach ($data_table['th'] AS $th) {?>
						<span class="th" data-width="<?php echo $th['length']?>">
							<span class="td-con"><?php echo $th['title']?></span>
						</span>
						<?php }?>
					</div>
					<?php foreach ($data_table['lists'] AS $item) {?>
						<div class="tr">
						<?php foreach ($data_table['th'] AS $k=>$th) {?>
						<span class="td">
							<?php echo isset($item[$k])&&$item[$k]?$item[$k]:'--'; ?>
						</span>
						<?php }?>
						</div>
					<?php }?>
					<!-- 分页 -->
					<div class="paging padding-tb body-bg clearfix">
						<ul class="fr"><?php echo $data_table['pages']; ?></ul>
						<div class="clear"></div>
					</div>

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
