<?php

use zuji\order\ReturnStatus;
use zuji\order\PaymentStatus;

include template('header', 'admin');
?>

<form action="<?php echo url('order2/payment/check') ?>" method="POST" name="parcel_form" >
    <div class="form-box border-bottom-none order-eidt-popup clearfix">
	<input type="hidden" name="payment_id" value="<?php echo $payment_id ?>">
	    <?php echo form::input('select', 'apply_status',PaymentStatus::PaymentApplySuccessful, '是否同意退款：', '', array('items' => [PaymentStatus::PaymentApplySuccessful => "同意", PaymentStatus::PaymentApplyFailed => "拒绝"])) ?>

	<!-- 当选择拒绝时  出现拒绝理由 -->
	<div style="display: none" id="case">
	    <?php echo form::input('text', 'admin_remark', '', '审核备注（最少五个字符）', '', array('nullmsg' => '请填写审核备注','minlength'=>5)); ?>
	</div>
        <div style="height: 150px;"></div>

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
	dialog.title('支付退款审核');
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
					window.top.main_frame.location.reload();
				}, 2000);
			}else{
				$.message.error( ret.msg, 4 );
			}

			return false;
		}
	})

	$("input[name=apply_status]").change(function() {
	    var status = $("input[name=apply_status]").val();
	    if (status ==<?php echo PaymentStatus::PaymentApplyFailed ?>) {
		$("#case").show();
	    } else {
		$("#case").hide();
	    }
	});

	$('#closebtn').on('click', function() {
	    dialog.remove();
	    return false;
	});
    })
</script>
