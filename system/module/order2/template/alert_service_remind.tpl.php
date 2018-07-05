<?php include template('header','admin');?>

	<form action="<?php echo url('order2/service/sendsms')?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 200px; margin:30px auto;">
	
		<input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
		是否短信提醒？<br>
	</div>
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
		var $val=$("textarea").first().text();
		$("textarea").first().focus().text($val);
		dialog.title('关闭服务提醒');
		dialog.reset();     // 重置对话框位置

		var parcel_form = $("form[name='parcel_form']").Validform({
			ajaxPost:true,
			beforeSubmit:function( curform ){
				$.message.start();
			},
			callback:function(ret) {
//                dialog.content(ret.msg);
				if(ret.status == 1) {
					$.message.end( ret.message, 3 );
					setTimeout(function(){
//						dialog.close('reload');
						window.top.main_frame.location.reload();
					}, 2000);
				}else{
					$.message.error( ret.message, 4 );
				}

				return false;
			}
		})

		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
