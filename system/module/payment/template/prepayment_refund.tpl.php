<?php use zuji\order\ReturnStatus;
use zuji\Business;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<style>
    .order-edit-btn a, .order-edit-btn .a { float: left; display: inline-block; margin: 5px; padding: 6px 20px; height: 30px; line-height: 18px; font-family: "微软雅黑"; border: 0; border-radius: 3px; color: #fff; font-size: 12px; text-align: center; cursor: pointer; }
</style>
<body>

<div class="content padding-big have-fixed-nav">
    <!--还款详情-->
    <form action="<?php echo url('payment/prepayment/refund')?>" method="POST" name="parcel_form">

        <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
            <tbody>
            <tr class="border">
                <td class="padding-big-left padding-big-right">
                    <table cellpadding="0" cellspacing="0" class="layout">
                        <tbody>
                        <tr class="line-height-40">
                            <th class="text-left">交易流水号：<?php echo $info['trade_no']; ?> </th>
                        </tr>
                        <tr class="line-height-40">
                            <th class="text-left">交易支付宝号：<?php echo $info['out_trade_no']; ?> </th>
                        </tr>
                        <tr class="line-height-40">
                            <th class="text-left">支付宝账号：<?php echo $info['payment_account']; ?> </th>
                        </tr>
                        <tr class="line-height-40">
                            <th class="text-left">交款金额：<?php echo $info['payment_amount']; ?> </th>
                        </tr>
                        <tr class="line-height-40">
                            <th class="text-left">交纳期数：<?php echo $info['term']; ?> </th>
                        </tr>
                        <tr class="line-height-40">
                            <th class="text-left">交纳订单：<?php echo $info['order_no']; ?> </th>
                        </tr>
                        <tr class="line-height-40">
                            <th class="text-left">还款时间：<?php echo $info['prepayment_time']; ?> </th>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>

            </tbody>
        </table>



        <input type="hidden" name="prepayment_id" value="<?php echo $info['prepayment_id']; ?>">
    <div class="padding-tb">
        <?php if($refund){ ?>
        <input type="submit" class="button bg-main" id="okbtn" value="确认退款" data-name="dosubmit" data-reset="false"/>
        <?php }?>
        <input class="button margin-left bg-gray border-none" id="closebtn" type="button" value="返回" />
    </div>

    </form>
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
        dialog.title('还款详情');
        dialog.reset();     // 重置对话框位置


        var parcel_form = $("form[name='parcel_form']").Validform({
            ajaxPost:true,
            beforeSubmit:function( curform ){
                $.message.start();
            },
            callback:function(ret) {

                if(ret.status == 1) {
                    $.message.end( ret.message, 2 );
                    setTimeout(function(){
                        window.top.main_frame.location.reload();
                    }, 1000);
                }else{
                    $.message.error( ret.message, 4 );
                }

                return false;
            }
        })


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