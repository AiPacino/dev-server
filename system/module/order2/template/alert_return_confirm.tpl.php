<?php use zuji\order\ReturnStatus;
include template('header','admin');?>

	<form action="<?php echo url('order2/return/return_confirm')?>" method="POST" name="parcel_form" >
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
		<input type="hidden" name="return_id" value="<?php echo $return_id ?>">
		<input type="hidden" name="order_id" value="<?php echo $order_id ?>">
		确认用户已收货？
		 <?php echo form::calendar('receive_time',!empty($_GET['receive_time']) ? $_GET['receive_time']:'',array('format' => 'YYYY-MM-DD hh:mm:ss'))?>
	</div>
	<div style="height:50px;"></div>
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
		dialog.title('退回用户确认收货');
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

//  		$("input[name=return_status]").change(function(){
//            var status =$("input[name=return_status]").val();
           		if(status==<?php echo ReturnStatus::ReturnDenied?>){
//            			$("#case").show();
//                }else{
//             	   $("#case").hide();  
//                }
//         });
		// $("#okbtn").live('click',function(){
		// 	if($("textarea[name=log]").val() == ''){
		// 		alert('请填写更改原因');
		// 		return false;
		// 	}
		// 	var url = "<?php echo url('order/admin_order/complete_parcel')?>";
		// 	var status = '' ;
		// 	$("input[name=status]").each(function(){
		// 		if($(this).attr('checked') == 'checked'){
		// 			status = $(this).val();
		// 		}
		// 	})
		// 	var date = {
		// 		"id":<?php echo $_GET['id']?>,
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
		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
