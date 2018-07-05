<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<div class="fixed-nav layout">
	<ul>
		<li class="first">会员管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
			<p>- 通过会员管理，你可以进行查看、编辑会员资料以及删除会员等操作</p>
			<p>- 你可以根据条件搜索会员，然后选择相应的操作</p>
		</div>
	</div>

	<div class="member-list-search clearfix">
	<form action="" method="get">
		<div class="form-box form-layout-rank clearfix border-bottom-none">
			<?php echo form::input('select','certified',$_GET['certified'] ? $_GET['certified'] : 'all','认证状态','',array('items' => ['all'=>'全部','y'=>'已认证','n'=>'未认证',]))?>
			<?php echo form::input('text', 'keyword', $_GET['keyword'], '搜索', '', array('css'=>'form-group-id2','placeholder' => '输入会员名/手机/邮箱均可搜索'));?>
		</div>
		<input type="hidden" name="m" value="member">
		<input type="hidden" name="c" value="member">
		<input type="hidden" name="a" value="index">
		<input class="button bg-sub fl" type="submit" value="查询">
	</form>
	</div>

	<div class="table-work border margin-tb">
		<div class="border border-white tw-wrap">
			<a data-message="是否确定删除所选？" href="<?php echo url('delete')?>" data-ajax='ids'><i class="ico_delete"></i>删除</a>
			<div class="spacer-gray"></div>
			<a data-message="是否锁定此用户？" href="<?php echo url('togglelock',array('type'=>1))?>" data-ajax='ids'><i class="ico_lock"></i>锁定</a>
			<div class="spacer-gray"></div>
			<a data-message="是否解锁此用户？" href="<?php echo url('togglelock',array('type'=>0))?>" data-ajax='ids'><i class="ico_unlock"></i>解锁</a>
			<div class="spacer-gray"></div>
			<!--<a href=""><i class="ico_out"></i>导出</a>
			<div class="spacer-gray"></div>-->
		</div>
	</div>
	<?php echo runhook('admin_member_lists_extra')?>
	<div class="table-wrap member-info-table">
		<div class="table resize-table paging-table check-table high-table border clearfix">
			<div class="tr">
				<span class="th check-option" data-resize="false"><span><input id="check-all" type="checkbox" /></span></span>
				<?php foreach ($lists['th'] AS $th) {?>
					<span class="th" data-width="<?php echo $th['length']?>">
						<span class="td-con"><?php echo $th['title']?></span>
					</span>
					<?php }?>
					<span class="th" data-width="5">
						<span class="td-con">详情</span>
					</span>
			</div>
			<?php foreach ($lists['lists'] AS $list) {?>
				<div class="tr">
					<span class="td check-option"><input type="checkbox" name="id" value="<?php echo $list['id']?>" /></span>
					<?php foreach ($lists['th'] as $key => $th) {
					   
					    $value = $list[$key];
					    ?>
					<?php if ($th['style'] == 'double_click') {?>
					<span class="td">
						<div class="double-click">
							<a class="double-click-button margin-none padding-none" title="双击可编辑" href="javascript:;"></a>
							<input class="input double-click-edit text-ellipsis text-center" type="text" name="<?php echo $key?>" data-id="<?php echo $list['id']?>" value="<?php echo $value?>" />
						</div>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'member') {?>
						<span class="td">
							<div class="td-con td-pic text-left over-initial">
							<span class="pic"><img src="<?php echo $list['avatar'] ?>" alt="用户头像" /></span>
							<span class="title txt"><?php echo $value ?>&nbsp;&nbsp;<em class="text-main">（登录次数：<?php echo $list['login_num']?>）</em></span>
							<span class="icon">
								<div class="ico_emial member-info-tip"><span><i></i>邮箱：<?php echo $list['email'] ?></span></div>
								<div class="ico_moblie member-info-tip"><span><i></i>手机：<?php echo $list['mobile'] ?></span></div>
								<a class="ico_address" href="<?php echo url('member/member/address', array('mid' => $list['id'],'has_address' => 1));?>" data-iframe='true' data-iframe-width='780' title="点击查看收货地址"></a>
						</span>
					</div>
						</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'member_level') {?>
					<span class="td">
						<span class="td-con text-left double-row">
							会员等级：<?php echo $value ?><br/>经验值：<?php echo $list['exp'];?>&nbsp;&nbsp;&nbsp;&nbsp;<!-- 积分：<?php echo $member['integral'];?> -->
						</span>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'money') {?>
					<span class="td">
						<span class="td-con double-row text-left">可用余额：￥<?php echo $value ?><br />冻结余额：￥<?php echo $list['frozen_money']?></span>
					</span>
					<?php }elseif ($lists['th'][$key]['style'] == 'login') {?>
					<span class="td">
						<span class="td-con double-row text-left">注册时间：<?php echo $value?><br />最后登录：<?php echo $list['login_time']?></span>
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
						<span class="td-con">
						<?php if($key =="block"){?>		      
						      <?php if($value ==0){?>
						                  未封&emsp;|
						      <?php }else{?>
						       <a onclick="order_action.dialog({width:350,url:'<?php echo url('member/member/deblocking',array('id' => $list['id'])); ?>'});" href="javascript:;"><font color="red">解封</font></a>&emsp;|
						      <?php }?>
						      &emsp;<a href="<?php echo url('member/member/deblocking_record',array('id' => $list['id'])); ?>" data-iframe="true" data-iframe-width="600">解封记录</a>
						<?php }else{echo $value;}?>
						</span>
					</span>
					<?php }?>
					<?php }?>
					
					<span class="td">
						<a href="javascript:;" onclick="order_action.dialog({'title':'用户详情','url':'<?php echo url('member/member/detail',array('id' =>$list["id"])); ?>'})">详情</a>
					</span>
					<span class="td">
					
				</span>
				</div>
				<?php }?>
			<div class="paging padding-tb body-bg clearfix">
				<?php echo $pages;?>
				<div class="clear"></div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">

	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		$(".member-info-tip").hover(function(){
			$(this).children("span").show();
		},function(){
			$(this).children("span").hide();
		});
		$(".member-list-search .form-group").each(function(i){
			$(this).addClass("form-group-id"+(i+1));
		});
		$("a.member_update").click(function() {
			top.dialog({
				url: $(this).attr("data-url"),
				title: '修改会员信息',
				width: 460,
				onclose:function() {
					if(this.returnValue) {
						window.location.href = this.returnValue.referer;
					}
				}
			})
			.showModal();
		});
		return false;
	})
	$(function(){
		var $val=$("input[type=text]").eq(1).val();
		$("input[type=text]").eq(1).focus().val($val);
	})
</script>
<?php include template('footer','admin');?>
