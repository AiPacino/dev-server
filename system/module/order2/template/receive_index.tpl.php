<?php use zuji\order\ReceiveStatus;
use zuji\order\OrderStatus;
include template('header','admin');?>
	<script type="text/javascript" src="./statics/js/goods/goods_cat.js" ></script>
	<script type="text/javascript" src="./statics/js/goods/goods_list.js"></script>
	<script type="text/javascript" src="./statics/js/template.js" ></script>
<script type="text/javascript" src="./statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
		<ul>
			<li class="first">全部收货列表管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
                <input type="hidden" name="c" value="receive" />
                <input type="hidden" name="a" value="index" />
                <div class="order2-list-search clearfix">
                    <div class="form-box clearfix border-bottom-none" >
                        <?php echo form::input('select','time_type',$_GET['time_type'] ? $_GET['time_type'] : 'create_time','时间','',array('css'=>'form-layout-rank','items' => ["create_time"=>"创建时间","receive_time"=>"收货时间"]))?>                
                        <div class="form-group form-layout-rank">
                            <span class="label"></span>   
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

                        <?php // echo form::input('select','payment_channel',$_GET['payment_channel'] ? $_GET['payment_channel'] : '0','支付渠道','',array('css'=>'form-layout-rank','items' => $pay_channel_list))?>

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
					<span class="td">
    					    <?php if($lists['th'][$k]['style'] == 'double_click'){?>
    					    <div class="td-con">
    							<div class="double-click">
    								<a class="double-click-button margin-none padding-none" title="双击可编辑" href="javascript:;"></a>
    								<input class="input double-click-edit text-ellipsis text-center" type="text" name="<?php echo $k?>" data-id="<?php echo $item["receive_id"]?>" value="<?php echo $item[$k]?>" />
    							</div>
    						</div>
    						
    					    <?php }else if($lists['th'][$k]['style'] == 'date'){
    					               if($item[$k]==0)
    					                   echo "";
    					               else 
    					                   echo date("Y-m-d H:i:s",$item[$k]); 
    					    }else{ 
    					                echo $item[$k]; 
    					    }?>
    					</span>
    				    <?php }?>
    				    <span class="td" >
        					<div class="btn-list">
        					<?php if($item['receive_confirmed']){?>
        					<a class="bg-main btn" href='<?php echo url("order2/receive/receive_confirmed",array("receive_id"=>$item["receive_id"])); ?>' data-iframe="true" data-iframe-width="300" >收货</a>&emsp;<br>
        	                <?php }?>
        	                <?php if ($item['create_evaluation'] && $item['evaluation_id'] >0) { ?>
                			<a class="bg-main btn" href='<?php echo url("order2/receive/create_evaluation",array("order_id"=>$item["order_id"])); ?>' data-iframe="true" data-iframe-width="300" >生成检测单</a>&emsp;<br>
                			<?php }?>
        	                <a href="javascript:;" onclick="order_action.dialog({'title':'收货详情','url':'<?php echo url('order2/receive/detail',array('receive_id' =>$item["receive_id"])); ?>'})">收货详情</a><br>
                                <a href="javascript:;" onclick="order_action.dialog({'title':'物流详情','url':'<?php echo url('order2/wuliu/detail',array('receive_id' =>$item["receive_id"])); ?>'})">物流详情</a><br/>
        	                 <a href='<?php echo url('order2/order/detail',array('order_id' => $item["order_id"])); ?>' data-iframe="true" data-iframe-width="1000">订单详情</a> 
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
		<input onclick="order_action.daochu('<?php echo url("order2/total_take/daochu",array("id"=>$list['id'])); ?>');" class="button bg-sub margin-top " type="daochu" style="height: 26px; line-height: 14px;" value="导出">
	</div>
<?php include template('footer','admin');?>

<script>
	var edit_wuliu_no= "<?php echo url('edit_wuliu_no')?>";
	var edit_bar_code= "<?php echo url('edit_bar_code')?>";
	$(".form-group .box").addClass("margin-none");
	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		var $val=$("input[type=text]").first().val();
		$("input[type=text]").first().focus().val($val);

		$('input[name=wuliu_no]').bind('blur',function() {
			var name = $(this).val();
			var id = $(this).attr('data-id');
			list_action.change_name(edit_wuliu_no,id,name);
		});

		$('input[name=bar_code]').bind('blur',function() {
			var name = $(this).val();
			var id = $(this).attr('data-id');
			list_action.change_name(edit_bar_code,id,name);
		});
	})
</script>
