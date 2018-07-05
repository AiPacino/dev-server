<?php include template('header','admin');?>

	<form action="<?php echo url($url,['order_id'=>$order_id])?>" method="POST" name="parcel_form">
        <div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 300px; margin:30px auto;">
            <?php echo $msg;?><br>
            <?php echo form::input('text', 'should_amount', $order_info['jiedong'], '解除预授权金额(单位：元)(<='. $order_info['jiedong'] .'元)', '', array('datatype' => '*', 'nullmsg' => '请填写解除授权金额','onblur'=>'check_num(this.value)')); ?>
            <?php echo form::input('textarea', 'should_remark', '', '备注（最少五个字符）', '', array('datatype' => '*', 'nullmsg' => '请填写解除授权金额备注','minlength'=>5)); ?>
        </div>
        <div class="padding text-right ui-dialog-footer">
            <?php if($order_id){?>
            <input type="hidden" name="order_id" value="<?php echo $order_id;?>">
            <input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
            <?php }?>
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
		dialog.title('确认取消');
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
