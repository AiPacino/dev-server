<?php use zuji\order\ReturnStatus;
use zuji\Business;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<script type="text/javascript">
    var order = <?php echo json_encode($order); ?>;
    $(document).ready(function() {
        order_action.init();
    });
</script>
<style>
    .order-edit-btn a, .order-edit-btn .a { float: left; display: inline-block; margin: 5px; padding: 6px 20px; height: 30px; line-height: 18px; font-family: "微软雅黑"; border: 0; border-radius: 3px; color: #fff; font-size: 12px; text-align: center; cursor: pointer; }
</style>
<body>
<div class="fixed-nav layout">
    <ul>
        <li class="first">协议详情</li>
        <li class="spacer-gray"></li>
    </ul>
    <div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
    <!--订单概况-->
    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
        <tbody>
        <tr class="bg-gray-white line-height-40 border-bottom">
            <th class="text-left padding-big-left">
                协议概况

            </th>
        </tr>

        <tr class="border">
            <td class="padding-big-left padding-big-right">
                <table cellpadding="0" cellspacing="0" class="layout">
                    <tbody>
                    <tr class="line-height-40">
                        <th class="text-left">租机用户ID：<?php echo $withhold_info['user_id']; ?> </th>
                        <th class="text-left">合作者身份ID：<?php echo $withhold_info['partner_id']; ?> </th>
                        <th class="text-left">支付宝用号：<?php echo $withhold_info['alipay_user_id']; ?></th>
                    </tr>
                    <tr class="line-height-40">
                        <th class="text-left">支付宝代扣协议号：<?php echo $withhold_info['agreement_no']; ?> </th>
                        <th class="text-left">协议状态：<?php echo $withhold_info['status']; ?> </th>
                    </tr>

                    <tr class="line-height-40">
                        <td class="text-left">签约时间：<?php echo $withhold_info['sign_time']; ?></td>
                        <td class="text-left">协议生效时间：<?php echo $withhold_info['valid_time']; ?></td>
                    </tr>
                     <tr class="line-height-40">
                        <td class="text-left">协议失效时间：<?php echo $withhold_info['invalid_time']; ?></td>

                        <td class="text-left">解约时间：

                            <?php echo strtotime($withhold_info['unsign_time']) ? $withhold_info['unsign_time'] : "--"; ?>

                        </td>

                    </tr>
                  
                    </tbody>
                </table>
            </td>
        </tr>

    
        </tbody>
    </table>

     <!-- 通知信息 -->
    <?php if(!empty($withhold_notify)):?>
    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
        <tbody>
            <tr class="bg-gray-white line-height-40 border-bottom">
                <th class="text-left padding-big-left">通知信息</th>
            </tr>
            <tr class="border">
                <td>
                    <div class="table resize-table clearfix">
                        <div class="tr">
                            <span class="th" data-width="12">
                                <span class="td-con">租机用户名</span>
                            </span>
                           
                            <span class="th" data-width="16">
                                <span class="td-con">通知时间</span>
                            </span>
                            
                            <!--
                            <span class="th" data-width="8">
                                <span class="td-con">通知类型</span>
                            </span>
                            -->
                            <!--
                            <span class="th" data-width="12">
                                <span class="td-con">签名类型</span>
                            </span>
                            -->
                            <!--
                            <span class="th" data-width="14">
                                <span class="td-con">签名</span>
                            </span>
                            -->
                            <!--
                            <span class="th" data-width="10">
                                <span class="td-con">合作者身份ID</span>
                            </span>
                            -->
                            <span class="th" data-width="14">
                                <span class="td-con">支付宝用号</span>
                            </span>
                            <span class="th" data-width="17">
                                <span class="td-con">协议号</span>
                            </span>
                            <span class="th" data-width="17">
                                <span class="td-con">签约产品码</span>
                            </span>
                           
                            <span class="th" data-width="8">
                                <span class="td-con">协议状态</span>
                            </span>

                            <span class="th" data-width="16">
                                <span class="td-con">签约时间</span>
                            </span>
                            
                            <!--
                            <span class="th" data-width="8">
                                <span class="td-con">解约时间</span>
                            </span>
                            -->
                        </div>
                               <?php foreach ($withhold_notify as $k => $item) : ?>
                        <div class="tr">
                            <span class="td" data-width="12">
                                <span class="td-con"><?php echo $item['external_user_id']; ?></span>
                            </span>
                           
                             <span class="td" data-width="16">
                                <span class="td-con"><?php echo $item['notify_time']; ?></span>
                            </span>
                            <!--
                            <span class="td" data-width="6">
                                <span class="td-con"><?php echo $item['notify_type']; ?></span>
                            </span>
                            -->
                            <!--
                             <span class="td" data-width="12">
                                <span class="td-con"><?php echo $item['sign_type']; ?></span>
                            </span>
                            -->
                            <!--
                            <span class="td" data-width="14">
                                <span class="td-con"><?php echo $item['sign']; ?></span>
                            </span>
                            -->
                            <!--
                             <span class="td" data-width="10">
                                <span class="td-con"><?php echo $item['partner_id']; ?></span>
                            </span>
                            -->
                            <span class="td" data-width="14">
                                <span class="td-con"><?php echo $item['alipay_user_id']; ?></span>
                            </span>
                             <span class="td" data-width="17">
                                <span class="td-con"><?php echo $item['agreement_no']; ?></span>
                            </span>
                            <span class="td" data-width="17">
                                <span class="td-con"><?php echo $item['product_code']; ?></span>
                            </span>
                           
                            <span class="td" data-width="8">
                                <span class="td-con"><?php echo $item['status']; ?></span>
                            </span>
                             <span class="td" data-width="16">
                                <span class="td-con"><?php echo $item['sign_time']; ?></span>
                            </span>
                            <!--
                            <span class="td" data-width="8">
                                <span class="td-con"><?php echo $item['unsign_time']; ?></span>
                            </span>
                            -->
                        </div>
                <?php endforeach; ?>

             
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php endif;?>



    <div class="padding-tb">
        <input class="button margin-left bg-gray border-none" id="closebtn" type="button" value="返回" />
    </div>
</div>
<?php include template('footer', 'admin'); ?>
<script>
    $(function(){
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }
        var $val=$("textarea").first().text();
        $("textarea").first().focus().text($val);
        dialog.title('订单详情');
        dialog.reset();     // 重置对话框位置


        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });
    })
