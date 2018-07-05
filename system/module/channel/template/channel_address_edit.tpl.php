<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">回寄地址设置</li>
				<li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>

		<div class="content padding-big have-fixed-nav">
            <form name="district" method="post" action="<?php echo empty($address_info['id']) ? url('add') : url('edit');?>">
                <div class="add-address clearfix">
                    <ul class="double-line text-left clearfix">
                        <li class="list">
                            <span class="label">渠道：</span>
                            <div class="content">
                                <select id="channel" name="channel_id">
                                    <option value="0">请选择渠道</option>
                                    <?php foreach ($channels as $item){?>
                                        <option value="<?php echo $item['id'];?>" <?php echo $item['id']==$address_info['channel_id'] ? 'selected' : '';?>><?php echo $item['name'];?></option>
                                    <?php }?>
                                </select>
                                <span id="check-channel" class="text-mix">&nbsp;</span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">类型：</span>
                            <div class="content">
                                <select id="entrance" name="type">
                                    <option value="0">请选择类型</option>
                                    <?php foreach ($type_arr as $k => $item){?>
                                        <option value="<?php echo $k;?>" <?php echo $k==$address_info['type'] ? 'selected' : '';?>><?php echo $item;?></option>
                                    <?php }?>
                                </select>
                                <span id="check-entrance" class="text-mix">&nbsp;</span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">收货地区：</span>
                            <div class="content">
                                <input type="hidden" name="district_id" value="<?php echo $address_info['cid']?>">
                                <select id="district">
                                    <option>请选择地区</option>
                                </select>
                                <span id="check-area" class="text-mix">&nbsp;</span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">详细地址：</span>
                            <div class="content">
                                <textarea class="textarea wide" name="address" type="text"><?php echo $address_info['address']?></textarea>
                                <span id="check-address" class="text-mix"></span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">收货人：</span>
                            <div class="content">
                                <input class="input" name="name" type="text" value="<?php echo $address_info['name']?>" />
                                <span id="check-name" class="text-mix"></span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">手机号：</span>
                            <div class="content">
                                <input class="input" name="mobile" type="text" value="<?php echo $address_info['mobile']?>"/>
                                <span id="check-mobile" class="text-mix"></span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">邮政编码：</span>
                            <div class="content">
                                <input class="input" name="zipcode" type="text" value="<?php echo $address_info['zipcode']?>"/>
                                <span id="check-zipcode" class="text-mix"></span>
                            </div>
                        </li>
                        <li class="list">
                            <span class="label">备注：</span>
                            <div class="content">
                                <textarea class="textarea wide" name="remark" type="text"><?php echo $address_info['remark']?></textarea>
                                <span id="check-remark" class="text-mix"></span>
                            </div>
                        </li>
                    </ul>
                </div>
                <input type="hidden" name="id" value="<?php echo $address_info['id'];?>">
                <div class="padding border-top bg-gray-white">
                    <input type="submit" id="hold" name="dosubmit" class="button bg-main" value="确定" />
                    <input type="button" class="button margin-left bg-gray" value="返回" />
                </div>
            </form>
		</div>
        <script type="text/javascript" src="<?php echo __ROOT__;?>statics/js/haidao.linkage.js?v=<?php echo HD_VERSION;?>"></script>
        <script type="text/javascript">
            var def = '<?php echo json_encode($address_info['cids']);?>';
            $("#district").linkageSel({
                url: '<?php echo url('admin/district/ajax_district');?>',
                defVal: eval(def),
                callback: function(vals,tar){
                    $("input[name=district_id]").val(vals[vals.length-1]);
                }
            });

            $(function(){
                $("input[name=name]").blur(function(){
                    checkName();
                });
                $("input[name=mobile]").blur(function(){
                    checkMobile();
                });
                $("[name=address]").blur(function(){
                    checkAddress();
                });
                $("[name=remark]").blur(function(){
                    checkRemark();
                });


                $("#hold").click(function(){
                    var flag = submithandle();
                    if(flag && $("#hold").attr('disabled') === false){
                        $([form[name="district"]]).submit();
                    }
                });


                function checkName(){
                    if(!$("input[name=name]").val()){
                        $("#check-name").text("请您填写收货人姓名！");
                        $("#hold").attr('disabled',true);
                        return false;
                    }
                    $("#check-name").text("");
                    $("#hold").attr('disabled',false);
                    return true;
                }

                function checkMobile(){
                    var str = $("input[name=mobile]").val();
                    if(!str){
                        $("#check-mobile").text("请您填写收货人手机号码！");
                        $("#hold").attr('disabled',true);
                        return false;
                    }
                    if(!str.match(/^1[3|4|5|7|8]\d{9}$/)){
                        $("#check-mobile").text("手机号码格式不正确！");
                        $("#hold").attr('disabled',true);
                        return false;
                    }
                    $("#check-mobile").text("");
                    $("#hold").attr('disabled',false);
                    return true;
                }

                function checkAddress(){
                    var str = $("[name=address]").val();
                    if(!str){
                        $("#check-address").text("请您填写收货人详细地址！");
                        $("#hold").attr('disabled',true);
                        return false;
                    }
                    if(str.length<6){
                        $("#check-address").text("收货地址至少六个字符！");
                        $("#hold").attr('disabled',true);
                        return false;
                    }
                    $("#check-address").text("");
                    $("#hold").attr('disabled',false);
                    return true;
                }
                function checkRemark(){
                    var str = $("[name=remark]").val();
                    if(!str){
                        $("#check-remark").text("请您填写备注！");
                        $("#hold").attr('disabled',true);
                        return false;
                    }
                    $("#check-remark").text("");
                    $("#hold").attr('disabled',false);
                    return true;
                }


                function submithandle(){

                    if(!checkName()){
                        $("input[name=name]").focus();
                        return false;
                    }
                    if(!checkMobile()){
                        $("input[name=mobile]").focus();
                        return false;
                    }
                    if(!checkAddress()){
                        $("input[name=address]").focus();
                        return false;
                    }
                    if(!checkRemark()){
                        $("input[name=remark]").focus();
                        return false;
                    }
                    var flog = true;
                    $('select').each(function(){
                        if($(this).val()==''){
                            flog = false;
                        }
                    });
                    if(!flog){
                        $("#check-area").text("请您选择完整的信息！")
                        return false;
                    }

                    return true;
                }

            });
        </script>
<?php include template('footer','admin');?>
