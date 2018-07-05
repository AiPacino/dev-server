<?php include template('header','admin');?>
<div id="dialog_box">
    <form action="<?php echo url('payment/fundauth/deposit_unfreeze_topay')?>" method="POST" name="parcel_form">

        <div class="form-box border-bottom-none order-eidt-popup clearfix">
            <?php echo form::input('text', 'amount', $zhifuamount, '押金解冻转支付金额 ( 转支付金额 ≤ '. $zhifuamount.' 元)', '', array('datatype' => '*', 'nullmsg' => '请填写转支付金额','maxlength'=>10,'onblur'=>'check_num(this.value)')); ?>
        </div>

        <div class="form-box border-bottom-none order-eidt-popup clearfix">
            <?php echo form::input('textarea', 'remark', '', '备注', '', array('datatype' => '*', 'nullmsg' => '请填写备注信息','minlength'=>5)); ?>
        </div>

        <input type="hidden" name="auth_id" value="<?php echo $auth_info['auth_id']; ?>">
        <div class="padding text-right ui-dialog-footer">
            <input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
            <input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
        </div>
    </form>
</div>
<?php include template('footer','admin');?>
<script>
    function check_num(v){
        var a=/^[0-9]*(\.[0-9]{1,2})?$/;
        if(!a.test(v))
        {
            alert("格式不正确");
            return false;
        }
    }

    $(function(){
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }

        dialog.title('押金解冻转支付');
        dialog.reset();     // 重置对话框位置


        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });

        var parcel_form = $("form[name='parcel_form']").Validform({
            ajaxPost:true,
            beforeSubmit:function( curform ){
                $.message.start();
            },
            callback:function(ret) {
                if(ret.status == 1) {

                    $.message.end( ret.message, 3 );
                    setTimeout(function(){
                        window.top.main_frame.location.reload();
                    }, 2000);
                }else{
                    $.message.error( ret.msg, 4 );
                }

                return false;
            }
        })
    })

</script>





