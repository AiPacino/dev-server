<?php include template('header', 'admin'); ?>
<body>
<div class="fixed-nav layout">
    <ul>
        <li class="first">内容设置</li>
        <li class="spacer-gray"></li>
        <li><a class="current" href="javascript:;">内容列表</a></li>
        <li><a href="<?php echo url('position_index') ?>">位置管理</a></li>
    </ul>
    <div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
    <form name="form" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" enctype="multipart/form-data">
        <div class="form-box clearfix">
            <?php if ($edit_flag) { ?>
                <?php echo form::input('file', 'content_pic', $images, '内容图片：', '请选择内容图片', array('preview' => $content)); ?>

                <div class="form-group" style="z-index: 2;">
                    <span class="label">商品渠道<b style="color:red">*</b>：</span>
                    <div class="box" style="width: 256px;">
                        <div class="form-select-edit select-search-text-box">
                            <div class="form-buttonedit-popup">
                                <input class="input hd-input" type="text"
                                       value="<?php echo isset($channel_info['name']) ? $channel_info['name'] : '全部'; ?>" placeholder="请输入渠道名称"
                                       readonly="readonly" nullmsg="请选择商品渠道" datatype="*" data-reset="false">
                                <span class="ico_buttonedit"></span>
                                <input class="input hd-input" type="hidden" name="channel_id"
                                       value="<?php echo isset($channel_info['id']) ? $channel_info['id'] : 0 ?>" data-reset="false"
                                       nullmsg="请选择商品渠道">
                            </div>
                            <div class="select-search-field border border-main">
                                <input class="input border-none" autocomplete="off" type="text" id="channelname-edit"
                                       value="" placeholder="请输入渠道名称" data-reset="false"/>
                                <i class="ico_search"></i>
                            </div>
                            <div class="listbox-items channel-list-edit">
                                <span class="listbox-item" data-val="0">全部</span>
                                <?php foreach ($channels AS $channel) { ?>
                                    <span class="listbox-item"
                                          data-val="<?php echo $channel['id'] ?>"><?php echo $channel['name'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <p class="desc">【必填】为商品选择所属渠道</p>
                </div>

                <?php echo form::input('select', 'tag', $tag ? $tag : 0, '内容标签：', '请选择内容标签', array('items' => $tag_list), array('datatype' => '*')); ?>
                <?php echo form::input('text', 'sort', $sort, '排序值：', '请输入展示顺序（数字大的展示越靠前）'); ?>
            <?php } else { ?>
                <?php echo form::input('text', 'title', $title, '内容名称：', '请输入内容名称', array('datatype' => '*')); ?>

                <div class="form-group" style="z-index: 2;">
                    <span class="label">内容渠道<b style="color:red">*</b>：</span>
                    <div class="box" style="width: 256px;">
                        <div class="form-select-edit select-search-text-box">
                            <div class="form-buttonedit-popup">
                                <input class="input hd-input" type="text" value="<?php echo isset($channel_info['name']) ? $channel_info['name'] : '全部'; ?>" placeholder="请输入渠道名称" readonly="readonly" nullmsg="请选择内容渠道" datatype="*" data-reset="false">
                                <span class="ico_buttonedit"></span>
                                <input class="input hd-input" type="hidden" name="channel_id" value="<?php echo isset($channel_info['id']) ? $channel_info['id'] : 0 ?>" data-reset="false" nullmsg="请选择内容渠道">
                            </div>
                            <div class="select-search-field border border-main">
                                <input class="input border-none" autocomplete="off" type="text" id="channelname-add" value="" placeholder="请输入渠道名称" data-reset="false" />
                                <i class="ico_search"></i>
                            </div>
                            <div class="listbox-items channel-list-add">
                                <span class="listbox-item" data-val="0">全部</span>
                                <?php foreach ($channels AS $channel) { ?>
                                    <span class="listbox-item" data-val="<?php echo $channel['id'] ?>"><?php echo $channel['name'] ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <p class="desc">【必填】为内容选择所属渠道</p>
                </div>

                <?php echo form::input('select', 'position_id', $position_id, '内容位：', '请选择内容位', array('items' => $position_format['items']), array('datatype' => '*')); ?>

                <?php echo form::input('select', 'tag', $tag ? $tag : 0, '内容标签：', '请选择内容标签', array('items' => $tag_list), array('datatype' => '*')); ?>

                <?php echo form::input('text', 'sort', $sort, '排序值：', '请输入展示顺序（数字大的展示越靠前）'); ?>
                <!--				--><?php //echo form::input('calendar', 'starttime', $startime_text, '开始时间', '开始时间'); ?>
                <!--				--><?php //echo form::input('calendar', 'endtime', $endtime_text, '结束时间', '结束时间'); ?>
                <!--<div class="form-group">
					<span class="label">开始时间</span>
					<div class="box">
						<input type="text" id="start" placeholder="YYYY-MM-DD hh:mm:ss" value="<?php echo $startime_text ?>" name="starttime" class="input laydate-icon hd-input" datatype = '/\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/'>
					</div>
					<p class="desc">开始时间</p>
				</div>
				<div class="form-group">
					<span class="label">结束时间</span>
					<div class="box">
						<input type="text" id="end" placeholder="YYYY-MM-DD hh:mm:ss" value="<?php echo $endtime_text ?>" name="endtime" class="input laydate-icon hd-input" datatype = '/\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/'>
					</div>
					<p class="desc">结束时间</p>
				</div>-->
                <?php echo form::input('file', 'content_pic', $images, '内容图片：', '请选择内容图片', array('preview' => $content)); ?>

                <?php echo form::input('text', 'content_text', $content, '内容文字：', '请输入内容文字'); ?>
                <?php echo form::input('text', 'link', $link, '链接地址：', '请输入链接地址(http://www.haidao.la)', array('datatype' => '*', 'ignore' => 'ignore')); ?>

            <?php } ?>
        </div>
        <div class="padding">
            <?php if (isset($id)): ?>
                <input type="hidden" name="id" value="<?php echo $id ?>"/>
            <?php endif; ?>
            <input type="hidden" name="type" value=""/>
            <input type="submit" class="button bg-main" value="保存"/>
            <input type="button" class="button margin-left bg-gray" value="返回"/>
        </div>
    </form>
</div>
<script src="/statics/js/laydate/laydate.js" type="text/javascript"></script>
<script type="text/javascript">
    $(window).otherEvent();
    var position_json = <?php echo json_encode($position_format)?>;
    //切换效果

    //			function loadBegin(position_type){
    //				$('input[name="type"]').val(position_type);
    //				if(position_type=="0"){
    //					$(".form-group:eq(4)").show();
    //					$(".form-group:eq(5)").hide();
    //				}else{
    //					$(".form-group:eq(4)").hide();
    //					$(".form-group:eq(5)").show();
    //				}
    //			}
    $(function () {
        var $val = $("input[type=text]").first().val();
        $("input[type=text]").first().focus().val($val);
        //模拟第一个点击
        if ($('input[name="position_id"]').val() == '') $('.listbox-items span:eq(0)').trigger('click');
        $("[name=form]").Validform({
            beforeSubmit: function (curform) {
                var startTime = $('input[name="starttime"]').val();
                var endTime = $('input[name="endtime"]').val();
                var d1 = new Date(startTime.replace(/\-/g, "\/"));
                var d2 = new Date(endTime.replace(/\-/g, "\/"));
                if (startTime != "" && endTime != "" && d1 >= d2) {
                    alert("开始时间不能大于结束时间！");
                    return false;
                }

            }
        });
        var position_type = position_json.type[parseInt($("input[name='position_id']").val())];
//				loadBegin(position_type);
        $('input[name="position_id"]').live('change', function () {
            position_type = position_json.type[parseInt($(this).val())];
            loadBegin(position_type);
        });
        //日期时间
        laydate.skin('danlan');
        var start = {
            elem: '#start',
            format: 'YYYY-MM-DD hh:mm:ss',
            min: laydate.now(), //设定最小日期为当前日期
            max: '2099-06-16 23:59:59', //最大日期
            istime: true,
            istoday: true,
            choose: function (datas) {
                end.min = datas; //开始日选好后，重置结束日的最小日期
                end.start = datas //将结束日的初始值设定为开始日
            }
        };
        var end = {
            elem: '#end',
            format: 'YYYY-MM-DD hh:mm:ss',
            min: laydate.now(),
            max: '2099-06-16 23:59:59',
            istime: true,
            istoday: true,
            choose: function (datas) {
                start.max = datas; //结束日选好后，重置开始日的最大日期
            }
        };
        laydate(start);
        laydate(end);
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

    $('#channelname-add').live('keyup', function () {
        var name = this.value;
        $.post("<?php echo url('ajax_channel') ?>", {name: name}, function (data) {
            $('.channel-list-add').children('.listbox-item').remove();
            if (data.status == 1) {
                var html = '';
                $.each(data.result, function (i, item) {
                    html += '<span class="listbox-item" data-val="' + i + '">' + item + '</span>';
                })
                $('.channel-list-add').append(html);
            } else {
                var html = '<span class="listbox-item">未搜索到结果</span>';
                $('.channel-list-add').append(html);
            }
        }, 'json')
    });

    $('#channelname-edit').live('keyup', function () {
        var name = this.value;
        $.post("<?php echo url('ajax_channel') ?>", {name: name}, function (data) {
            $('.channel-list-edit').children('.listbox-item').remove();
            if (data.status == 1) {
                var html = '';
                $.each(data.result, function (i, item) {
                    html += '<span class="listbox-item" data-val="' + i + '">' + item + '</span>';
                })
                $('.channel-list-edit').append(html);
            } else {
                var html = '<span class="listbox-item">未搜索到结果</span>';
                $('.channel-list-edit').append(html);
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
<?php include template('footer', 'admin'); ?>
