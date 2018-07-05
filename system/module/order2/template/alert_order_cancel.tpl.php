<?php include template('header','admin');?>
<div id="dialog_box">
    <form action="<?php echo url('order2/refund/create_refund')?>" method="POST" name="parcel_form">
	
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	</div>
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	    <?php echo form::input('text', 'should_amount', '', '应退金额(单位：元)(<='. $payment_info['payment_amount'] .')', '', array('datatype' => 'price', 'nullmsg' => '请填写应退金额','onblur'=>'check_num(this.value)')); ?>
	</div>
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	    <?php echo form::input('textarea', 'should_remark', '', '备注(最少五个字符)', '', array('datatype' => '*', 'nullmsg' => '请填写备注','minlength'=>5)); ?>
	</div>
	<input type="hidden" name="order_id" value="<?php echo $order_id?>">
	<div class="padding text-right ui-dialog-footer">
	<input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
		<input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
	</div>
    </form>
</div>
<?php include template('footer','admin');?>
<script>
    $(function(){
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }
        dialog.title('申请退款');
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





