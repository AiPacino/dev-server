<?php include template('header','admin');?>
<style type="text/css">
    .message{
        padding: 10px;
    }
</style>
<div id="dialog_box">
    <form action="<?php echo url('order2/evaluation/confirm_qualified')?>" method="POST" name="parcel_form">
        <div class="form-box border-bottom-none order-eidt-popup clearfix"style="width: 100%;min-height: 55px; margin:30px 0 20px 30px;">

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
            <div class="form-box border-bottom-none order-eidt-popup clearfix">
                <?php echo form::input('radio', 'qualified', '', $msg.'？', '', array('items' => array('1'=>'合格', '0'=>'不合格'), 'colspan' => 2)); ?>
            </div>
            <div class="form-box border-bottom-none order-eidt-popup clearfix">
                <?php echo form::input('textarea', 'evaluation_remark', '', '备注(至少五个字符)', '', array('datatype' => '*', 'nullmsg' => '请输入合检测结果' ,'minlength'=>5)); ?>
            </div>
        </div>
        <input type="hidden" name="order_id" value="<?php echo $order_id?>">
        <input type="hidden" name="evaluation_id" value="<?php echo $evaluation_id?>">
        <div class="padding text-right ui-dialog-footer">
            <input type="submit" class="button bg-main" id="okbtn" value="确认结果" data-name="dosubmit" data-reset="false"/>
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
        dialog.title('检测是否合格');
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
//        $('#okbtn').on('click', function () {
//            document.getElementById("okbtn").setAttribute("disabled", true);
//            return false;
//        });

        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });
    })
</script>



