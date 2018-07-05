<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

	<div class="fixed-nav layout">
		<ul>
			<li class="first">优惠券管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
			<li class="spacer-gray"></li>
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
				<p>- <a href="<?php echo url('coupon/coupon/coupon_add'); ?>">生成优惠券</a></p>
			</div>
		</div>
		<div class="hr-gray"></div>
		<div class="clearfix">
			<form method="get" >
				<input type="hidden" name="m" value="coupon" />
				<input type="hidden" name="c" value="coupon" />
				<input type="hidden" name="a" value="coupon_list" />
				<div class="order2-list-search clearfix">
					<div class="form-box clearfix border-bottom-none" >

						<div class="form-group form-layout-rank">
							<span class="label">时间：</span>
							<div class="box margin-none">
									<?php echo form::calendar('start_time',!empty($_GET['start_time']) ? $_GET['start_time']:'',array('format' => 'YYYY-MM-DD hh:mm'))?>
							</div>
						</div>

						<div class="form-group form-layout-rank">
							<span class="label">~</span>
							<div class="box margin-none">
								<?php echo form::calendar('end_time',!empty($_GET['end_time'])? $_GET['end_time']:'',array('format' => 'YYYY-MM-DD hh:mm'))?>
							</div>
						</div>
					</div>
					<div class="form-box clearfix border-bottom-none" >
						<?php echo form::input('select','coupon_type_id',$_GET['coupon_type_id'] ? $_GET['coupon_type_id'] : '0','优惠券类型','',array('css'=>'form-layout-rank','items' => $coupon_date))?>
						<div class="form-group form-layout-rank">
							<div class="box keywords margin-none">
								<input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo !empty($_GET['keywords'])?$_GET['keywords'] :''?>">
							</div>
						</div>
					</div>
					<input class="button bg-sub fl" value="查询" type="submit">
				</div>
			</form>
		</div>
		<div class="table-wrap">
			<div class="table resize-table paging-table border clearfix">
				<div class="tr">
					<?php foreach ($lists['th'] AS $th) {?>
					<span class="th" data-width="<?php echo $th['length']?>">
						<span class="td-con"><?php echo $th['title']?></span>
					</span>
					<?php }?>
				</div>
				<?php foreach ($lists['lists'] AS $item) {?>
				    <div class="tr">
				    <?php foreach ($lists['th'] AS $k=>$th) {?>
					<span class="td">
					    <?php echo $item[$k]; ?>
					</span>
				    <?php }?>
				    </div>
				<?php }?>
				<!-- 分页 -->
				<div class="paging padding-tb body-bg clearfix">
					<ul class="fr"><?php echo $pages; ?></ul>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	</div>
<?php include template('footer','admin');?>
<script src="<?php echo __ROOT__;?>statics/js/laydate/laydate.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<script>
	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		var $val=$("input[type=text]").first().val();
		$("input[type=text]").first().focus().val($val);
	})

</script>
