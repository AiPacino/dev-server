<?php

use zuji\order\ReturnStatus;
use zuji\order\OrderStatus;

include template('header', 'admin');
?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
    <ul>
	<li class="first">退货单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
	<li class="spacer-gray"></li>
	<?php
	foreach ($tab_list as $tab) {
	    echo '<li>' . $tab . '</li>';
	}
	?>	
    </ul>
    <div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
    <div class="tips margin-tb">
	<div class="tips-info border">
	    <h6>温馨提示</h6>
	    <a id="show-tip" data-open="true" href="javascript:;">关闭操作提示</a>
	</div>
	<div class="tips-txt padding-small-top layout">
	    <p>- </p>
	</div>
    </div>
    <div class="hr-gray"></div>
    <div class="clearfix">
	<form>
	    <input type="hidden" name="m" value="order2" />
	    <input type="hidden" name="c" value="return" />
	    <input type="hidden" name="a" value="index" />
	    <div class="order2-list-search clearfix">
		<div class="form-box clearfix border-bottom-none" >

		    <div class="form-group form-layout-rank">
			<span class="label">时间：</span>
			<div class="box margin-none">
<?php echo form::calendar('begin_time', !empty($_GET['begin_time']) ? $_GET['begin_time'] : '', array('format' => 'YYYY-MM-DD hh:mm')) ?>
			</div>
		    </div>

		    <div class="form-group form-layout-rank">
			<span class="label">~</span>
			<div class="box margin-none">
<?php echo form::calendar('end_time', !empty($_GET['end_time']) ? $_GET['end_time'] : '', array('format' => 'YYYY-MM-DD hh:mm')) ?>
			</div>
		    </div>

		</div>
		<div class="form-box clearfix border-bottom-none" >
<?php echo form::input('select', 'business_key', $_GET['business_key'] ? $_GET['business_key'] : '0', '业务类型', '', array('css' => 'form-layout-rank', 'items' => $business_list)) ?>

<?php echo form::input('select', 'kw_type', $_GET['kw_type'] ? $_GET['kw_type'] : 'order_no', '搜索', '', array('css' => 'form-layout-rank', 'items' => $keywords_type_list)) ?>

		    <div class="form-group form-layout-rank">
			<div class="box keywords margin-none">
			    <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo!empty($_GET['keywords']) ? $_GET['keywords'] : '' ?>">
			</div>
		    </div>
		</div>
		<input class="button bg-sub fl" value="查询" type="submit">
		<input id="export" class="button bg-sub text-normal" style="padding: 3px 20px;" type="button" value="导出" /> 
	    </div>
	</form>
    </div>


    <div class="table-wrap">
	<div class="table resize-table paging-table border clearfix">
	    <div class="tr">
		<?php foreach ($lists['th'] AS $th) { ?>
    		<span class="th" data-width="<?php echo $th['length'] ?>">
    		    <span class="td-con"><?php echo $th['title'] ?></span>
    		</span>
<?php } ?>
		<span class="th" data-width="10">
		    <span class="td-con">操作</span>
		</span>
	    </div>


<?php foreach ($lists['lists'] AS $item) { ?>
    	    <div class="tr">
		<?php foreach ($lists['th'] as $k => $setting) { ?>
		<span class="td">
		    <span class="td-con"><?php echo $item[$k]; ?></span>
		</span>
		<?php } ?>
    		<span class="td">
    		     <div class="btn-list" align="center">
        			<?php if ($item['allow_check']) { ?>
        			    <a class="bg-main btn" href='<?php echo url("order2/return/check", array("return_id" => $item['return_id'])); ?>' data-iframe="true" data-iframe-width="300" >审核</a>&emsp;<br>
        			<?php } ?>
        			<a href="javascript:;" onclick="order_action.dialog({'title':'退货详情','url':'<?php echo url('order2/return/detail',array('return_id' =>$item["return_id"])); ?>'})">退货申请详情</a><br>
        			<a href='<?php echo url('order2/order/detail',array('order_id' => $item["order_id"],'return_id'=>$item['return_id'])); ?>' data-iframe="true" data-iframe-width="1000">订单详情</a> 
    		    </div>
    		</span>
    	    </div>
<?php } ?>


	    <!-- 分页 -->
	    <div class="paging padding-tb body-bg clearfix">
		<ul class="fr"><?php echo $pages; ?></ul>
		<div class="clear"></div>
	    </div>


	</div>
    </div>
</div>
<?php include template('footer', 'admin'); ?>

<script>
    $(".form-group .box").addClass("margin-none");
    $(window).load(function() {
	$(".table").resizableColumns();
	$(".paging-table").fixedPaging();
	var $val = $("input[type=text]").first().val();
	$("input[type=text]").first().focus().val($val);
		//导出
		$('#export').click(function(){
			//获取导出的开始时间
			var begin_time = $('input[name="begin_time"]').val();
			var end_time 	= $('input[name="end_time"]').val();
			window.location.href = "<?php echo url('return_order_export')?>"+ "&begin_time=" + begin_time + "&end_time=" + end_time;
		});
    })
</script>
