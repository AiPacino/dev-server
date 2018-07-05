<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/jquery.print.js"></script>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<style type="text/css">
.button_search{
    background-image: -webkit-linear-gradient(top,#ffffff,#e6e6e6);
    background-image: -moz-linear-gradient(top,#ffffff,#e6e6e6);
    background-image: -o-linear-gradient(top,#ffffff,#e6e6e6);
    border: 1px solid #c7c7c7;
    cursor: pointer;
    width: 80px;
    height: 26px;
    line-height: 24px;
    margin-top: 20px;
    margin-bottom: 20px;
}
</style>

	<div class="content padding-big have-fixed-nav">
		<div id="content" class="margin-top">
			<?php echo $info['content']?>
		</div>
	</div>
	<div style="text-align:center;">
    <input type="button" class="button_search" value="开始打印" data-event="start_print" title="点击后会标识该快递单已打印" /> &nbsp;&nbsp;
    <?php if (empty($action)){?>
    <a style="display: inline-block" class="button_search" href="javascript:;" onclick="order_action.dialog({'url':'<?php echo url('order2/delivery/send_alert',array('order_id' => $order_id,'delivery_id'=>$delivery_id)); ?>',width:350})">发货</a> &nbsp;&nbsp;
    <?php }?>
    <input type="button" class="button_search" id="cancel" value="取消" />
	</div>

<script type="text/javascript">
$("[data-event='start_print']").bind("click" ,function(){
    $("#content").jqprint(); 
})
</script>
<script>
	$(".operation").live('click',function(){
		 $(".margin-top").jqprint();
	})
	$(".back").live('click',function(){
		window.history.go(-1);
	})
	$(".delete").live('click',function(){
		var total_num = parseInt($(".total_num").text());
		var number = parseInt($(this).prev().prev().text());
		var total_price = parseFloat($(".total_price").text());
		var total_goods_price = parseFloat($(this).prev().text());
		var new_total_price = total_price - total_goods_price;
		$(".total_num").text(total_num - number);
		$(".total_price").text(new_total_price.toFixed(2));
		$(this).parents("#goodslist").hide();
	})

    $(function(){
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }
        dialog.title('打印租机协议');
        dialog.reset();     // 重置对话框位置

        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });

        $("#cancel").on('click', function () {
            dialog.remove();
            return false;
        });
    })
</script>
<?php include template('footer','admin');?>