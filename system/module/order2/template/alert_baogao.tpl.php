<?php include template('header','admin');?>
<style>
        table,table tr th, table tr td { border:1px solid #0094ff; }
        table { width: 500px; min-height: 25px; line-height: 25px; text-align: center; border-collapse: collapse;}   
    </style>
	<form action="<?php echo url('order/admin_order/complete_parcel')?>" method="POST" name="parcel_form">
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
		<input type="hidden" name="id" value="<?php echo $id ?>">
		<table border=1><tr border="1px"><td>检测项目</td><td>检测结果</td><td>是否有异常折旧</td></tr>
		<tr border="1px"><td>AAAA</td><td>检测结果</td><td>是</td></tr>
		<tr border="1px"><td>BBBB</td><td>检测结果</td><td>否</td></tr>
		<tr border="1px"><td>CCCC</td><td>检测结果</td><td>是</td></tr>
		</table>
	</div>
	
	<div class="padding text-right ui-dialog-footer">
		
		<input type="button" class="button margin-left bg-gray" id="closebtn" value="关闭"  data-reset="false"/>
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
		dialog.title('发货');
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
