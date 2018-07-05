<?php include template('header','admin');?>
	<script type="text/javascript" src="./statics/js/goods/goods_list.js"></script>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">支付配置<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
                <li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>

		<div class="content padding-big have-fixed-nav">
			<div class="tips margin-tb">
				<div class="tips-info border">
					<h6>温馨提示</h6>
					<a id="show-tip" data-open="true" href="javascript:;">点击关闭操作提示</a>
				</div>
				<div class="tips-txt padding-small-top layout">
					<p>-</p>
				</div>
			</div>
			<div class="hr-gray"></div>
            <div class="clearfix">
            </div>
			<div class="table-work border margin-tb">
				<div class="border border-white tw-wrap">
					<a href="<?php echo url('edit')?>"><i class="ico_add"></i>添加</a>
					<div class="spacer-gray"></div>
				</div>
			</div>
			<div class="table-wrap resize-table">
				<div class="table paging-table resize-table border clearfix">
					<div class="tr">
						<?php foreach ($lists['th'] AS $th) {?>
						<span class="th" data-width="<?php echo $th['length']?>">
							<span class="td-con"><?php echo $th['title']?></span>
						</span>
						<?php }?>
                        <span class="th" data-width="10">
							<span class="td-con">操作</span>
						</span>
					</div>
                    <?php foreach ($lists['lists'] AS $item) {?>
                        <div class="tr">
                            <?php foreach ($lists['th'] AS $k=>$th) {?>
                                <?php if ($lists['th'][$k]['style'] == 'double_click') {?>
                                    <span class="td" style="padding-left: 30px;">
                                <div class="double-click">
                                    <a class="double-click-button margin-none padding-none" title="双击可编辑" href="javascript:;"></a>
                                    <input class="input double-click-edit text-ellipsis" type="text" name="<?php echo $k?>" data-id="<?php echo $item['id']?>" value="<?php echo $item[$k]?>" />
                                </div>
                                </span>
                                <?php }elseif ($lists['th'][$k]['style'] == 'ico_up_rack') {?>
                                    <span class="td">
						        <a class="ico_up_rack <?php if($item['status'] != 1){?>cancel<?php }?>" href="javascript:;" data-id="<?php echo $item['id']?>" title="点击启用，禁用"></a>
					            </span>
                                <?php }else{?>
                                    <span class="td">
						        <span class="td-con"><?php echo $item[$k]; ?></span>
					            </span>
                                <?php }?>
                            <?php }?>
                            <span class="td">
                        <span class="td-con"><a href="<?php echo url('edit',array('id'=>$item['id']))?>">编辑</a></span>
                    </span>
                        </div>
                    <?php }?>
				</div>
			</div>
		</div>
		<script>
			var ajax_status = "<?php echo url('ajax_status')?>";
			var ajax_name = "<?php echo url('ajax_name')?>";
			$(window).load(function(){
				$(".table").resizableColumns();
				$(".paging-table").fixedPaging();
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
				$('input[name=pay_name]').bind('blur',function() {
					var name = $(this).val();
					var id = $(this).attr('data-id');
					list_action.change_name(ajax_name,id,name);
				});
			})
		</script>
<?php include template('footer','admin');?>
