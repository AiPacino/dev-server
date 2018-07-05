<?php include template('header','admin');?>
<script type="text/javascript" src="./statics/js/goods/goods_list.js"></script>
<div class="fixed-nav layout">
    <ul>
        <li class="first">押金配置<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
        <li class="spacer-gray"></li>
    </ul>
    <div class="hr-gray"></div>
</div>

<div class="content padding-big have-fixed-nav">
    <div class="tips margin-tb">
        <div class="tips-info border">
            <h6>温馨提示</h6>
            <a id="show-tip" data-open="true" href="javascript:;">点击关闭操作提示</a>
        </div>
        <div class="tips-txt padding-small-top layout">
            <p>-</p>
        </div>
    </div>
    <div class="hr-gray"></div>
    <div class="clearfix">
    </div>
    <div class="table-work border margin-tb">
        <div class="border border-white tw-wrap">
            <a href="<?php echo url('deposit_add')?>"><i class="ico_add"></i>添加</a>
            <div class="spacer-gray"></div>
        </div>
    </div>
    <div class="table-wrap resize-table">
        <div class="table paging-table resize-table check-table border clearfix">
            <div class="tr">
                <span class="th check-option" data-resize="false">
                    <span><input id="check-all" type="checkbox" /></span>
                </span>
                <?php foreach ($lists['th'] AS $th) {?>
                    <span class="th" data-width="<?php echo $th['length']?>">
                            <span class="td-con"><?php echo $th['title']?></span>
                        </span>
                <?php }?>
                <span class="th" data-width="10">
                    <span class="td-con">操作</span>
                </span>
            </div>
            <?php foreach ($lists['lists'] AS $key => $list) {?>
                <div class="tr">
                    <span class="td check-option"><input type="checkbox" name="id" value="<?php echo $list['id']?>" /></span>

                    <?php foreach ($lists['th'] as $key => $value) {?>
                        <?php if($lists['th'][$key] ){?>

                            <?php if ($lists['th'][$key]['style'] == 'ico_open') {?>
                                <span class="td">
                                    <a class="ico_up_rack <?php if($list[$key] != 1){?>cancel<?php }?>" href="javascript:;" data-id="<?php echo $list['id']?>" data-status="<?php echo $list['is_open']?>" title="点击启用，禁用"></a>
                                </span>

                            <?php }else{?>
                                <span class="td">
                                    <span class="td-con"><?php echo $list[$key];?></span>
                                </span>
                            <?php }?>
                        <?php }?>
                    <?php }?>
                    <span class="td">
                        <span class="td-con">
                            <a href="<?php echo url('deposit_add',array('id'=>$list['id']))?>">编辑</a>
                        </span>
                    </span>
                </div>
            <?php }?>
        </div>
    </div>
</div>
<script>
    var ajax_status = "<?php echo url('set_disable')?>";
    var ajax_default = "<?php echo url('set_default')?>";
    $(window).load(function(){
        $(".table").resizableColumns();
        $(".paging-table").fixedPaging();
        //启用与关闭
        $(".table .ico_up_rack").bind('click',function(){
            var id = $(this).attr('data-id');
            var is_open = $(this).attr('data-status');
            var row = $(this);
            list_action.change_status(ajax_status,id,is_open,row);
            if(!$(this).hasClass("cancel")){
                $(this).addClass("cancel");
                $(this).attr("title","点击开启");
            }else{
                $(this).removeClass("cancel");
                $(this).attr("title","点击关闭");
            }
        });

    })
</script>
<?php include template('footer','admin');?>
