<?php include template('header','admin');?>

<form action="<?php echo url($url)?>" method="POST" name="parcel_form">
    <div class="form-box border-bottom-none order-eidt-popup clearfix" style="width: 300px; margin:30px auto;">
        <?php echo form::input('radio', 'contact_status', '', '是否联系到用户：', '', array('items' => array( '0'=>'未联系用户','1'=>'联系到用户'), 'colspan' => 2)); ?>
        <br>
        <?php echo form::input('textarea', 'remark', '', '备注（最少五个字符）', '', array('datatype' => '*', 'nullmsg' => '请填写备注信息','minlength'=>5)); ?>

    </div>
    <div class="padding text-right ui-dialog-footer">

        <input type="hidden" name="instalment_id" value="<?php echo $instalment_id;?>">
        <input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" />

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
        dialog.title('备注信息');
        dialog.reset();     // 重置对话框位置


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
                    }, 1000);
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
