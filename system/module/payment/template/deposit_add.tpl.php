<?php include template('header','admin');?>
<body>
<div class="fixed-nav layout">
    <ul>
        <li class="first">押金配置</li>
        <li class="spacer-gray"></li>
    </ul>
    <div class="hr-gray"></div>
</div>

<div class="content padding-big have-fixed-nav">
    <form action="" method="POST" name="release_channel">
        <div class="form-box clearfix" id="form">

            <?php echo form::input('text', 'deposit_name', $deposit['deposit_name'], '押金名称<b style="color:red">*</b>：', '【必填】请填写押金名称。', array('datatype' => '*', 'nullmsg' => '押金名称不能为空','maxlength'=>20)); ?>


            <div class="form-group" style="z-index: 2;">
                <span class="label">支付方式<b style="color:red">*</b>：</span>
                <div class="box" style="width: 256px;">
                    <div class="form-select-edit select-search-text-box">
                        <div class="form-buttonedit-popup">
                            <input class="input hd-input" type="text" value="<?php echo $deposit['pay_name'] ?>" readonly="readonly" placeholder="请输入支付方式" nullmsg="请选择支付方式" datatype="*" data-reset="false">
                            <span class="ico_buttonedit"></span>
                            <input class="input hd-input" type="hidden" name="payment_style_id" value="<?php echo $deposit['payment_style_id'] ?>" data-reset="false">
                        </div>
                        <div class="select-search-field border border-main">
                            <input class="input border-none" autocomplete="off" type="text" id="machinename" value="" placeholder="请输入支付方式名称" data-reset="false" />
                            <i class="ico_search"></i>
                        </div>
                        <div class="listbox-items machine-list">
                            <?php foreach ($payment AS $item) { ?>
                                <span class="listbox-item" data-val="<?php echo $item['id'] ?>"><?php echo $item['pay_name'] ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <p class="desc">【必填】为押金选择支付方式</p>
            </div>

            <?php echo form::input('radio', 'is_open', $deposit['is_open'] ? $deposit['is_open'] : 1, '是否启用：', '', array('items' => array('1'=>'启用', '0'=>'禁用'), 'colspan' => 2,)); ?>
        </div>
        <div class="padding">
            <input type="hidden" name="id" value="<?php echo $deposit['id']?>">
            <input type="submit" name="dosubmit" class="button bg-main" value="确定" />
            <input type="button" class="button margin-left bg-gray" value="返回" />
        </div>
    </form>
</div>

<script type="text/javascript">
    $(function(){
        var release_channel = $("[name=release_channel]").Validform({
            ajaxPost:false,
            tipSweep: true
        });
    })
    $('.select-search-text-box .form-buttonedit-popup').click(function () {
        if (!$(this).hasClass('buttonedit-popup-hover')) {
            $(this).parent().find('.select-search-field').show();
            $(this).parent().find('.select-search-field').children('.input').focus();
            $(this).parent().find('.listbox-items').show();
        } else {
            $(this).parent().find('.select-search-field').hide();
            $(this).parent().find('.listbox-items').hide();
        }
    });

    // 下拉框
    $('#machinename').live('keyup', function () {

        var name = this.value;
        $.post("<?php echo url('ajax_machine') ?>", {name: name}, function (data) {
            $('.machine-list').children('.listbox-item').remove();
            if (data.status == 1) {
                var html = '';
                $.each(data.result, function (i, item) {
                    html += '<span class="listbox-item" data-val="' + i + '">' + item + '</span>';
                })
                $('.machine-list').append(html);
            } else {
                var html = '<span class="listbox-item">未搜索到结果</span>';
                $('.machine-list').append(html);
            }
        }, 'json')
    });
    $(".select-search-text-box .listbox-items .listbox-item").live('click', function () {
        $(this).parent().prev('.select-search-field').children('.input').val();
        $(this).parent().prev('.select-search-field').hide();
        $(this).parent().parent().find('.form-buttonedit-popup .input').val($(this).html());
        $(this).parent().parent().find('input[name="payment_style_id"]').val($(this).attr('data-val'));
    });
</script>

<?php include template('footer','admin');?>
