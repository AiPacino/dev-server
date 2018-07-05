<?php include template('header','admin');?>

	<form action="<?php echo url('order2/delivery/delivery_confirmed',['delivery_id'=>$delivery_id])?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 280px; margin:20px auto;">
		<input type="hidden" name="delivery_id" value="<?php echo $delivery_id ?>"> 

		<div class="form-group form-layout-rank">
		    <span class="label">签收时间：</span>
		    <div class="box margin-none">
		      <?php echo form::calendar('confirm_time',date('Y-m-d H:i:s'),array('format' => 'YYYY-MM-DD hh:mm:ss'))?>
		    </div>
		</div>
		<?php echo form::input('textarea', 'confirm_remark', '', '备注（最少5个字符）', '', array('nullmsg' => '请填写备注','datatype' => '*','minlength'=>5)); ?>	
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
		dialog.title('确认收货');
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

		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
