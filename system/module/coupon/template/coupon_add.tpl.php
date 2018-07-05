<?php include template('header','admin');?>
<div class="fixed-nav layout">
	<ul>
		<li class="first">生成优惠券设置</li>
		<li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
 <form method="POST">
	 <input type="hidden" name="m" value="coupon" />
	 <input type="hidden" name="c" value="coupon" />
	 <input type="hidden" name="a" value="coupon_add" />
	<div class="form-box clearfix">
		<?php echo form::input('select','coupon_type_id','','优惠券类型','',array('css'=>'form-layout-rank','items' => $coupon_date))?>
		<div class="form-group form-layout-rank">
			<span class="label">优惠券活动开始时间：</span>
			<div class="box margin-none">
				<?php echo form::calendar('start_time','',array('format' => 'YYYY-MM-DD hh:mm'))?>
			</div>
		</div>
		<div class="form-group form-layout-rank">
			<span class="label">优惠券活动结束时间：</span>
			<div class="box margin-none">
				<?php echo form::calendar('end_time','',array('format' => 'YYYY-MM-DD hh:mm'))?>
			</div>
		</div>
		<div class="form-group form-layout-rank">
			<?php echo form::input('text','num','1','数量：','需要生成的优惠券个数')?>
		</div>

	</div>
	<div class="padding">
		<input type="submit" name="dosubmit" class="button bg-main" value="确定" />
		<input type="button" class="button margin-left bg-gray" value="返回" />
	</div>
</form>
</div>
<?php include template('footer','admin');?>
