<?php

use zuji\order\WeixiuStatus;

include template('header', 'admin');
?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
    <ul>
        <li class="first">维修单管理<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
        <li class="spacer-gray"></li>
        <?php
        foreach ($tab_list as $tab) {
            echo '<li>' . $tab . '</li>';
        }
        ?>
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
            -
        </div>
    </div>
    <div class="hr-gray"></div>
    <div class="clearfix">
        <form method="get">
            <input type="hidden" name="m" value="weixiu" />
            <input type="hidden" name="c" value="weixiu" />
            <input type="hidden" name="a" value="index" />
            <div class="order2-list-search clearfix">
                <div class="form-box clearfix border-bottom-none" >
                    <?php echo form::input('select', 'kw_type', $_GET['kw_type'] ? $_GET['kw_type'] : 'order_no', '搜索', '', array('css' => 'form-layout-rank', 'items' => $keywords_type_list)) ?>
                    <div class="form-group form-layout-rank">
                        <div class="box keywords margin-none">
                            <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo!empty($_GET['keywords']) ? $_GET['keywords'] : '' ?>">
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
            </div>


            <?php foreach ($lists['lists'] AS $item) { ?>
                <div class="tr">
                    <?php foreach ($lists['th'] as $k => $setting) { ?>
                        <span class="td">
		    <span class="td-con"><?php echo $item[$k]; ?></span>
		</span>
                    <?php } ?>
                </div>
            <?php } ?>


            <!-- 分页 -->
            <div class="paging padding-tb body-bg clearfix">
                <ul class="fr"><?php echo $pages; ?></ul>
                <div class="clear"></div>
            </div>


        </div>
    </div>
</div>
<?php include template('footer', 'admin'); ?>

<script>
    $(".form-group .box").addClass("margin-none");
    $(window).load(function() {
        $(".table").resizableColumns();
        $(".paging-table").fixedPaging();
        var $val = $("input[type=text]").first().val();
        $("input[type=text]").first().focus().val($val);
    })
</script>
