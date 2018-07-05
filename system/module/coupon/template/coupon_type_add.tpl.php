<?php include template('header','admin');?>
<div class="fixed-nav layout">
	<ul>
		<li class="first">添加优惠券类型</li>
		<li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
	<form method="POST">
		<input type="hidden" name="m" value="coupon" />
		<input type="hidden" name="c" value="coupon" />
		<input type="hidden" name="a" value="coupon_type_add" />
		<div class="form-box clearfix">
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','coupon_name','','优惠券名称：','')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','coupon_type','1','优惠券类型：','1固定金额；2租金百分比；3首月0租金')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','coupon_value','0','优惠券类型的值：','固定金额单位"分"；百分比如九折写90；首月免金额填0')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','range','0','优惠范围：','0全场；1指定商品；4指定商品品牌；5指定渠道；6指定商品规格')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','range_value','0','优惠范围的值：','指定商品,商品规格,商品品牌,渠道 用"ID+,"如"3,"指定多个如"3,4,5,"；全场填0')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','mode','1','优惠方式：','1直减；2返现(暂时不支持)')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','use_restrictions','0','使用限制：','0不限制；如果限制填写金额单位"分"')?>
			</div>
			<div class="form-group form-layout-rank">
				<?php echo form::input('text','describe','0','优惠券描述：','')?>
			</div>
		</div>
		<div class="padding">
			<input type="submit" name="dosubmit" class="button bg-main" value="确定" />
			<input type="button" class="button margin-left bg-gray" value="返回" />
		</div>
	</form>
</div>
<?php include template('footer','admin');?>
