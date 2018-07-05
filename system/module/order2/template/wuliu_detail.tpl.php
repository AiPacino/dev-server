<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
<?php if(!$inner){?>
    <div class="fixed-nav layout">
        <ul>
            <li class="first">物流详情</li>
            <li class="spacer-gray"></li>
        </ul>
        <div class="hr-gray"></div>
    </div>
<?php }?>
<div class="content <?php if(!$inner){echo 'padding-big have-fixed-nav';}?>">
    <!--支付详情-->
    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
        <tbody>
        <tr class="bg-gray-white line-height-40 border-bottom">
            <th class="text-left padding-big-left"><?php echo $wuliu_name;?>物流详细信息</th>
        </tr>
        <?php if(!empty($wuliu_info)){?>
            <tr class="border">
                <td class="padding-big-left padding-big-right">
                    <table cellpadding="0" cellspacing="0" class="layout">
                        <tbody>
                        <tr class="line-height-40">
                            <th class="text-left">物流单号：<?php echo $wuliu_no; ?></th>

                        </tr>
                        <?php if($wuliu_info['data']) {?>
                            <?php foreach($wuliu_info['data'] as $key => $value) {?>
                                <tr class="line-height-40">
                                    <td class="text-left">物流信息：<?php echo $value['status']; ?></td>
                                    <td class="text-left">更新时间：<?php echo $value['barTm']; ?></td>
                                </tr>
                            <?php }?>
                        <?php }else{ ?>
                            <tr class="line-height-40">
                                <td class="text-left">暂无物流信息！！！</td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        <?php }else{ ?>
            <tr class="border">
                <td class="padding-big-left padding-big-right">
                    <table cellpadding="0" cellspacing="0" class="layout">
                        <tbody>
                        <tr class="line-height-40">
                            <th class="text-left">暂无物流信息！！！</th>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

</div>
<?php include template('footer', 'admin'); ?>
<script>
    $('.table').resizableColumns();
</script>
