<?php include template('header','admin');?>
</head>
<body>
    <div class="fixed-nav layout">
        <ul>
            <li class="first">支付规则</li>
            <li class="spacer-gray"></li>
        </ul>
        <div class="hr-gray"></div>
    </div>
    <div class="content padding-big have-fixed-nav">
        <form action="" method="POST" name="release_channel">
        <div class="form-box clearfix" id="form">
            <?php echo form::input('text', 'name', $info['rule']['name'], '规则名称<b style="color:red">*</b>：', '【必填】请填写规则名称。', array('datatype' => '*', 'nullmsg' => '规则名称不能为空','maxlength'=>20)); ?>
            <?php echo form::input('checkbox', 'channel_id[]', array_column($info['_channel'], 'id'), '所属渠道<b style="color:red">*</b>：', '【必填】请选择所属渠道。', array('items' => $channel_list,'colspan' => count($channel_list), 'css'=>'channel-list')); ?>
            <?php echo form::input('select', 'payment_style_id', isset($info['rule']['payment_style_id']) ? $info['rule']['payment_style_id'] : 0, '支付方式<b style="color:red">*</b>：', '【必填】请选择支付方式。', array('datatype' => '*', 'nullmsg' => '请选择支付方式', 'items' => $payment_list, 'css' => 'payment-style'))?>
            <div id="credit-yajin-list">
                <div class="form-group ">
                    <span class="label">使用信用<b style="color:red">*</b>：</span>
                    <div class="box">
                        <select class="input" id="credit" name="credit_id">
                            <option value="0">请选择信用</option>
                            <?php echo $credit_text?>
                        </select>
                        <span class="ico_buttonedit"></span>
                    </div>
                    <p class="desc">【必填】请选择信用。</p>
                </div>
                <div class="form-group ">
                    <span class="label">使用押金<b style="color:red">*</b>：</span>
                    <div class="box">
                        <select id="yajin" class="input" name="yajin_id">
                            <option value="0">请选择押金</option>
                            <?php echo $yajin_text?>
                        </select>
                        <span class="ico_buttonedit"></span>
                    </div>
                    <p class="desc">【必填】请选择押金。</p>
                </div>
            </div>
            <div class="form-group">
                <span class="label">具体规则<b style="color:red">*</b>：</span>
                <div class="rule-box">
                    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
                        <tbody>
                        <tr class="bg-gray-white line-height-40 border-bottom">
                            <th class="text-left padding-big-left">
                                <input class="rule-btn" type="button" value="+新增规则" onclick="setClass()" data-reset="false" />
                            </th>
                        </tr>
                        <tr class="border">
                            <td>
                                <div class="table resize-table clearfix">
                                    <div class="tr">
                                        <span class="th" data-width="15">
                                            <span class="td-con">信用分</span>
                                        </span>
                                        <span class="th" data-width="15">
                                            <span class="td-con">年龄</span>
                                        </span>
                                        <span class="th" data-width="15">
                                            <span class="td-con">押金类型</span>
                                        </span>
                                        <span class="th" data-width="20">
                                            <span class="td-con">减免押金</span>
                                        </span>
                                        <span class="th" data-width="15">
                                            <span class="td-con">减免上限</span>
                                        </span>
                                        <span class="th" data-width="20">
                                            <span class="td-con">操作</span>
                                        </span>
                                    </div>
                                    <?php foreach ($info['_rule_detail'] as $item) : ?>
                                    <div class="tr">
                                        <span class="td">
                                            <span class="td-con"><?php echo $item['credit_down'].'~'.$item['credit_up']?></span>
                                            <input type="hidden" name="detail[credit_down][]" value="<?php echo $item['credit_down'];?>">
                                            <input type="hidden" name="detail[credit_up][]" value="<?php echo $item['credit_up'];?>">
                                        </span>
                                        <span class="td" >
                                            <span class="td-con"><?php echo $item['age_down'].'~'.$item['age_up']?></span>
                                            <input type="hidden" name="detail[age_down][]" value="<?php echo $item['age_down'];?>">
                                            <input type="hidden" name="detail[age_up][]" value="<?php echo $item['age_up'];?>">
                                        </span>
                                        <span class="td">
                                            <span class="td-con"><?php echo $item['yajin_type'] == 1 ? '数额' : '百分比'?></span>
                                            <input type="hidden" name="detail[yajin_type][]" value="<?php echo $item['yajin_type'];?>">
                                        </span>
                                        <span class="td">
                                            <span class="td-con"><?php echo $item['yajin_type'] == 1 ? '减压额度：'.$item['relief_amount'].'元' : '押金百分比：'.$item['relief_amount'].'%'?></span>
                                            <input type="hidden" name="detail[relief_amount][]" value="<?php echo $item['relief_amount'];?>">
                                        </span>
                                        <span class="td">
                                            <span class="td-con"><?php echo $item['max_amount'].'元';?></span>
                                            <input type="hidden" name="detail[max_amount][]" value="<?php echo $item['max_amount'];?>">
                                        </span>
                                        <input type="hidden" name="detail[detail_id][]" value="<?php echo $item['id'];?>">
                                        <span class="td">
                                            <span class="td-con">
                                                <input class="rule-btn" type="button" value="编辑" onclick="editClass(this)">
                                                <input class="rule-btn" type="button" value="删除" onclick="delClass(this)">
                                            </span>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if(empty($info['rule']['id'])){
                echo form::input('radio', 'status', $info['rule']['status'] ? $info['rule']['status'] : 1, '状态：', '', array('items' => array('1'=>'启用', '0'=>'禁用'), 'colspan' => 2,));
            }?>

        </div>
        <div class="padding">
            <input type="hidden" name="id" value="<?php echo $info['rule']['id']?>">
            <input type="submit" name="dosubmit" class="button bg-main" value="确定" />
            <input type="button" class="button margin-left bg-gray" value="返回" />
        </div>
        </form>
    </div>
    <style type="text/css">
        .rule-btn{
            width: 70px;
            height: 25px;
            margin-top: 10px;
            line-height: 25px;
            cursor: pointer;
        }
    </style>
    <script type="text/javascript">
        $(function(){
            $(".channel-list .box").css({"min-width":"600px"});

            var release_channel = $("[name=release_channel]").Validform({
                ajaxPost:false,
                tipSweep: true
            });

            $(".payment-style input[name=payment_style_id]").change(function () {
                var url = "<?php echo url('ajax_get_credit_yajin_list')?>";
                $.post(url,{payment_style_id:this.value},function(data){
                    $("#credit-yajin-list").html(data);
                },'text');

            });
        });

        //分类选择
        function setClass() {
            var pid = $('input[name="cat_id"]').val();
            var pname = $('#choosecat').val();
            var pvalue = $('input[name=cat_format]').val();
            var data = [pid, pname, pvalue];
            top.dialog({
                url: "<?php echo url('payment/payment_rule_detail/rule_popup') ?>",
                title: '加载中...',
                data: data,
                width: 530,
                onclose: function () {
                    var res = this.returnValue;
                    var rule_text = '<div class="tr" style="visibility: visible;">';
                    var rule_value = '<input type="hidden" name="detail[detail_id][]" value="">';

                    var text_data = addData(res);
                    rule_text += text_data;
                    rule_text += rule_value;
                    rule_text += "</div>";
                    $(".rule-box table .border .table").append(rule_text);
                }

            }).showModal();
        }

        function delClass(obj) {
            $(obj).parent().parent().parent().remove();
        }
        function editClass(obj) {
            var box_obj = $(obj).parent().parent().parent();
            var credit_down = box_obj.find('[name="detail[credit_down][]"]').val();
            var credit_up = box_obj.find('[name="detail[credit_up][]"]').val();
            var age_down = box_obj.find('[name="detail[age_down][]"]').val();
            var age_up = box_obj.find('[name="detail[age_up][]"]').val();
            var yajin_type = box_obj.find('[name="detail[yajin_type][]"]').val();
            var relief_amount = box_obj.find('[name="detail[relief_amount][]"]').val();
            var max_amount = box_obj.find('[name="detail[max_amount][]"]').val();
            var detail_id = box_obj.find('[name="detail[detail_id][]"]').val();
            var data = "&credit_down="+credit_down+"&credit_up="+credit_up+"&age_down="+age_down+"&age_up="+age_up+"&yajin_type="+yajin_type+"&relief_amount="+relief_amount+"&max_amount="+max_amount;

            top.dialog({
                url: "<?php echo url('payment/payment_rule_detail/rule_popup') ?>"+data,
                title: '加载中...',
                width: 530,
                onclose: function () {
                    var res = this.returnValue;
                    var rule_text = '';
                    var rule_value = '<input type="hidden" name="detail[detail_id][]" value="'+detail_id+'">';

                    var text_data = addData(res);
                    rule_text += text_data;
                    rule_text += rule_value;
                    box_obj.html(rule_text);

                }

            }).showModal();
        }
        
        function addData(res) {
            var rule_text = "";
            var rule_value = "";
            var type = "";
            for(var i=0; i<res.length; i++){
                if(res[i].name == "credit_down"){
                    rule_text += '<span class="td" style="width: 15%"><span class="td-con">' + res[i].value + "~";
                    rule_value += '<input type="hidden" name="detail[credit_down][]" value="'+res[i].value+'">';
                }
                if(res[i].name == "credit_up"){
                    rule_text +=  res[i].value + "</span></span>";
                    rule_value += '<input type="hidden" name="detail[credit_up][]" value="'+res[i].value+'">';
                }
                if(res[i].name == "age_down"){
                    rule_text += '<span class="td" style="width: 15%"><span class="td-con">' + res[i].value + "~";
                    rule_value += '<input type="hidden" name="detail[age_down][]" value="'+res[i].value+'">';
                }
                if(res[i].name == "age_up"){
                    rule_text +=  res[i].value + "</span></span>";
                    rule_value += '<input type="hidden" name="detail[age_up][]" value="'+res[i].value+'">';
                }
                if(res[i].name == "yajin_type"){
                    type = res[i].value==1 ? "数额" : "百分比";
                    rule_text += '<span class="td" style="width: 15%"><span class="td-con">' + type + "</span></span>";
                    rule_value += '<input type="hidden" name="detail[yajin_type][]" value="'+res[i].value+'">';
                }
                if(res[i].name == "relief_amount"){
                    if(type == "百分比"){
                        rule_text += '<span class="td" style="width: 20%"><span class="td-con">押金'+ type +"：" + res[i].value + "%</span></span>";
                    }
                    else{
                        rule_text += '<span class="td" style="width: 20%"><span class="td-con">减压额度：' + res[i].value + "元</span></span>";
                    }
                    rule_value += '<input type="hidden" name="detail[relief_amount][]" value="'+res[i].value+'">';
                }
                if(res[i].name == "max_amount"){
                    rule_text += '<span class="td" style="width: 15%"><span class="td-con">' + res[i].value + "元</span></span>";
                    rule_value += '<input type="hidden" name="detail[max_amount][]" value="'+res[i].value+'">';
                }

            }
            rule_text += '<span class="td" style="width: 20%"><span class="td-con">';
            rule_text += '<input class="rule-btn" type="button" value="编辑" style="margin-right: 5px;" onclick="editClass(this)">';
            rule_text += '<input class="rule-btn" type="button" value="删除" onclick="delClass(this)">';
            rule_text += '</span></span>';
            rule_text += rule_value;

            return rule_text;
        }

        $('.table').resizableColumns();
    </script>
<?php include template('footer','admin');?>
