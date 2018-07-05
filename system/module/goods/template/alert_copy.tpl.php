<?php include template('header','admin');?>

	<form name="pay_form" method="post">
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	<br>
	                         请选择要复制的渠道：<br><br>
	        <?php echo $list;?><br><br>
	        <input type="hidden" value="<?php echo $spu_id;?>" name="spu_id">
	</div>
	<div class="padding text-right ui-dialog-footer">
		<input type="submit" class="button bg-main" id="okbtn" value="确定" name="dosubmit" data-reset="false"/>
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

		dialog.title('确认复制的渠道');
		var obj_validform = $("form[name='pay_form']").Validform({
			ajaxPost:true,
			dragonfly:true,
            beforeSubmit:function( curform ){
                $.message.start();
            },
            callback:function(ret) {
//                dialog.content(ret.msg);
                if(ret.status == 1) {
                    $.message.end( ret.message, 3 );
                    setTimeout(function(){
                        dialog.close();
                        window.top.main_frame.location.reload();
                    }, 1000);
                } else{
                    $.message.error( ret.message, 4 );
                }

                return false;
            }
		})

		$("#closebtn").click(function(){ dialog.close();return false;})


	})
</script>