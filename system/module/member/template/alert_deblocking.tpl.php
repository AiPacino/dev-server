<?php include template('header','admin');?>

	<form action="<?php echo url('member/member/confirm_deblocking',['member_id'=>$member_id])?>" method="POST" name="parcel_form">
            <div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 100%;min-height: 55px; margin:30px 0 20px 30px;">
            <?php echo form::input('textarea', 'admin_remark', '', '解封备注：', '', array('datatype' => '*', 'nullmsg' => '请填写解封备注')); ?>
            </div>
            <div class="padding text-right ui-dialog-footer">
                <input type="button" class="button bg-main" id="okbtn" value="提交" data-name="dosubmit" data-reset="false"/>
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
		dialog.title('添加解锁记录');
		dialog.reset();     // 重置对话框位置
               
		$("#okbtn").on('click',function(){
            $(this).attr('disabled',true);        
		    var url = "<?php echo url('member/member/confirm_deblocking',['member_id'=>$member_id])?>";
                  
                    var remark = $("[name='admin_remark']").val();
                    if(remark ==""){
                    	$("[name='admin_remark']").css('border-color','red');
                        return false;
                    }
                    var date = {
                        "member_id":<?php echo $member_id;?>,
                        "admin_remark":remark,
                    }
                    
	    			$.message.start();
                    //数据提交
		    		$.post(url,date,function( data ){
                        //提示内容
                        if( data.status == 1 ) {
    		    			$.message.end(data.msg, 2);
                            setTimeout(function(){                    
                                dialog.close('reload');
                            },3000);
                        }else{
                        	$.message.error(data.msg, 2);
                        }
                        
		    },'json');
		})
                //关闭按钮
		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
                //初始隐藏检测不合格填写原因的input框
                $('.evaluation_remark').hide();
                //检测合格隐藏检测不合格填写原因的input框
                $('#qualified-true').click(function(){
                    $('.evaluation_remark').hide();
                });
                //检测不合格显示检测不合格填写原因的input框
                $('#qualified-false').click(function(){
                    $('.evaluation_remark').show();
                });
	})
</script>
