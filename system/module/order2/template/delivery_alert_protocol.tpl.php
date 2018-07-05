<?php include template('header','admin');?>

	<form name="pay_form" method="post">
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
            <br>
            <div style="margin: 20px 0 20px 0;"><?php echo $protocol_no;?></div>
            <br>
	</div>
	<div class="padding text-right ui-dialog-footer">
		<input type="button" class="button bg-main" id="closebtn" value="确定"  data-reset="false"/>
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

		dialog.title('生成租机协议');
		var obj_validform = $("form[name='pay_form']").Validform({
			ajaxPost:true,
			dragonfly:true,
			callback:function(ret) {
				message(ret.message);
				if(ret.status == 1) {
					setTimeout(function(){
						window.top.main_frame.location.reload();
					}, 2000);
				}
				return false;
			}
		})

		$("#closebtn").click(function(){ dialog.close();return false;})


	})
</script>