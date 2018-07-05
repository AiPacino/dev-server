<?php include template('header','admin');?>

	<form action="<?php echo url('order/admin_order/complete_parcel')?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	
		<input type="hidden" name="id" value="<?php echo $id ?>">
		处理结果：<select><option>寄回用户并买断</option></select><br><br>
		<?php echo form::input('select','delivery_id',$first_key,'物流公司：','',array('items' => $deliverys)) ?>
		<?php echo form::input('text', 'delivery_sn', '','物流单号：','',array('datatype' => '*')); ?>
	
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
		dialog.title('处理异常');
		dialog.reset();     // 重置对话框位置


		var parcel_form = $("form[name='parcel_form']").Validform({
			ajaxPost:true,
			beforeSubmit:function( curform ){
				$.message.start();
			},
			callback:function(ret) {
				if(ret.code == 1) {
					$.message.end( ret.msg, 3 );
					setTimeout(function(){
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
