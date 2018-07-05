<?php include template('header','admin');?>
<style type="text/css">
    .message{
        padding: 10px;
    }
</style>
	<form action="<?php echo url($url)?>" method="POST" name="parcel_form">

	<div class="form-box border-bottom-none order-eidt-popup clearfix form-block">
		<input type="hidden" name="delivery_id" value="<?php echo $delivery_id ?>">
		<input type="hidden" name="order_id" value="<?php echo $order_id ?>">
		<input type="hidden" name="goods_id" value="<?php echo $goods_id ?>">
		<?php echo form::input('select','logistics_id',"1",'物流公司：','',array('items' => $logistics,)) ?>
		<?php echo form::input('text', 'logistics_sn', $delivery_info['wuliu_no'],'物流单号：','',array('datatype' => '*','nullmsg' => '请填写物流编号')); ?>

		<?php echo form::input('textarea', 'delivery_remark', $delivery_info['delivery_remark'], '发货备注：', '', array('datatype' => '*', 'nullmsg' => '请填写备注','minlength'=>5)); ?>
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            序列号：<?php echo $goods_info['serial_number'];?>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            寄出商品：<?php echo $goods_name;?>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            IMEI1：<?php echo $goods_info['imei1'];?>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            IMEI2：<?php echo $goods_info['imei2'];?>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            IMEI3：<?php echo $goods_info['imei3'];?>
        </div>
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
