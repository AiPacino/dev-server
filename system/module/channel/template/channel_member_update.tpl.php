<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">账户管理设置</li>
				<li class="spacer-gray"></li>
				<li><a class="current" href="javascript:;"></a></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" enctype="multipart/form-data" name="member_edit">
			<div class="form-box clearfix">
				<?php if(isset($data['id'])):?>
					<?php echo form::input('text', 'username', $data['username'], '用户名：', '用户名不允许修改', array('validate'=>'required;','readonly'=>'')); ?>
				<?php else:?>
					<?php echo form::input('text', 'username', '', '用户名：', '请输入用户名', array('validate'=>'required;')); ?>
				<?php endif;?>
				<?php echo form::input('password', 'password', '', '密码：', '为空则不进行修改', array('validate'=>'required;')); ?>
                <?php echo form::input('radio', 'type', isset($data['type']) ? $data['type'] : 1, '账户类型：', '请选择账户类型', array('items' => $type_list, 'colspan' => 2,)); ?>
                <div class="form-group channel-box" style="z-index: 2;">
                    <span class="label">所属渠道：</span>
                    <div class="box" style="width: 256px;">
                        <div class="form-select-edit select-search-text-box">
                            <div class="form-buttonedit-popup">
                                <input class="input hd-input" type="text" name="relation_name" value="<?php echo $data['relation_name'] ?>" readonly="readonly" datatype="*" data-reset="false">
                                <span class="ico_buttonedit"></span>
                                <input class="input hd-input" type="hidden" name="relation_id" value="<?php echo $data['relation_id'] ?>" data-reset="false">
                            </div>
                            <div class="select-search-field border border-main">
                                <input class="input border-none" autocomplete="off" type="text" id="channelname" value="" placeholder="请输入渠道名称。" data-reset="false" />
                                <i class="ico_search"></i>
                            </div>
                            <div class="listbox-items channel-list">
                                <?php foreach ($channel_list AS $channel) { ?>
                                    <span class="listbox-item" data-val="<?php echo $channel['id'] ?>"><?php echo $channel['name'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <p class="desc">选择所属渠道。</p>
                </div>

                <div class="form-group appid-box" style="z-index: 2;">
                    <span class="label">门店：</span>
                    <div class="box" style="width: 256px;">
                        <div class="form-select-edit select-search-text-box">
                            <div class="form-buttonedit-popup">
                                <input class="input hd-input" type="text" name="relation_name" value="<?php echo $data['relation_name'] ?>" readonly="readonly" datatype="*" data-reset="false">
                                <span class="ico_buttonedit"></span>
                                <input class="input hd-input" type="hidden" name="relation_id" value="<?php echo $data['relation_id'] ?>" data-reset="false">
                            </div>
                            <div class="select-search-field border border-main">
                                <input class="input border-none" autocomplete="off" type="text" id="appidname" value="" placeholder="请输入门店名称。" data-reset="false" />
                                <i class="ico_search"></i>
                            </div>
                            <div class="listbox-items appid-list">
                                <?php foreach ($appid_list AS $item) { ?>
                                    <span class="listbox-item" data-val="<?php echo $item['id'] ?>"><?php echo $item['name'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <p class="desc">选择所属门店。</p>
                </div>

			</div>
			<div class="padding">
				<?php if(isset($data['id'])):?>
					<input type="hidden" name="id" value="<?php echo $data['id']?>" />
				<?php endif;?>
				<input type="submit" class="button bg-main" value="保存" />
				<a href="<?php echo url('index')?>" class="button margin-left bg-gray" >返回</a>
			</div>
			</form>
		</div>
		<script>
            $(window).otherEvent();

            $('.select-search-field').click(function (e) {
                e.stopPropagation();
            });

            $(function(){
                var type = $("input[name='type']:checked").val();
                if(type == 1){
                    $(".channel-box").hide();
                    $(".appid-box").show();
                }else if(type == 2){
                    $(".channel-box").show();
                    $(".appid-box").hide();
                }

                var member_edit = $("[name=member_edit]").Validform({
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

            $('#appidname').live('keyup', function () {
                var name = this.value;
                $.post("<?php echo url('channel_appid/ajax_appid') ?>", {name: name}, function (data) {
                    $('.appid-list').children('.listbox-item').remove();
                    if (data.status == 1) {
                        var html = '';
                        $.each(data.result, function (i, item) {
                            html += '<span class="listbox-item" data-val="' + i + '">' + item + '</span>';
                        })
                        $('.appid-list').append(html);
                    } else {
                        var html = '<span class="listbox-item">未搜索到结果</span>';
                        $('.appid-list').append(html);
                    }
                }, 'json')
            });

            $(".select-search-text-box .listbox-items .listbox-item").live('click', function () {
                $(this).parent().prev('.select-search-field').hide();
                $('input[name="relation_name"]').val($(this).html());
                $('input[name="relation_id"]').val($(this).attr('data-val'));
            });

            $(":radio").click(function(){
                var type = $(this).val();
                var current_type = "<?php echo $data['type']?>";
                if(type == 1){
                    $(".channel-box").hide();
                    $(".appid-box").show();
                    if(current_type != "" && current_type != "1"){
                        $('.appid-box input[name="relation_name"]').val("");
                        $('.appid-box input[name="relation_id"]').val("");
                    }
                }else if(type == 2){
                    $(".channel-box").show();
                    $(".appid-box").hide();
                    if(current_type != "" && current_type != "2"){
                        $('.channel-box input[name="relation_name"]').val("");
                        $('.channel-box input[name="relation_id"]').val("");
                    }
                }
            });
		</script>
<?php include template('footer','admin');?>
