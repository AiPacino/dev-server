<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">商品渠道设置</li>
				<li class="spacer-gray"></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" name="release_channel">
			<div class="form-box clearfix" id="form">
				<?php echo form::input('text', 'name', $info['name'], '渠道名称<b style="color:red">*</b>：', '【必填】请填写商品渠道名称。', array('datatype' => '*', 'nullmsg' => '渠道名称不能为空','maxlength'=>20)); ?>
                <?php echo form::input('text', 'contacts', $info['contacts'], '渠道负责人<b style="color:red">*</b>：','【必填】请填写渠道负责人。', array('datatype' => '*', 'nullmsg' => '渠道负责人不能为空')); ?>
                <?php echo form::input('text', 'phone', $info['phone'], '联系方式<b style="color:red">*</b>：','【必填】请填写联系方式。', array('datatype' => '*', 'nullmsg' => '联系方式不能为空')); ?>
                <?php if(empty($info['id'])) echo form::input('radio', 'alone_goods', $info['alone_goods'], '是否使用独立商品<b style="color:red">*</b>：', '此渠道若有独立的商品，则选是。此选项一旦选择无法更改。', array('items' => array('1'=>'是', '0'=>'否'), 'colspan' => 2,)); ?>
                <?php echo form::input('textarea', 'desc', $info['desc'], '渠道描述：','此项为备注，填写后对渠道分成无直接影响。'); ?>
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
