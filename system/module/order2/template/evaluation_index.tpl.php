<?php 
use zuji\order\EvaluationStatus;
use zuji\order\OrderStatus;
use zuji\Business;
include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
		<ul>
			<li class="first">检测单列表<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
			<li class="spacer-gray"></li>
			<?php
            	foreach ($tab_list as $tab) {
            	    echo '<li>' . $tab . '</li>';
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
			<form>
				<input type="hidden" name="m" value="order2" />
				<input type="hidden" name="c" value="evaluation" />
				<input type="hidden" name="a" value="index" />

                        <div class="order2-list-search clearfix">
                            <div class="form-box clearfix border-bottom-none" >

                                <div class="form-group form-layout-rank">
                                    <span class="label">检测时间：</span>
                                    <div class="box margin-none">
                                            <?php echo form::calendar('start',!empty($_GET['start']) ? $_GET['start']:'',array('format' => 'YYYY-MM-DD hh:mm'))?>
                                    </div>
                                </div>

                                <div class="form-group form-layout-rank">
                                    <span class="label">~</span>
                                    <div class="box margin-none">
                                            <?php echo form::calendar('end',!empty($_GET['end'])? $_GET['end']:'',array('format' => 'YYYY-MM-DD hh:mm'))?>
                                    </div>
                                </div>

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
                                        <?php echo $item[$k]; ?>
                                    </span>
                                <?php }?>
                                    <span class="td" >
                                        <div class="btn-list">
                                            <?php if($item['allow_to_create_refund']){?>
                                                <a class="bg-main btn" href="javascript:;" onclick="order_action.dialog({'title':'申请退款','url':'<?php echo url('order2/refund/create_refund', ['order_id' =>$item["order_id"]])?>'})">申请退款</a><br/>
                                            <?php }?>
                                            <?php if($item['allow_to_remove_authorize']){?>
                                                <a class="bg-main btn" href="javascript:;" onclick="order_action.dialog({'title':'解除资金预授权','url':'<?php echo url('order2/order/remove_authorize', ['order_id' =>$item["order_id"]])?>',width:300})">解除资金预授权</a><br/>
                                            <?php }?>
                                            <?php if($item['allow_create_delivery']){?>
                                            <a class="bg-main btn" href='<?php echo url("order2/evaluation/alert_delivery",array("order_id"=>$item["order_id"],"evaluation_id"=>$item["evaluation_id"])); ?>' data-iframe="true" data-iframe-width="680" >生成发货单</a><br>
                                            <?php }?>
                                            <?php if( $item['allow_isqualified'] ){ ?>
                                                <a class="bg-main btn" onclick="common_action.alerts('<?php echo url('order2/evaluation/isqualified_alert',array('order_id' => $item["order_id"],'evaluation_id'=>$item['evaluation_id'])); ?>');"href="javascript:;">检测</a><br>
                                            <?php } ?>
                    					    <?php if($item['evaluation_status'] == EvaluationStatus::EvaluationFinished){?>
                    					    <a href="javascript:;" onclick="order_action.dialog({'title':'检测详情','url':'<?php echo url('order2/evaluation/detail',array('evaluation_id' =>$item["evaluation_id"])); ?>'})">检测详情</a><br>
                    					    <?php }?>
                                            <a class="" href="javascript:;" onclick="order_action.dialog({'title':'订单详情','url':'<?php echo url('order2/order/detail',array('order_id' =>$item["order_id"])); ?>'})">订单详情</a>
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
        //提交检测结果按钮
        $("#create_refund").live('click',function(){
            //确认检测结果的url
            var url = "<?php echo url('order2/evaluation/create_refund')?>";
            var order_id = $(this).attr('index');
            var date = {
                "order_id":order_id
            }
            //数据提交
            $.post(url,date,function( data ){
                note_html = '<div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 300px; margin:10px auto;"><div> \
                                    '+ data.msg +' \
                                </div></div>';
                
                order_action.dialog({
                    'title':'生成退款单信息',
                    'content':note_html,
                    'width':'300px',
                    'height':'60px',
                });
            },'json');
        })
</script>
