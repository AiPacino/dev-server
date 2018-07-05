<?php use zuji\order\ServiceStatus;
use zuji\order\OrderStatus;
include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

	<div class="fixed-nav layout">
		<ul>
			<li class="first">即将结束订单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
                        <input type="hidden" name="c" value="service" />
                        <input type="hidden" name="a" value="index" />
                        <div class="order2-list-search clearfix">
                            <div class="form-box clearfix border-bottom-none" >

                                <div class="form-group form-layout-rank">
                                    <span class="label">创建时间：</span>
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
					<span class="th" data-width="20">
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
					   <?php if($item['service_status']==ServiceStatus::ServiceOpen){?>
                           <?php if($promission_arr['close']){?>
					   <a id="bgMain" class="bg-main btn" href='<?php echo url('order2/service/close',array('service_id' => $item["service_id"])); ?>' data-iframe="true" data-iframe-width="300" >关闭</a><br>
					    <?php }
					        if($promission_arr['sendsms']){
					    ?>
                               <a class="bg-main btn" href='<?php echo url('order2/service/sendsms',array('service_id' => $item["service_id"])); ?>' data-iframe="true" data-iframe-width="300" >快到期提醒</a><br>
					   <?php }
					   } ?>
					   
					   <?php if($item['service_status']==ServiceStatus::ServiceClose){?>
					   <!--
					   <a id="bgMain" class="bg-main btn" href='<?php echo url('order2/service/open',array('service_id' => $item["service_id"])); ?>' data-iframe="true" data-iframe-width="300" >开启</a><br>
					   -->
					   <?php }?>
					   <?php 
					        if($promission_arr['detail']){
					   ?>
					   <a href="javascript:;" onclick="order_action.dialog({'title':'服务详情','url':'<?php echo url('order2/service/detail',array('service_id' =>$item["service_id"])); ?>'})">服务详情</a>&emsp;|&emsp;
        	           <?php } ?>
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
	</div>
<?php include template('footer','admin');?>
<script src="<?php echo __ROOT__;?>statics/js/laydate/laydate.js" type="text/javascript"></script>
<script>
	$(window).load(function(){
		$(".table").resizableColumns();
		$(".paging-table").fixedPaging();
		var $val=$("input[type=text]").first().val();
		$("input[type=text]").first().focus().val($val);
	})



</script>
