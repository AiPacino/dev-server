<script type="text/javascript" src="./statics/js/goods/goods_add.js" ></script>
<div class="form-box goods-form">
    <?php echo form::input('text', 'spu[name]', $goods['spu']['name'], '商品名称<b style="color:red">*</b>：', '【必填】商品标题名称不能为空，最长不能超过20个字符', array('datatype' => '*', 'nullmsg' => '商品名称不能为空','maxlength'=>20)); ?>
    <div class="form-group" style="z-index: 2;">
        <span class="label">商品渠道<b style="color:red">*</b>：</span>
        <div class="box" style="width: 256px;">
            <div class="form-select-edit select-search-text-box">
                <div class="form-buttonedit-popup">
                    <input class="input hd-input" type="text" value="<?php echo $goods['_channel']['name'] ? $goods['_channel']['name'] : '官方渠道' ?>" placeholder="请输入渠道名称" readonly="readonly" nullmsg="请选择商品渠道" datatype="*" data-reset="false">
                    <span class="ico_buttonedit"></span>
                    <input type="hidden" name="spu[channel_id]" value="<?php echo $goods['_channel']['id'] ? $goods['_channel']['id'] : 1 ?>">
                </div>
                <div class="select-search-field border border-main">
                    <input class="input border-none" autocomplete="off" type="text" id="channelname" value="" placeholder="请输入渠道名称" data-reset="false" />
                    <i class="ico_search"></i>
                </div>
                <div class="listbox-items channel-list">
                    <?php foreach ($channels AS $channel) { ?>
                        <span class="listbox-item" data-val="<?php echo $channel['id'] ?>"><?php echo $channel['name'] ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <p class="desc">【必填】为商品选择所属渠道</p>
    </div>
    <div class="form-group">
        <span class="label">商品分类<b style="color:red">*</b>：</span>
        <div class="box ">
            <input class="goods-class-text input hd-input input-readonly" id="choosecat" value="<?php echo $goods['_category']['parent_name'] ?>" tabindex="0"  nullmsg="请选择商品分类" datatype="*" readonly="readonly" type="text" placeholder="请选择商品分类" data-reset="false" />
            <input class="goods-class-btn" type="button" value="选择" onclick="setClass()" data-reset="false" />
            <input type="hidden" name="spu[catid]" value="<?php echo $goods['spu']['catid'] ?>">
            <input type="hidden" name="cat_format" value="<?php echo $goods['_category']['cat_format']?>">
        </div>
        <p class="desc">【必填】选择商品所属分类，一个商品只能属于一个分类</p>
    </div>

    <div class="form-group" style="z-index: 2;">
        <span class="label">商品机型<b style="color:red">*</b>：</span>
        <div class="box" style="width: 256px;">
            <div class="form-select-edit select-search-text-box">
                <div class="form-buttonedit-popup">
                    <input class="input hd-input" type="text" value="<?php echo $goods['_machine']['name'] ?>" readonly="readonly" placeholder="请输入机型名称" nullmsg="请选择商品机型" datatype="*" data-reset="false">
                    <span class="ico_buttonedit"></span>
                    <input class="input hd-input" type="hidden" name="spu[machine_id]" value="<?php echo $goods['_machine']['id'] ?>" data-reset="false" nullmsg="请选择商品机型">
                </div>
                <div class="select-search-field border border-main">
                    <input class="input border-none" autocomplete="off" type="text" id="machinename" value="" placeholder="请输入机型名称" data-reset="false" />
                    <i class="ico_search"></i>
                </div>
                <div class="listbox-items machine-list">
                    <?php foreach ($machines AS $item) { ?>
                        <span class="listbox-item" data-val="<?php echo $item['id'] ?>"><?php echo $item['name'] ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <p class="desc">【必填】为商品选择所属机型</p>
    </div>

    <?php echo form::input('text', 'spu[subtitle]', $goods['spu']['subtitle'], '广告语：', '商品广告语是用于介绍商品的描述信息', array('color' => $goods['spu']['style'] ? $goods['spu']['style'] : '', 'key' => 'spu[style]')); ?>
    <?php echo form::input('text', 'spu[warn_number]', isset($goods['spu']['warn_number']) ? $goods['spu']['warn_number'] : 5, '库存警告：', '填写商品库存警告数，当库存小于等于警告数，系统就会提醒此商品为库存警告商品，系统默认为5', array('datatype' => 'n', 'errormsg' => '库存警告只能为数字')); ?>
    <?php echo form::input('text', 'spu[yiwaixian]', isset($goods['spu']['yiwaixian']) ? $goods['spu']['yiwaixian'] : 1, '意外险<b style="color:red">*</b>：', '【必填】商品的意外保险，意外保险值必须大于零小于一千，系统默认为1',array('datatype' => '/^0{1}([.]\d{1,2})?$|^[1-9]\d*([.]{1}[0-9]{1,2})?$/', 'errormsg' => '意外保险值必须大于零小于一千')); ?>
    <?php echo form::input('text', 'spu[yiwaixian_cost]', isset($goods['spu']['yiwaixian_cost']) ? $goods['spu']['yiwaixian_cost'] : 1, '成本价<b style="color:red">*</b>：', '【必填】碎屏险成本价',array('datatype' => '/^0{1}([.]\d{1,2})?$|^[1-9]\d*([.]{1}[0-9]{1,2})?$/', 'errormsg' => '必须大于零')); ?>
    <div id="payment-list">
        <?php echo form::input('checkbox', 'rule[payment_rule_id][]', $goods['spu']['payment_rule_id'], '支付方式<b style="color:red">*</b>：', '【必填】请填写支付方式。', array('items' => $payment_list,'colspan' => count($payment_list))); ?>
    </div>
    <div class="form-group" style="z-index: 2;">
        <span class="label">合同协议模板<b style="color:red">*</b>：</span>
        <div class="box" style="width: 256px;">
            <div class="form-select-edit select-search-text-box">
                <div class="form-buttonedit-popup">
                    <input class="input hd-input" type="text" value="<?php echo $contract_list[$goods['spu']['contract_id']]['name'] ?>" readonly="readonly" placeholder="请输入合同协议模板名称" nullmsg="请选择合同协议模板" datatype="*" data-reset="false">
                    <span class="ico_buttonedit"></span>
                    <input class="input hd-input" type="hidden" name="contract_id" value="<?php echo $goods['spu']['contract_id'] ?>" data-reset="false" nullmsg="请选择合同协议模板">
                </div>
                <div class="select-search-field border border-main">
                    <input class="input border-none" autocomplete="off" type="text" id="contract_id" value="" placeholder="请输入合同协议模板名称" data-reset="false" />
                    <i class="ico_search"></i>
                </div>
                <div class="listbox-items machine-list">
                    <?php foreach ($contract_list AS $item) { ?>
                        <span class="listbox-item" data-val="<?php echo $item['id'] ?>"><?php echo $item['name'] ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <p class="desc">【必填】为商品选择合同模板</p>
    </div>
    <?php echo form::input('enabled', 'spu[status]', isset($goods['spu']['status']) ? $goods['spu']['status'] : '1', '是否上架销售：', '设置当前商品是否上架销售，默认为是，如选择否，将不在前台显示该商品', array('itemrows' => 2)); ?>
    <?php echo form::input('text', 'spu[sort]', isset($goods['spu']['sort']) ? $goods['spu']['sort'] : 100, '商品排序：', '请填写自然数，商品列表将会根据排序进行由小到大排列显示', array('datatype' => 'n', 'errormsg' => '排序只能为数字')); ?>
    <?php echo form::input('textarea', 'spu[peijian]', $goods['spu']['peijian'], '商品配件信息：', ''); ?>

    <!--    --><?php //echo form::input('text', 'spu[weight]', $goods['spu']['weight'], '重量：', '请填写每件商品重量，以（kg）为单位'); ?>
    <!--    --><?php //echo form::input('text', 'spu[volume]', $goods['spu']['volume'], '体积：', '请填写每件商品体积，以（m³）为单位'); ?>
    <!--    --><?php //echo form::input('select', 'spu[delivery_template_id]', $goods['spu']['delivery_template_id'], '运费模板：', '请选择商品将关联的运费模板（必选）', array('items'=> $delivery_template)); ?>
    <!--    --><?php //echo form::input('text', 'spu[keyword]', $goods['spu']['keyword'], '商品关键词：', 'Keywords项出现在页面头部的<Meta>标签中，用于记录本页面的关键字，多个关键字请用分隔符分隔'); ?>
    <!--    --><?php //echo form::input('textarea', 'spu[description]', $goods['spu']['description'], '商品描述：', 'Description出现在页面头部的Meta标签中，用于记录本页面的高腰与描述，建议不超过80个字'); ?>
</div>
<script type="text/javascript" src="./statics/js/goods/goods_publish.js?v=<?php echo HD_VERSION ?>" ></script>
<script type="text/javascript">
    $(window).load(function(){
        $(".cxcolor").find("table").removeClass("hidden");
    });
    $(".select-search-text-box .channel-list .listbox-item").live('click', function () {
        var url = "<?php echo url('ajax_get_payment_rule_list')?>";
        $.post(url,{channel_id:$(this).attr('data-val')},function(data){
            $("#payment-list").html(data);
        },'text');
    });

    $('.form-group:last-child').addClass('last-group');

    $('.select-search-field').click(function (e) {
        e.stopPropagation();
    });

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

    $('#channelname').live('keyup', function () {
        var name = this.value;
        $.post("<?php echo url('ajax_channel') ?>", {name: name}, function (data) {
            $('.channel-list').children('.listbox-item').remove();
            if (data.status == 1) {
                var html = '';
                $.each(data.result, function (i, item) {
                    html += '<span class="listbox-item" data-val="' + i + '">' + item + '</span>';
                })
                $('.channel-list').append(html);
            } else {
                var html = '<span class="listbox-item">未搜索到结果</span>';
                $('.channel-list').append(html);
            }
        }, 'json')
    });

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
        $(this).parent().parent().find('input[name="spu[brand_id]"]').val($(this).attr('data-val'));
        $(this).parent().parent().find('input[name="spu[channel_id]"]').val($(this).attr('data-val'));
        $(this).parent().parent().find('input[name="spu[machine_id]"]').val($(this).attr('data-val'));
        $(this).parent().parent().find('input[name="contract_id').val($(this).attr('data-val'));
    });
</script>
