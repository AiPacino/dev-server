<?php include template('header','admin');?>

	<form action="<?php echo url('weixiu/weixiu/create_record')?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix form-block"  style="width: 280px; margin:20px auto;">
		<input type="hidden" name="order_id" value="<?php echo $order_id ?>">
		<?php echo form::input('textarea', 'reason_name', '', '本次维修内容：', '', array('datatype' => '*', 'nullmsg' => '请填写维修内容')); ?>
        <div class="form-group">
            <span class="label">维修时间：</span>
            <div class="box">
            <?php echo form::calendar('repair_time', '');?>
            </div>
        </div>
	</div>
	<div class="padding text-right ui-dialog-footer">
		<input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" name ="dosubmit" data-reset="false"/>
		<input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
	</div>
	</form>
<?php include template('footer','admin');?>
<style>
    .form-group{
       padding:20px 0;
    }
</style>
<script>

	$(function(){
		try {
			var dialog = top.dialog.get(window);
		} catch (e) {
			return;
		}
		var $val=$("textarea").first().text();
		$("textarea").first().focus().text($val);
		dialog.title('录入维修记录');
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
					}, 1000);
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
