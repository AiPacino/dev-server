<?php use zuji\order\PaymentStatus;
use zuji\order\OrderStatus;

include template('header', 'admin'); ?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
    <ul>
        <li class="first">短信发送记录<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
        <li class="spacer-gray"></li>
    </ul>
    <div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
    <div class="tips margin-tb">
        <div class="tips-info border">
            <h6>温馨提示</h6>
            <a id="show-tip" data-open="true" href="javascript:;">关闭操作提示</a>
        </div>
        <div class="tips-txt padding-small-top layout">
            <p>- </p>
        </div>
    </div>
    <div class="hr-gray"></div>

    <div class="clearfix">
        <form method="get">
            <input type="hidden" name="m" value="order2"/>
            <input type="hidden" name="c" value="sms"/>
            <input type="hidden" name="a" value="index"/>
            <div class="order2-list-search clearfix">
                <div class="form-box">

                    <div class="form-group form-layout-rank">
                        <span class="label"></span>
                        <div class="box margin-none">
                            <?php echo form::calendar('begin_time', !empty($_GET['begin_time']) ? $_GET['begin_time'] : '', array('format' => 'YYYY-MM-DD hh:mm')) ?>
                        </div>
                    </div>

                    <div class="form-group form-layout-rank">
                        <span class="label">~</span>
                        <div class="box margin-none">
                            <?php echo form::calendar('end_time', !empty($_GET['end_time']) ? $_GET['end_time'] : '', array('format' => 'YYYY-MM-DD hh:mm')) ?>
                        </div>
                    </div>

                </div>
                <div class="form-box clearfix border-bottom-none">
                    <?php echo form::input('select', 'kw_type', $_GET['kw_type'] ? $_GET['kw_type'] : 'order_no', '搜索', '', array('css' => 'form-layout-rank', 'items' => $keywords_type_list)) ?>

                    <div class="form-group form-layout-rank">
                        <div class="box keywords margin-none">
                            <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text"
                                   value="<?php echo !empty($_GET['keywords']) ? $_GET['keywords'] : '' ?>">
                        </div>
                    </div>
                </div>
                <input class="button bg-sub fl" value="查询" type="submit">
            </div>
        </form>
    </div>


    <div class="table-wrap">
        <div class="table resize-table paging-table border clearfix">
            <div class="tr">
                <?php foreach ($lists['th'] AS $th) { ?>
                    <span class="th" data-width="<?php echo $th['length'] ?>">
						<span class="td-con"><?php echo $th['title'] ?></span>
					</span>
                <?php } ?>
                <span class="th" data-width="15">
						<span class="td-con">操作</span>
					</span>
            </div>
            <?php foreach ($lists['lists'] AS $item) { ?>
                <div class="tr">
                    <?php foreach ($lists['th'] AS $k => $th) { ?>
                        <span class="td">
					    <?php echo $item[$k]; ?>
					</span>
                    <?php } ?>
                    <span class="td">
					 <span class="td btn-list">
					    <a class="btn bg-sub debug-info-btn" href="javascript:;" data-id="<?php echo $item["id"];?>">查看</a>
                         <a class="btn bg-sub response-info-btn" href="javascript:;" data-id="<?php echo $item["id"];?>">回执查看</a>
					</span>
					</span>
                </div>
            <?php } ?>

            <!-- 分页 -->
            <div class="paging padding-tb body-bg clearfix">
                <ul class="fr"><?php echo $pages; ?></ul>
                <div class="clear"></div>
            </div>


        </div>
    </div>
    <!-- 	<input onclick=" " class="button bg-sub margin-top " type="daochu" style="height: 26px; line-height: 14px;" value="导出"> -->
</div>
<?php include template('footer', 'admin'); ?>
<script>
    $(window).load(function(){
        $(".table").resizableColumns();
        $(".paging-table").fixedPaging();
        var $val=$("input[type=text]").first().val();
        $("input[type=text]").first().focus().val($val);
    })
    $(function(){
        $('.debug-info-btn').click(function(){
            var id = $(this).attr('data-id');
            var debug_info_dialog = dialog({
                width: 600,
                title: 'sms info',
                url: '<?php echo url('order2/sms/detail')?>&id='+id,
                cancelValue: '取消',
                cancel: function () {}
            });
            debug_info_dialog.show();
        });
        $('.response-info-btn').click(function(){
            var id = $(this).attr('data-id');
            var debug_info_dialog = dialog({
                width: 600,
                title: 'sms info',
                url: '<?php echo url('order2/sms/response_detail')?>&id='+id,
                cancelValue: '取消',
                cancel: function () {}
            });
            debug_info_dialog.show();
        });
    });

</script>
