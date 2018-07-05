<?php include template('header','admin');?>

	<form action="<?php echo url('order2/service/'.$url)?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 280px; margin:20px auto;">
	
		<input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
		<?php echo $tishi;?><br>
		<?php echo form::input('textarea', 'remark', '', '备注（最少五个字符）', '', array('nullmsg' => '请填写备注','minlength'=>5)); ?>
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
		dialog.title('<?php echo $title;?>');
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
