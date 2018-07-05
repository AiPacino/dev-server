<?php use zuji\order\DeliveryStatus;
use zuji\order\OrderStatus;
include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
		<ul>
			<li class="first">发货单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
				<p>-</p>
			</div>
		</div>
		<div class="hr-gray"></div>
		<div class="clearfix">
			<form>
				<input type="hidden" name="m" value="order2" />
				<input type="hidden" name="c" value="delivery" />
				<input type="hidden" name="a" value="index" />
                        <div class="order2-list-search clearfix">
                            <div class="form-box clearfix border-bottom-none" >

                                <div class="form-group form-layout-rank">
                                    <span class="label">发货时间：</span>
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
                                <?php echo form::input('select','business_key',$_GET['business_key'] ? $_GET['business_key'] : '0','业务类型','',array('css'=>'form-layout-rank','items' => $business_list))?>
                                <?php echo form::input('select','kw_type',$_GET['kw_type'] ? $_GET['kw_type'] : 'order_no','搜索','',array('css'=>'form-layout-rank','items' => $keywords_type_list))?>

                                <div class="form-group form-layout-rank">
                                    <div class="box keywords margin-none">
                                            <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo !empty($_GET['keywords'])?$_GET['keywords'] :''?>">
                                    </div>
                                </div>
                            </div>
                            <input class="button bg-sub fl" value="查询" type="submit">
                            <input id="export" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="导出" /> 
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
                            <div class="tr" id="<?php echo $item['delivery_id'];?>">
                                <?php foreach ($lists['th'] AS $k=>$th) {?>
                                    <span class="td">
                                        <?php echo $item[$k]; ?>
                                    </span>
                                <?php }?>
                                <span class="td" >
                                    <div class="btn-list">
                                    <?php if($item['order_status'] == OrderStatus::OrderCreated){?>
                                        <?php  if( $item['allow_to_deliver'] ){
                                            if($item['business_key'] == zuji\Business::BUSINESS_ZUJI){
                                                $url = 'order2/delivery/prints';
                                            }elseif($item['business_key'] == zuji\Business::BUSINESS_HUIJI){
                                                $url = 'order2/delivery/send_alert';
                                            }elseif($item['business_key'] ==zuji\Business::BUSINESS_HUANHUO){
                                                $url ='order2/delivery/send_alert';
                                            }
                                            ?>
					<a class="bg-main btn" href="javascript:;"  onclick="order_action.dialog({'url':'<?php echo url($url,array('order_id' => $item["order_id"],'delivery_id'=>$item['delivery_id'])); ?>'})">发货</a> <br/>
                                        <?php }?>
                                    <?php }?>

                                        <?php  if( $item['allow_to_edit_deliver'] ){?>
                                            <a class="bg-main btn" href="javascript:;"  onclick="order_action.dialog({'url':'<?php echo url('order2/delivery/edit_delivery',array('order_id' => $item["order_id"],'delivery_id'=>$item['delivery_id'])); ?>',width:300})">修改发货</a> <br/>
                                        <?php }?>


                                            <a href="javascript:;" onclick="order_action.dialog({'title':'发货详情','url':'<?php echo url('order2/delivery/detail',array('delivery_id' =>$item["delivery_id"])); ?>'})">发货详情</a><br/>
                                            <a href="javascript:;" onclick="order_action.dialog({'title':'物流详情','url':'<?php echo url('order2/wuliu/detail',array('delivery_id' =>$item["delivery_id"])); ?>'})">物流详情</a><br/>
					    <a href='<?php echo url('order2/order/detail',array('order_id' => $item["order_id"])); ?>' data-iframe="true" data-iframe-width="1000">订单详情</a> <br/>
                                        <?php if($promission_arr['allow_to_prints']){?>
					    <a href="javascript:;"  onclick="order_action.dialog({'url':'<?php echo url('order2/delivery/prints',array('order_id' => $item["order_id"], 'delivery_id' => $item['delivery_id'], 'action' => 'print')); ?>'})">打印租机协议</a>
                                        <?php }?>
                                    </div>
                                </span>
                            </div>
                        <?php }?>
                        <!-- 分页 -->
                        <div class="paging padding-tb body-bg clearfix">
                                <ul class="fr"><?php echo $lists['pages']; ?></ul>
                                <div class="clear"></div>
                        </div>
                    </div>
		</div>

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

		function ajax_getdata(days ,keyword,mohu) {
		$.ajax({
			type: "get",
			async: false,
			//同步执行
			url: "order2/delivery/index",
			dataType: "json",
			data:{keyword:keyword,mohu:mohu,formhash:formhash},
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
			var mohu = $('input[name="mohu"]').val();
			if (keyword) {
				ajax_getdata('',keyword,mohu);
			}

		})
		//导出
		$('#export').click(function(){
			//获取导出的开始时间
			var begin_time = $('input[name="begin_time"]').val();
			var end_time 	= $('input[name="end_time"]').val();
			window.location.href = "<?php echo url('delivery_order_export')?>"+ "&begin_time=" + begin_time + "&end_time=" + end_time;
		});
	})

</script>
