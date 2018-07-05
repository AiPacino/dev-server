<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">商品机型设置</li>
				<li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" name="release_machine_model">
			<div class="form-box clearfix" id="form">
                <?php echo form::input('text', 'name', $info['name'], '机型名称<b style="color:red">*</b>：', '【必填】机型名称不能为空，最长不能超过20个字符', array('datatype' => '*', 'nullmsg' => '机型名称不能为空','maxlength'=>20)); ?>
                <div class="form-group" style="z-index: 2;">
                    <span class="label">品牌<b style="color:red">*</b>：</span>
                    <div class="box" style="width: 256px;">
                        <div class="form-select-edit select-search-text-box">
                            <div class="form-buttonedit-popup">
                                <input class="input hd-input" type="text" value="<?php echo $info['brand_name']; ?>" readonly="readonly" nullmsg="请选择商品品牌" datatype="*" data-reset="false">
                                <span class="ico_buttonedit"></span>
                                <input class="input hd-input" type="hidden" name="brand_id" value="<?php echo $info['brand_id'] ?>" data-reset="false" nullmsg="请选择商品品牌">
                            </div>
                            <div class="select-search-field border border-main">
                                <input class="input border-none" autocomplete="off" type="text" id="brandname" value="" placeholder="请输入品牌名称" data-reset="false" />
                                <i class="ico_search"></i>
                            </div>
                            <div class="listbox-items brand-list">
                                <?php foreach ($brands AS $brand) { ?>
                                    <span class="listbox-item" data-val="<?php echo $brand['id'] ?>"><?php echo $brand['name'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <p class="desc">【必填】选择所属品牌，便于用户按照品牌进行查找</p>
                </div>
            </div>
			<div class="padding">
				<input type="hidden" name="id" value="<?php echo $info['id']?>">
				<input type="submit" name="dosubmit" class="button bg-main" value="确定" />
				<input type="button" class="button margin-left bg-gray" value="返回" />
			</div>
			</form>
		</div>
		<script type="text/javascript">
			$(function(){
                var release_machine_model = $("[name=release_machine_model]").Validform({
                    ajaxPost:false,
                    tipSweep: true
                });
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

            $('#brandname').live('keyup', function () {
                var brandname = this.value;
                $.post("<?php echo url('admin/ajax_brand') ?>", {brandname: brandname}, function (data) {
                    $('.brand-list').children('.listbox-item').remove();
                    if (data.status == 1) {
                        var html = '';
                        $.each(data.result, function (i, item) {
                            html += '<span class="listbox-item" data-val="' + i + '">' + item + '</span>';
                        })
                        $('.brand-list').append(html);
                    } else {
                        var html = '<span class="listbox-item">未搜索到结果</span>';
                        $('.brand-list').append(html);
                    }
                }, 'json')
            });
            $(".select-search-text-box .listbox-items .listbox-item").live('click', function () {
                $(this).parent().prev('.select-search-field').children('.input').val();
                $(this).parent().prev('.select-search-field').hide();
                $(this).parent().parent().find('.form-buttonedit-popup .input').val($(this).html());
                $(this).parent().parent().find('input[name="brand_id"]').val($(this).attr('data-val'));
            });
		</script>
<?php include template('footer','admin');?>
