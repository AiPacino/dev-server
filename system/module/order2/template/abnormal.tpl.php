<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
		<ul>
			<li class="first">发货单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
			<li class="spacer-gray"></li>
			<li><a <?php if (!isset($_GET['delivery_status'])) {echo 'class="current"';} ?> href="<?php echo url('order2/send_goods/index') ?>">全部</a></li>
			<li><a <?php if (isset($_GET['delivery_status']) && $_GET['delivery_status'] == 0) {echo 'class="current"';} ?> href="<?php echo url('order2/send_goods/index',array('delivery_status'=>'0')) ?>">还机</a></li>
			<li><a <?php if (isset($_GET['delivery_status']) && $_GET['delivery_status'] == 1) {echo 'class="current"';} ?> href="<?php echo url('order2/send_goods/index',array('delivery_status'=>'1')) ?>">换机</a></li>
			<li><a <?php if (isset($_GET['delivery_status']) && $_GET['delivery_status'] == 2) {echo 'class="current"';} ?> href="<?php echo url('order2/send_goods/index',array('delivery_status'=>'2')) ?>">退货</a></li>
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
				<p>- 添加商品时可选择商品分类，用户可根据分类查询商品列表</p>
				<p>- 点击分类名前“+”符号，显示当前分类的下级分类</p>
				<p>- 对分类作任何更改后，都需要到 设置 -> 清理缓存 清理商品分类，新的设置才会生效</p>
			</div>
		</div>
		<div class="hr-gray"></div>
		<div class="clearfix">

					<form>
				<input type="hidden" name="m" value="order2" />
				<input type="hidden" name="c" value="send_goods" />
				<input type="hidden" name="a" value="index" />
				<div class="form-group form-layout-rank border-none" style="width: 400px;">

					<div class="box ">
						<div class="field margin-none">
							搜索：
							检测时间<input class="input" type="text"  style="width: 100px;"  name="keyword" value="<?php echo $_GET['keyword'] ?>" placeholder="时间框" >
							<?php echo form::input('select','group_id',$_GET['group_id'] ? $_GET['group_id'] : 0,'检测原因','',array('items' => array('还机','换机','退货')))?>
							<?php echo form::input('select','group_id',$_GET['group_id'] ? $_GET['group_id'] : 0,'','',array('items' => array('订单编号','收货人手机号')))?>
							<input class="input" type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>" placeholder="输入订单号/会员账号/手机号" tabindex="0">
						</div>
						<input type="checkbox" value="1" checked >  开启模糊查询
					</div>
				</div>
				<input class="button bg-sub margin-top fl" type="submit" style="height: 26px; line-height: 14px;" value="查询">
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
					<span class="th" data-width="15">
						<span class="td-con">操作</span>
					</span>
				</div>


				<?php foreach ($lists['lists'] AS $list) {?>
				<div class="tr">
					<?php foreach ($list as $key => $value) {?>
					<?php if($lists['th'][$key]){?>
					<?php if ($lists['th'][$key]['style'] == 'double_click') {?>
					<span class="td">
						<div class="double-click">
							<a class="double-click-button margin-none padding-none" title="双击可编辑" href="javascript:;"></a>
							<input class="input double-click-edit text-ellipsis text-center" type="text" name="<?php echo $key?>" data-id="<?php echo $list['id']?>" value="<?php echo $value?>" />
						</div>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'ident') {?>
						<span class="td ident">
							<span class="ident-show">
								<em class="ico_pic_show"></em>
								<div class="ident-pic-wrap">
									<img src="<?php echo $list['logo'] ? $list['logo'] : '../images/default_no_upload.png'?>" />
								</div>
							</span>
							<div class="double-click">
								<a class="double-click-button margin-none padding-none" title="双击可编辑" href="javascript:;"></a>
								<input class="input double-click-edit text-ellipsis" name="<?php echo $key?>" data-id="<?php echo $list['id']?>" type="text" value="<?php echo $value?>" />
							</div>
						</span>
					<?php }elseif ($lists['th'][$key]['delivery_status'] == 'delivery_status') {?>
					<span class="td">
						<span class="td-con">
						<?php
							switch($value){
								case 0:
									echo '待发货';
									break;
								case 1:
									echo '已发货';
									break;
								case 2:
									echo '确认收货';
									break;
							}
						?>

						</span>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'left_text') {?>
					<span class="td">
						<span class="td-con text-left"><?php echo $value;?></span>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'ico_up_rack') {?>
					<span class="td">
						<a class="ico_up_rack <?php if($value != 1){?>cancel<?php }?>" href="javascript:;" data-id="<?php echo $list['id']?>" title="点击取消推荐"></a>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'date') {?>
					<span class="td">
						<span class="td-con"><?php echo date('Y-m-d H:i' ,$value) ?></span>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'hidden') {?>
						<input type="hidden" name="id" value="<?php echo $value?>" />
					<?php }else{?>
					<span class="td">
						<span class="td-con"><?php echo $value;?></span>
					</span>
					<?php }?>
					<?php }?>
					<?php }?>
					<span class="td">
						<span class="td-con">

						<a onclick="order_action.chuli('<?php echo url("order2/evaluation/chuli",array("id"=>$list['id'])); ?>');" href="javascript:;">处理</a>&emsp;
								<a onclick="order_action.baogao('<?php echo url("order2/evaluation/baogao",array("id"=>$list['id'])); ?>');" href="javascript:;">查看检测报告</a>&emsp;

						</span>
					</span>
				</div>
				<?php }?>


				<!-- 分页 -->
				<div class="paging padding-tb body-bg clearfix">
					<ul class="fr"><?php echo $pages; ?></ul>
					<div class="clear"></div>
				</div>


			</div>
		</div>
		<input onclick="order_action.daochu('<?php echo url("order2/evaluation/daochu",array("id"=>$list['id'])); ?>');" class="button bg-sub margin-top " type="daochu" style="height: 26px; line-height: 14px;" value="导出">
	</div>
<?php include template('footer','admin');?>

<script>
	$(".form-group .box").addClass("margin-none");
	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		var $val=$("input[type=text]").first().val();
		$("input[type=text]").first().focus().val($val);
	})
</script>
