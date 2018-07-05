<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

	<div class="fixed-nav layout">
		<ul>
			<li class="first">资金授权管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
                        <input type="hidden" name="m" value="<?php echo MODULE_NAME;?>" />
                        <input type="hidden" name="c" value="<?php echo CONTROL_NAME;?>" />
                        <input type="hidden" name="a" value="<?php echo METHOD_NAME;?>" />
                        <div class="order2-list-search clearfix">
                            <div class="form-box clearfix border-bottom-none" >

                                <div class="form-group form-layout-rank">
                                    <span class="label">时间：</span>
                                    <div class="box margin-none">
                                            <?php echo form::calendar('begin_time',!empty($_GET['begin_time']) ? $_GET['begin_time']:'',array('format' => 'YYYY-MM-DD'))?>
                                    </div>
                                </div>

                                <div class="form-group form-layout-rank">
                                    <span class="label">~</span>
                                    <div class="box margin-none">
                                            <?php echo form::calendar('end_time',!empty($_GET['end_time'])? $_GET['end_time']:'',array('format' => 'YYYY-MM-DD'))?>
                                    </div>
                                </div>
							</div>
							<div class="form-box clearfix border-bottom-none" >
								<?php echo form::input('select','business_key',$_GET['business_key'] ? $_GET['business_key'] : '0','业务类型','',array('css'=>'form-layout-rank','items' => $business_list))?>

                                <?php echo form::input('select','kw_type',$_GET['kw_type'] ? $_GET['kw_type'] : 'order_no','搜索','',array('css'=>'form-layout-rank','items' => $keywords_type_list))?>

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
					<?php foreach ($data_table['th'] AS $th) {?>
					<span class="th" data-width="<?php echo $th['length']?>">
						<span class="td-con"><?php echo $th['title']?></span>
					</span>
					<?php }?>
					<span class="th" data-width="10">
						<span class="td-con">操作</span>
					</span>
				</div>
				<?php foreach ($data_table['record_list'] AS $item) {?>
				    <div class="tr">
				    <?php foreach ($data_table['th'] AS $k=>$th) {?>
					<span class="td">
					    <?php echo $item[$k]; ?>
					</span>
				    <?php }?>
					<span class="td btn-list">
					    <?php if( $item['jiedong'] ){?>
					    <a class="btn bg-sub jiedong" href="javascript:;" class="jiedong" data-id="<?php echo $item['auth_id'];?>">解冻</a> 
					    <?php }?>
					    
					    <?php if( $item['zhifu'] ){?>
					    <a class="btn bg-sub zhifu" href="javascript:;" class="zhifu" data-id="<?php echo $item['auth_id'];?>">转支付</a>
					    <?php }?>
						<a href="javascript:;" onclick="order_action.dialog({'title':'资金预授权操作列表','url':'<?php echo url('payment/fundauth/jilu',array('auth_id' =>$item["auth_id"])); ?>'})">记录</a>

						<?php if( $item['yajinjiedong']){?>
						<a  href="javascript:;" onclick="order_action.dialog({'title':'押金解冻','url':'<?php echo url('payment/fundauth/deposit_unfreeze',array('auth_id' =>$item["auth_id"])); ?>','width':300})">| 解冻押金</a>
						<?php }?>

						<?php if( $item['yajinzhifu']){?>
						<a  href="javascript:;" onclick="order_action.dialog({'title':'解冻押金','url':'<?php echo url('payment/fundauth/deposit_unfreeze_topay',array('auth_id' =>$item["auth_id"])); ?>','width':300})">| 押金转支付</a>
						<?php }?>

					</span>
				    </div>
				<?php }?>
				<!-- 分页 -->
				<div class="paging padding-tb body-bg clearfix">
					<ul class="fr"><?php echo $data_table['pages']; ?></ul>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	</div>
<?php include template('footer','admin');?>
<script src="<?php echo __ROOT__;?>statics/js/laydate/laydate.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/template.js" ></script>

<script id="queren-dialog" type="text/html">
    <div class="clearfix" style="padding: 20px">
	<form method="POST" >
	    <div class="order2-list-search clearfix">
		<div class="form-box clearfix border-bottom-none" >
		    <?php echo form::input('checkbox','queren',0,'','',array('css'=>'form-layout-rank','items' => ['1'=>'确认解冻']))?>
		</div>
	    </div>
	</form>
    </div>
</script>
<script>
	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		var $val=$("input[type=text]").first().val();
		$("input[type=text]").first().focus().val($val);
	})
	$(function(){
	    
	    var flag = true;
	    $('.jiedong').click(function(){
			if( !flag ){
				return false;
			}
			var id = $(this).attr('data-id');
    		var jiedong_dialog = dialog({
				width: 300,
				height: 100,
				title: '确认解冻',
				content: template('queren-dialog', {'id': id}),
				okValue: '确定',
				ok: function () {
					var _dialog = this;
					$.post("<?php echo  url('payment/fundauth/unfreeze');?>",{auth_id:id},function(data){
					_dialog.title('提交中…');
					_dialog.content(data);
					setTimeout(function(){

					},1000);

					});
					return false;
				},
				cancelValue: '取消',
				cancel: function () {}
			});
			jiedong_dialog.show();
	    });
	    
	    $('.zhifu').click(function(){
			if( !flag ){
				return false;
			}
			var id = $(this).attr('data-id');
				var jiedong_dialog = dialog({
				width: 300,
				height: 100,
				title: '确认解冻转支付',
				content: template('queren-dialog', {'id': id}),
				okValue: '确定',
				ok: function () {
					var _dialog = this;
					$.post("<?php echo  url('payment/fundauth/unfreeze_and_pay');?>",{auth_id:id},function(data){
					_dialog.title('提交中…');
					_dialog.content(data);
					setTimeout(function(){

					},1000);

					});
					return false;
				},
				cancelValue: '取消',
				cancel: function () {}
			});
			jiedong_dialog.show();
	    });
		
	});

</script>