</script>
<script>
    $(".detail_iframe").load(function(){
        var mainheight = $(this).contents().find("body").height()+20;
        $(this).height(mainheight);
    });
</script>
<script>
    $('.table').resizableColumns();

    $(".look-log").live('click', function() {
        if ($(this).hasClass('bg-gray'))
            return false;
        $(this).removeClass('bg-sub').addClass('bg-gray').html("加载中...");
        var $this = $(this);
        var txt = '';
        $.getJSON('<?php echo url("order/cart/get_delivery_log") ?>', {o_d_id: $(this).attr('data-did')}, function(ret) {
            if (ret.status == 0) {
                alert(ret.message);
                return false;
            }
            if (ret.result.logs.length > 0) {
                $.each(ret.result.logs, function(k, v) {
                    txt += '<p>' + v.add_time + '&nbsp;&nbsp;&nbsp;&nbsp;' + v.msg + '</p>';
                });
                top.dialog({
                        content: '<div class="logistics-info padding-big bg-white text-small"><p class="border-bottom border-dotted padding-small-bottom margin-small-bottom"><span class="margin-big-right">物流公司：' + ret.result.delivery_name + '</span>&nbsp;&nbsp;物流单号：' + ret.result.delivery_sn + '</p>' + txt + '</div>',
                        title: '查看物流信息',
                        width: 680,
                        okValue: '确定',
                        ok: function() {
                            $this.removeClass('bg-gray').addClass('bg-sub').html("查看物流");
                        },
                        onclose: function() {
                            $this.removeClass('bg-gray').addClass('bg-sub').html("查看物流");
                        }
                    })
                    .showModal();
            }
        });
    })
</script>
