<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">信用管理</li>
				<li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" name="release_channel">
			<div class="form-box clearfix" id="form">
				<?php echo form::input('text', 'credit_name', $info['credit_name'], '信用名称<b style="color:red">*</b>：', '【必填】请填写信用名称', array('datatype' => '*', 'nullmsg' => '信用名称不能为空','maxlength'=>20)); ?>
                <?php echo form::input('text', 'min_credit_score', $info['min_credit_score'], '信用分最小值<b style="color:red">*</b>：', '', array('datatype' => '*', 'nullmsg' => '最小信用分不能为空','maxlength'=>10,'onblur'=>'return check_num(this.value)')); ?>
                <?php echo form::input('text', 'max_credit_score', $info['max_credit_score'], '信用分最大值<b style="color:red">*</b>：', '', array('datatype' => '*', 'nullmsg' => '最大信用分不能为空','maxlength'=>10,'onblur'=>'return check_num2(this.value)')); ?>
                <?php echo form::input('radio', 'is_open', $info['is_open'] ? $info['is_open'] : 1, '是否启用：', '', array('items' => array('1'=>'启用', '0'=>'禁用'), 'colspan' => 2,)); ?>
			</div>
			<div class="padding">
				<input type="hidden" name="id" value="<?php echo $info['id']?>">
				<input type="submit" name="dosubmit" class="button bg-main" value="确定" />
				<input type="button" class="button margin-left bg-gray" value="返回" />
			</div>
			</form>
		</div>
		<script type="text/javascript">
            function check_num(v){
                if(isNaN(Number(v))){  //当输入不是数字的时候，Number后返回的值是NaN;然后用isNaN判断。
                    alert('不是数字！');return false;
                }
            }
            function check_num2(v){
                if(isNaN(Number(v))){  //当输入不是数字的时候，Number后返回的值是NaN;然后用isNaN判断。
                    alert('不是数字！');return false;
                }
            }
            $(function(){
                var release_channel = $("[name=release_channel]").Validform({
                    ajaxPost:false,
                    tipSweep: true
                });
            })
		</script>
<?php include template('footer','admin');?>
