<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

	<div class="fixed-nav layout">
		<ul>
			<li class="first">订单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
			<li class="spacer-gray"></li>
            <?php
    			foreach ($tab_list as $tab){
    			    echo '<li>'. $tab .'</li>';
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
                    <form method="get" >
                        <input type="hidden" name="m" value="order2" />
                        <input type="hidden" name="c" value="order" />
                        <input type="hidden" name="a" value="index" />
                        <input type="hidden" name="status" value="<?php echo $_GET['status'];?>" />
                        <div class="order2-list-search clearfix">
                            <div class="form-box clearfix border-bottom-none" >

                                <div class="form-group form-layout-rank">
                                    <span class="label">时间：</span>
                                    <div class="box margin-none">
                                            <?php echo form::calendar('begin_time',!empty($_GET['begin_time']) ? $_GET['begin_time']:'',array('format' => 'YYYY-MM-DD hh:mm'))?>
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
				<?php echo form::input('select','business_key',$_GET['business_key'] ? $_GET['business_key'] : '0','业务类型','',array('css'=>'form-layout-rank','items' => $business_list))?>
                    <?php echo form::input('select','appid',$_GET['appid'] ? $_GET['appid'] : '0','入口','',array('css'=>'form-layout-rank','items' => $appid_list))?>
                    <?php echo form::input('select','remark_id',$_GET['remark_id'] ? $_GET['remark_id'] : 0,'回访标识','',array('css'=>'form-layout-rank','items' => $beizhu_list))?>
                                <?php echo form::input('select','kw_type',$_GET['kw_type'] ? $_GET['kw_type'] : 'order_no','搜索','',array('css'=>'form-layout-rank','items' => $keywords_type_list))?>

                                <div class="form-group form-layout-rank">
                                    <div class="box keywords margin-none">
                                            <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo !empty($_GET['keywords'])?$_GET['keywords'] :''?>">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="status" value="<?php echo $_GET['status']; ?>">
                            <input class="button bg-sub fl" value="查询" type="submit">
                            <input id="export" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="导出" /> 
                            <input id="speed-export" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="快速导出" /> 
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
					<span class="th" data-width="9">
						<span class="td-con">操作</span>
					</span>
				</div>
				<?php foreach ($lists['lists'] AS $item) {?>
				    <div class="tr <?php //echo $item['locked']?'tr-disabled':''?>">
				    <?php foreach ($lists['th'] AS $k=>$th) {?>
					<span class="td">
						<?php if( $k == 'mobile' ){?>
							<a href="javascript:;" onclick="order_action.dialog({'title':'用户详情','url':'<?php echo url('member/member/detail',array('id' =>$item["user_id"])); ?>'})"><?php echo $item[$k]; ?></a>
						<?php }else{ ?>
							<?php echo $item[$k]; ?>
						<?php }?>
					</span>
				    <?php }?>
					<span class="td">
                        <div class="btn-list">
                        <a href="javascript:;" onclick="order_action.dialog({'title':'订单详情','url':'<?php echo url('order2/order/detail',array('order_id' =>$item["order_id"])); ?>'})">查看</a>
                        <?php foreach ($item['operation_list'] as $operation):?>
                        <br><a class="bg-main btn" href ='<?php echo url(trim($operation['mca']),$operation['params']); ?>' data-iframe="true" data-iframe-width=<?php echo $operation['iframe_width'];?>><?php echo $operation['name'];?></a>
                        <?php endforeach;?>
						<?php if( $item['allowed_repairl'] && $promission_arr['weixiu'] ){?>
							<br/><a class="bg-main btn" href='<?php echo url('weixiu/weixiu/create_record',array('order_id' => $item["order_id"])); ?>' data-iframe="true" data-iframe-width="350">录入维修</a>
						<?php } ?>
					    </div>
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

		//导出
		$('#export').click(function(){
			var status = $('#status').val();
			//获取导出的开始时间
			var begin_time = $('input[name="begin_time"]').val();
			var end_time 	= $('input[name="end_time"]').val();
			window.location.href = "<?php echo url('diff_order_export')?>"+ "&begin_time=" + begin_time + "&end_time=" + end_time  + "&status=" + status ;
		});
		//快速导出
		$('#speed-export').click(function(){
			var status = $('#status').val();
			//获取导出的开始时间
			var begin_time = $('input[name="begin_time"]').val();
			var end_time 	= $('input[name="end_time"]').val();
			window.location.href = "<?php echo url('diff_order_export_speed')?>"+ "&begin_time=" + begin_time + "&end_time=" + end_time  + "&status=" + status ;
		});
	})

</script>
