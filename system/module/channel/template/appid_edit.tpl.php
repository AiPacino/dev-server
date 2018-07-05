<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">入口设置</li>
				<li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" enctype="multipart/form-data" name="release_channel_appid">
			<div class="form-box clearfix" id="form">
				<?php echo form::input('text', 'name', $info['appid']['name'], '入口名称<b style="color:red">*</b>：', '【必填】请填写入口名称。', array('datatype' => '*', 'nullmsg' => '入口名称不能为空')); ?>
                <?php echo form::input('select', 'type', $info['appid']['type'] ? $info['appid']['type'] : '0', '类型<b style="color:red">*</b>：', '【必填】请选择类型。', array('datatype' => '*', 'nullmsg' => '请选择类型', 'items' => $type_list)) ?>
                <div class="form-group" style="z-index: 2;">
                    <span class="label">渠道<b style="color:red">*</b>：</span>
                    <div class="box" style="width: 256px;">
                        <div class="form-select-edit select-search-text-box">
                            <div class="form-buttonedit-popup">
                                <input class="input hd-input" type="text" value="<?php echo $info['_channel']['name'] ?>" readonly="readonly" nullmsg="请选择商品渠道。" datatype="*" data-reset="false">
                                <span class="ico_buttonedit"></span>
                                <input class="input hd-input" type="hidden" name="channel_id" value="<?php echo $info['_channel']['id'] ?>" data-reset="false" nullmsg="请选择商品渠道。">
                            </div>
                            <div class="select-search-field border border-main">
                                <input class="input border-none" autocomplete="off" type="text" id="channelname" value="" placeholder="请输入渠道名称。" data-reset="false" />
                                <i class="ico_search"></i>
                            </div>
                            <div class="listbox-items channel-list">
                                <?php foreach ($channels AS $channel) { ?>
                                    <span class="listbox-item" data-val="<?php echo $channel['id'] ?>"><?php echo $channel['name'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <p class="desc">【必填】选择所属渠道。</p>
                </div>
                <?php echo form::input('radio', 'is_upload_idcard', $info['appid']['is_upload_idcard'] ? $info['appid']['is_upload_idcard'] : 1, '是否需要上传身份证：', '请选择是否需要上传身份证', array('items' => array('1'=>'是', '0'=>'否'), 'colspan' => 2,)); ?>
                <?php echo form::input('textarea', 'address', $info['appid']['address'], '门店地址：','请填写门店地址。'); ?>
                <?php echo form::input('text', 'mobile', $info['appid']['mobile'], '联系电话：', '请填写联系电话。'); ?>
                <?php echo form::input('textarea', 'platform_public_key', $info['appid']['platform_public_key'], '平台公钥：','请填写平台公钥。'); ?>
                <?php echo form::input('textarea', 'platform_private_key', $info['appid']['platform_private_key'], '平台私钥：','请填写平台私钥。'); ?>
                <?php echo form::input('textarea', 'client_public_key', $info['appid']['client_public_key'], '客户端公钥：','请填写客户端公钥。'); ?>
			</div>
			<div class="padding">
				<input type="hidden" name="id" value="<?php echo $info['appid']['id']?>">
				<input type="submit" name="dosubmit" class="button bg-main" value="确定" />
				<input type="button" class="button margin-left bg-gray" value="返回" />
			</div>
			</form>
		</div>
		<script type="text/javascript">
			$(window).otherEvent();

            $('.select-search-field').click(function (e) {
                e.stopPropagation();
            });

            $(function(){
                var release_channel_appid = $("[name=release_channel_appid]").Validform({
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

            $('#channelname').live('keyup', function () {
                var name = this.value;
                $.post("<?php echo url('goods/admin/ajax_channel') ?>", {name: name}, function (data) {
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
            $(".select-search-text-box .listbox-items .listbox-item").live('click', function () {
                $(this).parent().prev('.select-search-field').children('.input').val();
                $(this).parent().prev('.select-search-field').hide();
                $(this).parent().parent().find('.form-buttonedit-popup .input').val($(this).html());
                $(this).parent().parent().find('input[name="channel_id"]').val($(this).attr('data-val'));
            });
		</script>
<?php include template('footer','admin');?>
