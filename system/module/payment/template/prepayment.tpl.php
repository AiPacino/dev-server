<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<style>
    .laydate_table {
        display: none;
    }
    #laydate_hms{
        display: none !important;
    }
</style>
<div class="fixed-nav layout">
    <ul>
        <li class="first">支付分期<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
        <li class="spacer-gray"></li>
        <?php
        foreach ($tab_list as $tab){
            echo '<li>'. $tab .'</li>';
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
            <p>- </p>
        </div>
    </div>
    <div class="hr-gray"></div>
    <div class="clearfix">
        <form method="get" >
            <input type="hidden" name="m" value="<?php echo MODULE_NAME;?>" />
            <input type="hidden" name="c" value="<?php echo CONTROL_NAME;?>" />
            <input type="hidden" name="a" value="<?php echo METHOD_NAME;?>" />
            <div class="order2-list-search clearfix">
                <div class="form-box clearfix border-bottom-none" >

                    <div class="form-group form-layout-rank">
                        <span class="label">时间：</span>
                        <div class="box margin-none">
                            <?php echo form::calendar('begin_time',!empty($_GET['begin_time']) ? $_GET['begin_time']:'',array('format' => 'YYYY-MM'))?>
                        </div>
                    </div>
                </div>
                <div class="form-box clearfix border-bottom-none" >

                    <?php echo form::input('select','kw_type',$_GET['kw_type'] ? $_GET['kw_type'] : 'order_no','搜索','',array('css'=>'form-layout-rank','items' => $keywords_type_list))?>

                    <div class="form-group form-layout-rank">
                        <div class="box keywords margin-none">
                            <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo !empty($_GET['keywords'])?$_GET['keywords'] :''?>">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="prepayment_status" value="<?php echo $prepayment_status; ?>">
                <input class="button bg-sub fl" value="查询" type="submit">
            </div>
        </form>
    </div>



    <div class="table-wrap">
        <div class="table resize-table paging-table border clearfix">
            <div class="tr">


                <?php foreach ($data_table['th'] AS $th) {?>
                    <span class="th" data-width="<?php echo $th['length']?>">
						<span class="td-con"><?php echo $th['title']?></span>
					</span>
                <?php }?>
                <span class="th" data-width="14">
						<span class="td-con">操作</span>
					</span>
            </div>
            <?php foreach ($data_table['record_list'] AS $item) {?>
                <div class="tr">

                    <?php foreach ($data_table['th'] AS $k=>$th) {?>
                        <span class="td">
					       <?php echo $item[$k]; ?>
					    </span>
                    <?php }?>
                    <span class="td">
                        <div class="btn-list">
                            <a href="javascript:;" onclick="order_action.dialog({'title':'订单详情','url':'<?php echo url('payment/prepayment/refund',array('prepayment_id' =>$item["prepayment_id"])); ?>','width':400})">查看详情</a>
                            <?php if($item['alloe_refund'] == 1){ ?>|
                            <a href="javascript:;" onclick="order_action.dialog({'title':'备注','url':'<?php echo url('payment/prepayment/refund',array('prepayment_id' =>$item["prepayment_id"],'refund'=>1)); ?>','width':400})"> 退款</a>
                            <?php }?>
                        </div>
                    </span>
                </div>
            <?php }?>
            <!-- 分页 -->
            <div class="paging padding-tb body-bg clearfix">
                <ul class="fr"><?php echo $data_table['pages']; ?></ul>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
<?php include template('footer','admin');?>
<script src="<?php echo __ROOT__;?>statics/js/laydate/laydate.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/template.js" ></script>

<script>
    $(window).load(function(){
        $(".table").resizableColumns();
        $(".paging-table").fixedPaging();
        var $val=$("input[type=text]").first().val();
        $("input[type=text]").first().focus().val($val);

        //  异步扣款
        $("#Debit").live('click',function(){
            var url = "<?php echo url('ajax_createpay')?>";

            $('input[name="id"]:checked').each(function(){
                $.post(url, {id:$(this).val()}, function(data){
                    if(data.status != 1){
                        alert(data.message);
                        return false;
                    }
                },'json');
            });

            window.location.reload();
        })

    })


</script>
