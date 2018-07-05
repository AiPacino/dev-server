<?php
include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="padding-big have-fixed-nav">
    <div class="table-wrap">
        <div class="table resize-table paging-table border clearfix">
            <div class="tr">
                <?php foreach ($lists['th'] AS $th) {?>
                    <span class="th" data-width="<?php echo $th['length']?>%">
                                            <span class="td-con"><?php echo $th['title']?></span>
                                    </span>
                <?php }?>
            </div>
            <?php foreach ($lists['lists'] AS $item) {?>
                <div class="tr">
                    <?php foreach ($lists['th'] AS $k=>$th) {?>
                        <span class="td">
                                        <?php echo $item[$k]; ?>
                                    </span>
                    <?php }?>
                </div>
            <?php }?>
            <!-- 分页 -->
            <div class="paging padding-tb body-bg clearfix">
                <ul class="fr"><?php echo $lists['pages']; ?></ul>
                <div class="clear"></div>
            </div>
        </div>
    </div>

</div>
<?php include template('footer','admin');?>

<script>
    $(".form-group .box").addClass("margin-none");
    $(window).load(function(){
        $(".table").resizableColumns();
        $(".paging-table").fixedPaging();
        var $val=$("input[type=text]").first().val();
        $("input[type=text]").first().focus().val($val);

    })




</script>
