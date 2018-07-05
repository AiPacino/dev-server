<?php

use zuji\order\WeixiuStatus;

include template('header', 'admin');
?>

<form action="<?php echo url('weixiu/weixiu/update') ?>" method="POST" name="parcel_form" >
    <div class="form-box border-bottom-none order-eidt-popup clearfix">
        <input type="hidden" name="record_id" value="<?php echo $_GET['record_id'] ?>">
        <?php if($_GET['status'] == 4) {?>
        <?php echo form::input('select', 'status',$_GET['items'], '是否继续维修：', '', array('items' => [WeixiuStatus::WeixiuCanceled => "用户不维修货物原路退回", WeixiuStatus::WeixiuConfirmed => "用户要求继续维修"])) ?>
        <div style="" id="case">
            <?php echo form::input('text', 'guest_remark', '', '审核备注', '', array('nullmsg' => '请填写审核备注')); ?>
        </div>
        <?php } else if($_GET['status'] == 32){?>
            <input type="hidden" name="status" value="<?php echo $_GET['status'] ?>">
            <div style="" id="case">
                <?php echo form::input('text', 'weixiu_info', '', '维修情况备注', '', array('nullmsg' => '请填写审核备注')); ?>
            </div>
        <?php } else { ?>
        <div style="" id="case">
            <input type="hidden" name="status" value="<?php echo $_GET['status'] ?>">
            <?php echo form::input('text', 'guest_remark', '', '审核备注', '', array('nullmsg' => '请填写审核备注')); ?>
        </div>
        <?php  }?>
        <div style="height: 150px;"></div>

    </div>
    <div class="padding text-right ui-dialog-footer">
        <input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
        <input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
    </div>
</form>
<?php include template('footer', 'admin'); ?>
<script>

    $(function() {
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }
        var $val = $("textarea").first().text();
        $("textarea").first().focus().text($val);
        dialog.title('维修单申请');
        dialog.reset();     // 重置对话框位置


        var parcel_form = $("form[name='parcel_form']").Validform({
            ajaxPost: true,
            callback: function(ret) {
                dialog.title(ret.message);
                if (ret.status == 1) {
                    setTimeout(function() {
                        dialog.close(ret);
                        dialog.remove();
                    }, 1000);
                }
                return false;
            }
        })

//        $("input[name=weixiu_status]").change(function() {
//            var status = $("input[name=weixiu_status]").val();
//            if (status ==<?php //echo PaymentStatus::PaymentApplyFailed ?>//) {
//                $("#case").show();
//            } else {
//                $("#case").hide();
//            }
//        });

        $('#closebtn').on('click', function() {
            dialog.remove();
            return false;
        });
    })
</script>
