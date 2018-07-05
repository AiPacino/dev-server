<?php include template('header','admin');?>
<div id="dialog_box">
    <form action="<?php echo url('payment/instalment/createpay')?>" method="POST" name="parcel_form">

        <div class="form-box border-bottom-none order-eidt-popup clearfix">
            <div class="form-group">
                扣款
            </div>
        </div>
        <div class="form-box border-bottom-none order-eidt-popup clearfix">
            <?php echo form::input('textarea', 'remark', '', '备注(最少五个字符)', '', array('datatype' => '*', 'nullmsg' => '请填写备注','minlength'=>5)); ?>
        </div>
        <input type="hidden" id="fund_auth_record_type" value="<?php echo $fund_auth_record_type; ?>">
        <input type="hidden" id="instalment_id" name="instalment_id" value="<?php echo $instalment_id; ?>">
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

        var fund_auth_record_type = $('#fund_auth_record_type').val();
        var action = "<?php echo url('payment/instalment/createpay')?>";

        if(fund_auth_record_type == 1){
            action = "<?php echo url('payment/instalment/unfreeze_fenqi')?>";
        }else if(fund_auth_record_type == 3){
            action = "<?php echo url('payment/instalment/unfreeze_to_pay_fenqi')?>";
        }

        $("form[name='parcel_form']").attr("action",action);

        dialog.title('代扣');
        dialog.reset();     // 重置对话框位置
        var instalment_id = $('#instalment_id').val();

        // 状态是默认初始状态 请求扣款 解冻支付
        if(fund_auth_record_type == 0){

            var parcel_form = $("form[name='parcel_form']").Validform({
                ajaxPost:true,
                beforeSubmit:function( curform ){
                    $.message.start();
                },
                callback:function(ret) {
                    // 扣款返回成功
                    if(ret.status == 1) {

                        // 隔0.5秒请求扣款状态
                        var num = 0;
                        var i = setTimeout(function() {
                            num++;
                            if (num >= 3) {
                                clearTimeout(i);
                            } else {
                                var instalment_url = "<?php echo url('payment/instalment/ajax_instalment_status')?>";
                                $.post(instalment_url,{instalment_id:instalment_id}, function(data){
									console.log( data );
                                    if(data.result.status == 2){	// 扣款状态 2 已扣款成功
										_msg = '已扣款成功';
										if( data.result.unfreeze_status == 0 ){	//有未解冻租金
											_msg += '解冻租金中，请等待...';
											// 扣款成功请求 解冻
											var url = "<?php echo url('payment/instalment/unfreeze_fenqi')?>";
											$.post(url, {instalment_id:instalment_id}, function(data){
												console.log( data );
												if(data.status != 1){
													$.message.error( data.message, 3 );
													return false;
												}else{
													window.top.main_frame.location.reload();
													//$.message.end( data.message, 2 );
												}
											},'json');
										}
										$.message.end( _msg, 4 );
                                        setTimeout(function(){
                                            window.top.main_frame.location.reload();
                                        }, 2000);
                                    }
                                },'json');
                            }
                        }, 500);

                    }else{
                        $.message.error( ret.message, 3 );
                    }
                    return false;
                }
            })
        }else{
            // 正常请求方法
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
        }


        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });
    })

</script>





