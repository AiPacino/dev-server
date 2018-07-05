<?php use zuji\order\RefundStatus;
use zuji\order\OrderStatus;
include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
		<ul>
			<li class="first">退款单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
                <p>- </p>
			</div>
		</div>
		<div class="hr-gray"></div>
        <div class="clearfix">
            <form method="get" >
                <input type="hidden" name="m" value="order2" />
                <input type="hidden" name="c" value="refund" />
                <input type="hidden" name="a" value="index" />
                <div class="order2-list-search clearfix">
                    <div class="form-box clearfix border-bottom-none" >
                        <div class="form-group form-layout-rank">
                            <span class="label">退款时间：</span>
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
                        <?php echo form::input('select','payment_channel_id',$_GET['payment_channel_id'] ? $_GET['payment_channel_id'] : 'All','支付渠道','',array('css'=>'form-layout-rank','items' => $pay_channel_list))?>
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
					    <?php if($lists['th'][$k]['style'] == 'date'){
					               if($item[$k]==0)
					                   echo "";
					               else 
					                   echo date("Y-m-d H:i:s",$item[$k]); 
					    }else{ 
					                   echo $item[$k]; 
					    }?>
					</span>
				    <?php }?>
					<span class="td">
					   <div class="btn-list">
					   <?php if($item['allow_should']){?>
        					<a class="bg-main btn" href='<?php echo url("order2/refund/refund_should",array("refund_id"=>$item["refund_id"])); ?>' data-iframe="true" data-iframe-width="300" >修改退款额</a>&emsp;<br>
                        <?php }?>
 
        				<?php if($item['allow_refund']){?>
        					<a class="bg-main btn" href='<?php echo url("order2/refund/refund_confirm",array("refund_id"=>$item["refund_id"])); ?>' data-iframe="true" data-iframe-width="300" >退款</a>&emsp;<br>
                        <?php }?>
                            <a href="javascript:;" onclick="order_action.dialog({'title':'退款详情','url':'<?php echo url('order2/refund/detail',array('refund_id' =>$item["refund_id"])); ?>'})">退款详情</a><br>
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
	<!--  	<input onclick="" class="button bg-sub margin-top " type="daochu" style="height: 26px; line-height: 14px;" value="导出">-->
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
	
		function ajax_getdata(days ,return_time,status,keyword,mohu) {
		$.ajax({
			type: "get",
			async: false,
			//同步执行
			url: "order2/refund/index",
			dataType: "json",
			data:{return_time:return_time,status:status,keyword:keyword,mohu:mohu,formhash:formhash},
			success: function(result) {
				if (result.status == 1) {
					showchart(result.result.search);
				} else {
					message("请求数据失败，请稍后再试!");
				}
			},
			error: function(errorMsg) {
				message("请求数据失败，请稍后再试!");
			}
		});
	}

	$(function(){
		$('.count-date a').on('click',function(){
			$(this).addClass('current').siblings().removeClass('current');
		})

		$('#submit').bind('click',function(){
			var keyword = $('input[name="keyword"]').val();
			var return_time = $('input[name="return_time"]').val();
			var status = $('input[name="status"]').val();
			var mohu = $('input[name="mohu"]').val();
			
			if (keyword) {
				ajax_getdata('',return_time,status,keyword,mohu);
			}

		})
	})
	
</script>
