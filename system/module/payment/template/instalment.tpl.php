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
                <input type="hidden" name="status" value="<?php echo $status;?>">
                <input type="hidden" name="ids" value="<?php echo $ids;?>">
                <input class="button bg-sub text-normal" value="查询" type="submit">
                <input id="export" class="button bg-sub text-normal" style="padding: 3px 20px;" type="button" value="导出" />
                <?php if($multi_createpay == 1){?>
                    <input id="multi_createpay" class="button bg-sub text-normal" value="此页扣款" type="button">
                <?php }?>
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
                            <a href="javascript:;" onclick="order_action.dialog({'title':'订单详情','url':'<?php echo url('order2/order/detail',array('order_id' =>$item["order_id"])); ?>'})">查看</a>

                            <a href="javascript:;" onclick="order_action.dialog({'title':'备注','url':'<?php echo url('payment/instalment/remark_record',array('id' =>$item["id"])); ?>','width':400})">| 备注</a>

                            <a href="javascript:;" onclick="order_action.dialog({'title':'联系日历','url':'<?php echo url('payment/instalment/contact_record',array('id' =>$item["id"])); ?>','width':800})">| 联系记录</a>

                            <?php if($item['allow_koukuan'] == 1){?>
                                <a class="bg-main btn" href="javascript:;" onclick="order_action.dialog({'title':'扣款','url':'<?php echo url('payment/instalment/createpay',array('id'=>$item["id"])); ?>','width':400})">扣款</a>
                            <?php }?>

                            <?php if($item['allow_koukuan'] && $item['jiedong_btn']){?>
                                <a class="bg-main btn" href="javascript:;" onclick="order_action.dialog({'title':'转支付','url':'<?php echo url('payment/instalment/unfreeze_to_pay_fenqi',array('instalment_id'=>$item["id"])); ?>','width':400})">转支付</a>
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
        });

        //导出
        $('#export').click(function(){
            //获取导出的开始时间
            var begin_time = $('input[name="begin_time"]').val();
            window.location.href = "<?php echo url('instalment_order_export')?>"+ "&begin_time=" + begin_time;
        });

        //导出
        $('#multi_createpay').click(function(){
            var ids = $('input[name="ids"]').val();
            var begin_time = $('input[name="begin_time"]').val();
            window.location.href = "<?php echo url('multi_createpay')?>" + "&ids=" + ids + "&begin_time=" + begin_time;

        });

    })


</script>
