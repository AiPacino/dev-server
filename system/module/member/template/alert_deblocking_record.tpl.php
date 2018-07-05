<?php include template('header','admin');?>
<style>
        table,table tr th, table tr td { border:1px solid #a5d5f8; }
        table { width: 600px; min-height: 25px; line-height: 25px; text-align: center; border-collapse: collapse;}   
    </style>
	<form action="" method="POST" name="parcel_form">
	<div>
		
		<table border=1>
		<tr border="1px"><td width="300px;">解封备注</td><td width="120px;">操作员</td><td width="180px;">解封日期</td></tr>
         <?php for($i=0;$i<count($deblocking);$i++){?>
		<tr border="1px">
		<td><?php echo $deblocking[$i]['admin_remark']?></td>
		<td><?php $_admin = model('admin/admin_user')->find($deblocking[$i]['admin_id']);
		          echo $_admin['username'];?></td>
		<td><?php echo date("Y-m-d H:i:s",$deblocking[$i]['deblocking_time']);?></td>
		</tr>
         <?php }?>
		</table>
	</div>
	共计：<?php echo count($deblocking);?>条
	<div class="padding text-right ui-dialog-footer">
		
		<input type="button" class="button bg-main" id="closebtn" value="关闭"  data-reset="false"/>
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
		dialog.title('解封记录');
		dialog.reset();     // 重置对话框位置
		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
