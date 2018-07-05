<?php include template('header','admin');?>
<div class="content padding-big">
    <form name="rule" id="rule-detail">
    <div class="add-address clearfix">
        <ul class="double-line text-left clearfix">
            <li class="list">
                <span class="label">信用分数：</span>
                <div class="content">
                    <input class="input" style="width: 100px" name="credit_down" type="text" value="<?php echo $params['credit_down']?>" />至
                    <input class="input" style="width: 100px;margin-left: 4px;" name="credit_up" type="text" value="<?php echo $params['credit_up']?>" />
                    <span id="check-credit" class="text-mix"></span>
                </div>
            </li>
            <li class="list">
                <span class="label">年龄：</span>
                <div class="content">
                    <input class="input" style="width: 100px" name="age_down" type="text" value="<?php echo $params['age_down']?>" />至
                    <input class="input" style="width: 100px;margin-left: 4px;" name="age_up" type="text" value="<?php echo $params['age_up']?>" />
                    <span id="check-age" class="text-mix"></span>
                </div>
            </li>
            <li class="list">
                <span class="label">押金类型：</span>
                <div class="content">
                    <input name="yajin_type" type="radio" value="1" <?php echo $params['yajin_type'] == 1 ? 'checked' : '';?>/>数额
                    <input name="yajin_type" type="radio" style="margin-left: 20px" value="2" <?php echo $params['yajin_type'] == 2 ? 'checked' : '';?>/>百分比
                    <span id="check-yajin" class="text-mix"></span>
                </div>
            </li>
            <li class="list">
                <span class="label">减压额度：</span>
                <div class="content">
                    <input class="input" name="relief_amount" type="text" value="<?php echo $params['relief_amount']?>" /><span id="relief-unit"><?php echo $params['yajin_type'] == 2 ? '%' : '元';?></span>
                    <span id="check-relief-amount" class="text-mix"></span>
                </div>
            </li>
            <li class="list">
                <span class="label">减压上限：</span>
                <div class="content">
                    <input class="input" name="max_amount" type="text" value="<?php echo $params['max_amount']?>" />元
                    <span id="check-max-amount" class="text-mix"></span>
                </div>
            </li>
        </ul>
    </div>
    <div class="padding border-top bg-gray-white text-right">
        <input class="button bg-sub" id="okbtn" type="button" value="保存" />
        <input class="margin-left button bg-gray" id="closebtn" type="button" value="取消" />
    </div>
    </form>
</div>
<script>
    $(function(){
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }
        dialog.title('添加规则');
        dialog.reset();     // 重置对话框位置
        $('#okbtn').on('click', function () {
            var ruleArr = $("#rule-detail").serializeArray();
            var credit_down = $("[name=credit_down]").val();
            var credit_up = $("[name=credit_up]").val();
            var age_down = $("[name=age_down]").val();
            var age_up = $("[name=age_up]").val();
            var yajin_type = $("input[name=yajin_type]:checked").val();
            var relief_amount = $("[name=relief_amount]").val();
            var max_amount = $("[name=max_amount]").val();
            if(credit_down=="" && credit_up==""){
                $("#check-credit").text("请您填写信用范围！");
                return false;
            }else{
                $("#check-credit").text("");
            }
            if(parseInt(credit_down) > parseInt(credit_up)){
                $("#check-credit").text("信用分范围错误");
                return false;
            }else{
                $("#check-credit").text("");
            }
            if(age_down=="" && age_up==""){
                $("#check-age").text("请您填写年龄范围！");
                return false;
            }else{
                $("#check-age").text("");
            }
            if(parseInt(age_down) > parseInt(age_up)){
                $("#check-age").text("年龄范围错误");
                return false;
            }else{
                $("#check-age").text("");
            }
            if(typeof yajin_type == "undefined"){
                $("#check-yajin").text("请选择押金类型");
                return false;
            }else{
                $("#check-yajin").text("");
            }
            if(relief_amount == ""){
                $("#check-relief-amount").text("请填写减免额度");
                return false;
            }else{
                $("#check-relief-amount").text("");
            }
            if(max_amount == ""){
                $("#check-max-amount").text("请填写减免上限");
                return false;
            }else{
                $("#check-max-amount").text("");
            }
            dialog.close(ruleArr);
        });
        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });
        $(":radio").click(function(){
            var type = $(this).val();
            if(type == 1){
                $("#relief-unit").text("元");
            }else{
                $("#relief-unit").text("%");
            }
        });
    })
</script>
<?php include template('footer','admin');?>
