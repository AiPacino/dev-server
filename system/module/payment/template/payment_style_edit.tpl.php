<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">支付配置</li>
				<li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" name="release_channel">
			<div class="form-box clearfix" id="form">
				<?php echo form::input('text', 'pay_name', $info['pay_name'], '支付名称<b style="color:red">*</b>：', '【必填】请填写支付名称。', array('datatype' => '*', 'nullmsg' => '支付名称不能为空','maxlength'=>20)); ?>
                <?php echo form::input('textarea', 'detail_pay_style', $info['detail_pay_style'], '详细支付方式<b style="color:red">*</b>：','【必填】请填写详细支付方式。', array('datatype' => '*', 'nullmsg' => '详细支付方式不能为空')); ?>
                <?php echo form::input('select','credit_id',"1",'信用名称：','',array('items' => $credits,)) ?>
                <?php //echo form::input('textarea', 'condition', $info['condition'], '使用条件<b style="color:red">*</b>：','【必填】请填写使用条件。', array('datatype' => '*', 'nullmsg' => '使用条件不能为空')); ?>
                <?php echo form::input('radio', 'status', $info['status'] ? $info['status'] : 1, '状态：', '', array('items' => array('1'=>'启用', '0'=>'禁用'), 'colspan' => 2,)); ?>
                <?php echo form::input('radio', 'isdefault', $info['isdefault'], '是否设为默认：', '', array('items' => array('1'=>'是', '0'=>'否'), 'colspan' => 2,));?>
			</div>
			<div class="padding">
				<input type="hidden" name="id" value="<?php echo $info['id']?>">
				<input type="submit" name="dosubmit" class="button bg-main" value="确定" />
				<input type="button" class="button margin-left bg-gray" value="返回" />
			</div>
			</form>
		</div>
		<script type="text/javascript">
            $(function(){
                var release_channel = $("[name=release_channel]").Validform({
                    ajaxPost:false,
                    tipSweep: true
                });
            })
		</script>
<?php include template('footer','admin');?>
