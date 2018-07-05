<?php include template('header','admin');?>

	<form action="<?php echo url('order2/delivery/create_protocol')?>" method="POST" name="parcel_form">
        <div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 200px; margin:30px auto;">
                     是否生成租机协议？
        </div>
        <input type="hidden" name="order_id" value="<?php echo $order_id?>">
        <input type="hidden" name="delivery_id" value="<?php echo $delivery_id?>">
        <div class="padding text-right ui-dialog-footer">
            <input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
            <input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
        </div>
	</form>
<?php include template('footer','admin');?>
<script>
	$(function(){
		try {
			var dialog = top.dialog.get(window);
		} catch (e) {
			return;
		}
		dialog.title('确认取消');
		dialog.reset();     // 重置对话框位置


		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
