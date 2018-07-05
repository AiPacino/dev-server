<?php include template('header','admin');?>
<div id="dialog_box">
    <form action="<?php echo url('order2/delivery/create')?>" method="POST" name="parcel_form">
	
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	    <div class="form-group">
		是否确认生成发货单？
	    </div>
	</div>
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	    <?php echo form::input('textarea', 'create_remark', '', '备注(最少五个字符)', '', array('datatype' => '*', 'nullmsg' => '请填写备注','minlength'=>5)); ?>
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
        dialog.title('确认订单');
        dialog.reset();     // 重置对话框位置

        var parcel_form = $("form[name='parcel_form']").Validform({
            ajaxPost:true,
            beforeSubmit:function( curform ){
                $.message.start();
            },
            callback:function(ret) {
                if(ret.status == 1) {
                    $.message.end( ret.message, 2 );
                    setTimeout(function(){
                        window.top.main_frame.location.reload();
                    }, 2000);
                }else{
                    $.message.error( ret.message, 3 );
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





