<?php include template('header','admin');?>

	<form action="<?php echo url('order2/service/close')?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 280px; margin:20px auto;">
	
		<input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
		是否关闭该服务单？<br>
		
		<?php echo form::input('textarea', 'remark', '', '关闭备注（最少五个字符）', '', array('datatype' => '*','nullmsg' => '请填写备注','minlength'=>5)); ?>
	</div>
	<div class="padding text-right ui-dialog-footer">
		<input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
		<input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
	</div>
	</form>
<?php include template('footer','admin');?>
<script>
function check_num(v){
	var a=/^[0-9]*(\.[0-9]{1,2})?$/;
	if(!a.test(v))
	{
	    alert("格式不正确");
	    return false;
	}
}
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
				if(ret.code == 1) {
					$.message.end( ret.msg, 3 );
					setTimeout(function(){
//						dialog.close('reload');
						window.top.main_frame.location.reload();
					}, 2000);
				}else{
					$.message.error( ret.msg, 4 );
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
