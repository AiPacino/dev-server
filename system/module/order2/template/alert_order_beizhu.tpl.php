<?php include template('header','admin');?>

	<form action="<?php echo url('order2/order/order_beizhu_edit',['order_id'=>$order_id])?>" method="POST" name="parcel_form">
        <div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 300px; margin:30px auto;">
            请填写回访信息和备注<br>
            <?php echo form::input('select','remark_id',$order_info['remark_id'],'回访信息：','',array('items' => $beizhu_list,)) ?>
            <?php echo form::input('textarea', 'remark', '', '备注（最少五个字符）', '', array('datatype' => '*', 'nullmsg' => '请填写备注','minlength'=>5)); ?>
        </div>
        <div class="padding text-right ui-dialog-footer">
            <?php if($order_info['order_id']){?>
            <input type="hidden" name="order_id" value="<?php echo $order_info['order_id'];?>">
            <input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
            <?php }?>
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
		dialog.title('订单回访备注');
		dialog.reset();     // 重置对话框位置


		var parcel_form = $("form[name='parcel_form']").Validform({
			ajaxPost:true,
			beforeSubmit:function( curform ){
				$.message.start();
			},
			callback:function(ret) {
//                dialog.content(ret.msg);
				if(ret.status == 1) {
					$.message.end( ret.message, 2 );
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
