<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<style type="text/css">
    .message{
        padding: 10px;
    }
</style>
	<form action="<?php echo url('order2/evaluation/confirm_deal_result')?>" method="POST" name="parcel_form">
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            商品名称：<?php echo $goods_info['goods_name'];?>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix message">
            序列号：<?php echo $goods_info['serial_number'];?>
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
        <div class="form-box border-bottom-none order-eidt-popup clearfix"style="width: 100%;min-height: 55px; margin-top:10px;">
            <div>
             <?php echo $msg;?>
                <select style="border:1px solid #B8B8B8;margin-top: 5px;height:30px;width: 200px; " class="deal_result_type" name="deal_result_type">
                    <?php foreach($deal_result_list as $k => $v){ ?>
                        <option value="<?php echo $k ?>"><?php echo $v ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix">
            <?php echo form::input('textarea', 'unqualified_remark', '', '异常备注（至少五个字符）', '', array('datatype' => '*', 'nullmsg' => '请输入异常检测结果' ,'minlength'=>5)); ?>
        </div>
        <div class="padding text-right ui-dialog-footer">
            <input type="button" class="button bg-main" id="okbtn" value="确认结果" data-name="dosubmit" data-reset="false"/>
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
		dialog.title('异常处理');
		dialog.reset();     // 重置对话框位置
                //提交检测结果按钮
		 $("#okbtn").on('click',function(){
            $(this).attr('disabled',true); 
                    $(".deal_result_type option:selected").css('border-color','');
                    $(".textarea").css('border-color','');
                    //确认检测结果的url
		    var url = "<?php echo url('order2/evaluation/confirm_deal_result')?>";
		    var order_id = "<?php echo $order_id;?>";
		    var evaluation_id = "<?php echo $evaluation_id;?>";
                    var deal_result_type = $(".deal_result_type option:selected").val();
                    var unqualified_remark = $("[name='unqualified_remark']").val();
                    if( deal_result_type == '' ) {
                        $(this).attr('disabled',false);
                        $(".deal_result_type option:selected").css('border-color','red');return false;
                    }
                    if( unqualified_remark == '' || unqualified_remark.length < 5 ) {
                        $(this).attr('disabled',false);
                        $(".textarea").css('border-color','red');return false;
                    }
                    //拼接检测结果的提交参数
                    var date = {
                        "deal_result_type":deal_result_type,
                        "order_id":order_id,
                        "evaluation_id":evaluation_id,
                        "unqualified_remark":unqualified_remark,
                    }
                    $.message.start();
                    //数据提交
		    $.post(url,date,function( data ){

		    	 //提示内容
                if( data.status == 1 ) {
                    if(deal_result_type == <?php echo \zuji\order\EvaluationStatus::UnqualifiedAccepted;?> && <?php echo intval($order_info['payment_type_id'])?> != <?php echo \zuji\Config::WithhodingPay ?> && <?php echo intval($order_info['payment_type_id'])?> != <?php echo \zuji\Config::MiniAlipay ?>){
                        order_action.dialog({'title':'生成退款单','url':'<?php echo url('order2/refund/create_refund', ['order_id' =>$order_id])?>'})
                    }else{
                        $.message.end(data.message, 2);
                        setTimeout(function(){
                            window.top.main_frame.location.reload();
                        },2000);
                    }

                }else{
                    $(this).attr('disabled',false);
                	$.message.error(data.message, 3);
                }
   
                        
		    },'json');
		})
		
                //关闭按钮
		$('#closebtn').on('click', function () {
			dialog.remove();
			return false;
		});
	})
</script>
