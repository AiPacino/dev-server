<?php include template('header','admin');?>

<form action="<?php echo url($url)?>" method="POST" name="parcel_form">

	<div class="form-box border-bottom-none order-eidt-popup clearfix form-block">
		<input type="hidden" name="delivery_id" value="<?php echo $delivery_id ?>">
		<input type="hidden" name="order_id" value="<?php echo $order_id ?>">
		<input type="hidden" name="goods_id" value="<?php echo $goods_id ?>">
		<?php echo form::input('select','logistics_id',"1",'物流公司：','',array('items' => $logistics,)) ?>
		<?php echo form::input('text', 'logistics_sn', $delivery_info['wuliu_no'],'物流单号：','',array('datatype' => '*','nullmsg' => '请填写物流编号')); ?>

		<?php echo form::input('textarea', 'delivery_remark', $delivery_info['delivery_remark'], '发货备注：', '', array('datatype' => '*', 'nullmsg' => '请填写备注','minlength'=>5)); ?>
		<?php if( $brand_id== \zuji\Config::Goods_Brand_Id ){ ?>
			<?php echo form::input('text', 'serial_number', $goods_info['serial_number'],'序列号：','',array('datatype' => '*','nullmsg' => '请填写序列号')); ?>
		<?php } else { ?>
			<?php echo form::input('text', 'serial_number', $goods_info['serial_number'],'序列号：',''); ?>
		<?php } ?>
		寄出商品：<?php echo $goods_name;?>
		<?php echo form::input('text', 'imei1', $goods_info['imei1'],'IMEI1：','',array('datatype' => '*' ,'nullmsg' => '请填写IMEI')); ?>
		<?php echo form::input('text', 'imei2', $goods_info['imei2'],'IMEI2：',''); ?>
		<?php echo form::input('text', 'imei3', $goods_info['imei3'],'IMEI3：',''); ?>
		<input type="hidden" name="goods_xinxi" value="<?php echo $goods_xinxi;?>"> 
	</div>
	<div class="padding text-right ui-dialog-footer">
		<input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" name ="dosubmit" data-reset="false"/>
		<input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
	</div>
	</form>
<?php include template('footer','admin');?>
<style>
	.form-block .form-group{
		display: flex;
	}
	.form-block .label{
		width:100px;
	}
	.form-block .form-group .box{
		margin-right: 10px;
		margin-bottom: 13px;
	}
</style>
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
				if(ret.status == 1) {
					$.message.end( ret.message, 2 );
					setTimeout(function(){
						window.top.main_frame.location.reload();
						dialog.close();
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
