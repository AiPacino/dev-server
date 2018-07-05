<?php include template('header','admin');?>

	<form action="<?php echo url('order2/delivery/delivery_return_refuse',['delivery_id'=>$delivery_id])?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
		<input type="hidden" name="delivery_id" value="<?php echo $delivery_id ?>"> 
	    <div class="form-group">
		是否确认客户已经回寄拒签？<br>
	    </div>
	</div>
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	    <?php echo form::input('textarea', 'refuse_remark', '', '备注（最少5个字符）', '', array('datatype' => '*','nullmsg' => '请填写备注','minlength'=>5)); ?>	
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
		dialog.title('确认回寄拒签操作');
		dialog.reset();     // 重置对话框位置


		var parcel_form = $("form[name='parcel_form']").Validform({
			ajaxPost:true,
			beforeSubmit:function( curform ){
				$.message.start();
			},
			callback:function(ret) {
				if(ret.status == 1) {
					$.message.end( ret.message, 3 );
					setTimeout(function(){
						window.top.main_frame.location.reload();
					}, 2000);
				}else{
					$.message.error( ret.message, 4 );
				}

				return false;
			}
		})



		// $("#okbtn").live('click',function(){
		// 	if($("textarea[name=log]").val() == ''){
		// 		alert('请填写更改原因');
		// 		return false;
		// 	}
		// 	var url = "<?php echo url('order/admin_order/complete_parcel')?>";
		// 	var status = '' ;
		// 	$("input[name=status]").each(function(){
		// 		if($(this).attr('checked') == 'checked'){
		// 			status = $(this).val();
		// 		}
		// 	})
		// 	var date = {
		// 		"id":<?php echo $_GET['id']?>,
		// 		"log":$("textarea[name=log]").val(),
		// 		"status":status
		// 	}
		// 	$.post(url,date,function(data){
		// 		if(data.status == 1){
		// 			dialog.close(data);
		// 			dialog.remove();
		// 			return true;
		// 		}else{
		// 			alert('操作失败');
		// 			dialog.remove();
		// 			return false;
		// 		}
		// 	},'json')
		// })
		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
