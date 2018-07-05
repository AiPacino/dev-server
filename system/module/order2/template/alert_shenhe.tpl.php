<?php

use zuji\order\ReturnStatus;

include template('header', 'admin');
?>

<form action="<?php echo url('order2/return/check') ?>" method="POST" name="parcel_form" >
    <div class="form-box border-bottom-none order-eidt-popup clearfix">
	<input type="hidden" name="return_id" value="<?php echo $return_id ?>">
	<input type="hidden" name="order_id" value="<?php echo $return_info['order_id'] ?>">

	    <?php echo form::input('select', 'return_status', ReturnStatus::ReturnAgreed, '是否同意用户退货：', '', array('items' => [ReturnStatus::ReturnAgreed => "同意",ReturnStatus::ReturnDenied => "拒绝", ReturnStatus::ReturnHuanhuo =>ReturnStatus::getStatusName(ReturnStatus::ReturnHuanhuo)])) ?>

	    <?php echo form::input('text', 'return_check_remark', '', '备注（最少五个字符）', '', array('datatype' => '*','nullmsg' => '请填写备注','minlength'=>5)); ?>
    </div>
    <div class="padding text-right ui-dialog-footer">
	<input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
	<input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
    </div>
</form>
<?php include template('footer', 'admin'); ?>
<script>

    $(function() {
	try {
	    var dialog = top.dialog.get(window);
	} catch (e) {
	    return;
	}
	var $val = $("textarea").first().text();
	$("textarea").first().focus().text($val);
	dialog.title('退货单审核');
	dialog.reset();     // 重置对话框位置


	var parcel_form = $("form[name='parcel_form']").Validform({
		ajaxPost:true,
		beforeSubmit:function( curform ){
			$.message.start();
		},
		callback:function(ret) {
//                dialog.content(ret.msg);
			if(ret.status == 1) {
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
	// $("#okbtn").live('click',function(){
	// 	if($("textarea[name=log]").val() == ''){
	// 		alert('请填写更改原因');
	// 		return false;
	// 	}
	// 	var url = "<?php echo url('order/admin_order/complete_parcel') ?>";
	// 	var status = '' ;
	// 	$("input[name=status]").each(function(){
	// 		if($(this).attr('checked') == 'checked'){
	// 			status = $(this).val();
	// 		}
	// 	})
	// 	var date = {
	// 		"id":<?php echo $_GET['id'] ?>,
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
	$('#closebtn').on('click', function() {
	    dialog.remove();
	    return false;
	});
    })
</script>
