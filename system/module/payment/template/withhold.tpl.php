<?php include template('header','admin');?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<div class="fixed-nav layout">
    <ul>
        <li class="first">支付宝代扣协议<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
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
                            <?php echo form::calendar('begin_time',!empty($_GET['begin_time']) ? $_GET['begin_time']:'',array('format' => 'YYYY-MM-DD'))?>
                        </div>
                    </div>

                    <div class="form-group form-layout-rank">
                        <span class="label">~</span>
                        <div class="box margin-none">
                            <?php echo form::calendar('end_time',!empty($_GET['end_time'])? $_GET['end_time']:'',array('format' => 'YYYY-MM-DD'))?>
                        </div>
                    </div>
                </div>
                <div class="form-box clearfix border-bottom-none" >
                    <?php echo form::input('select','business_key',$_GET['business_key'] ? $_GET['business_key'] : '0','业务类型','',array('css'=>'form-layout-rank','items' => $business_list))?>

                    <?php echo form::input('select','kw_type',$_GET['kw_type'] ? $_GET['kw_type'] : 'agreement_no','搜索','',array('css'=>'form-layout-rank','items' => $keywords_type_list))?>

                    <div class="form-group form-layout-rank">
                        <div class="box keywords margin-none">
                            <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo !empty($_GET['keywords'])?$_GET['keywords'] :''?>">
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
                <?php foreach ($data_table['th'] AS $th) {?>
                    <span class="th" data-width="<?php echo $th['length']?>">
						<span class="td-con"><?php echo $th['title']?></span>
					</span>
                <?php }?>
                <span class="th" data-width="10">
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
                            <a class="bg-main btn" href="javascript:;" onclick="order_action.dialog({'title':'协议详情','url':'<?php echo url('payment/withhold/detail',array('agreement_no' =>$item["agreement_no"])); ?>'})">详情</a>

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
    })
</script>
