<?php include template('header','admin');?>
<script type="text/javascript" src="./statics/js/goods/goods_list.js"></script>
<body>
<div class="fixed-nav layout">
	<ul>
		<li class="first">电子合同管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
		<li class="spacer-gray"></li>
		<li><a class="current" href="javascript:;">内容列表</a></li>
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
			<p>- 将内容位调用代码放入前台页面，将显示该内容位的内容</p>
			<p>- 温馨提示，创建内容之前需要添加一个内容位，内容位分为图片内容和文字内容</p>
		</div>
	</div>
	<div class="hr-gray"></div>
	<div class="table-work border margin-tb">
		<div class="border border-white tw-wrap">
			<?php if(count($lists['lists'])<2){ ?>
			<a href="<?php echo url('edit')?>"><i class="ico_add"></i>添加</a>
			<?php } ?>
			<!--<div class="spacer-gray"></div>
					<a data-message="是否确定删除所选？" href="<?php echo url('del')?>" data-ajax='id'><i class="ico_delete"></i>删除</a>
					<div class="spacer-gray"></div>-->
		</div>
	</div>
	<div class="table resize-table check-table paging-table border clearfix">
		<div class="member  tr">

			<?php foreach ($lists['th'] AS $th) {?>
				<span class="th" data-width="<?php echo $th['length']?>">
							<span class="td-con"><?php echo $th['title']?></span>
						</span>
			<?php }?>
			<div class="th" data-width="10"><span class="td-con">操作</span></div>
		</div>
		<?php foreach ($lists['lists'] AS $list) {?>
			<div class="member tr">
				<!--<span class="td check-option"><input type="checkbox" name="id" value="<?php echo $list['id']?>" /></span>-->
				<?php
				foreach ($lists['th'] AS $key => $th) {
					$value = $list[$key];
					if( $th['style'] == 'double_click' ){
						?>
						<span class="td">
						<div class="double-click">
							<a class="double-click-button margin-none padding-none" title="双击可编辑" href="javascript:;"></a>
							<input class="input double-click-edit text-ellipsis" type="text" name="<?php echo $key?>" data-id="<?php echo $list['id']?>" value="<?php echo $value?>" />
						</div>
					</span>
						<?php
					}elseif($th['style'] == 'ident'){
						?>
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
						<?php
					}elseif($th['style'] == 'ico_up_rack'){
						?>
						<span class="td">
						<a class="ico_up_rack <?php if($value != 1){?>cancel<?php }?>" href="javascript:;" data-id="<?php echo $list['id']?>" title="点击启用/禁用"></a>
					</span>
						<?php
					}elseif($th['style'] == 'date'){
						?>
						<span class="td">
						<span class="td-con"><?php echo date('Y-m-d H:i' ,$value) ?></span>
					</span>
						<?php
					}elseif($th['style'] == 'left_text'){
						?>
						<span class="td">
						<span class="td-con text-left"><?php echo $value;?></span>
					</span>
						<?php
					}elseif($th['style'] == 'hidden'){
						?>
						<input type="hidden" name="id" value="<?php echo $value?>" />
						<?php
					}elseif($th['style'] == 'img'){
						?>
						<span class="td">
					    <span class="td">
						<div class="td-con td-pic">
							<div class=""><img width="80" height="80" src="<?php echo $value ? $value :'./statics/images/default_no_upload.png';?>" /></div>
						</div>
					    </span>
					</span>
						<?php
					}else{
						?>
						<span class="td">
						<span class="td-con"><?php echo $value;?></span>
					</span>
						<?php
					}
				}
				?>
				<span class="td">
						<span class="td-con">
						<a href="<?php echo url('edit',array('id'=>$list['id']))?>">编辑</a>
                        </span>
					</span>
			</div>
		<?php }?>

		<div class="paging padding-tb body-bg clearfix">
			<ul class="fr">
				<?php echo $lists['pages']?>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
</div>
<script>
	var formhash='<?php echo FORMHASH?>';
	var del_url = "<?php echo url('del',array('formhash'=>FORMHASH))?>";
	var save_title_url = "<?php echo url('save_title')?>";
	var ajax_status = "<?php echo url('del',array('formhash'=>FORMHASH))?>";
	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		$('.table .tr:last-child').addClass("border-none");
		//启用与关闭
		$(".table .ico_up_rack").bind('click',function(){
			var id = $(this).attr('data-id');
			var row = $(this);
			list_action.change_status(ajax_status,id,row);
			if(!$(this).hasClass("cancel")){
				$(this).addClass("cancel");
				$(this).attr("title","点击开启");
			}else{
				$(this).removeClass("cancel");
				$(this).attr("title","点击关闭");
			}
		});
	});
	$(function(){
		//双击编辑
		$('.double-click-edit').on('blur',function(){
			$.post(save_title_url,{id:$(this).data('id'),title:""+$(this).val()+"",formhash:""+formhash+""},function(data){
			})
		})
	})
</script>
<?php include template('footer','admin');?>
