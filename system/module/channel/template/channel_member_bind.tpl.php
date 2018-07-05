<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">绑定手机号</li>
				<li class="spacer-gray"></li>
				<li><a class="current" href="javascript:;"></a></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		<div class="content padding-big have-fixed-nav">
			<form action="" method="POST" enctype="multipart/form-data" name="member_edit">
			<div class="form-box clearfix">
                <?php echo form::input('text', 'username', $data['username'], '用户名：', '用户名不允许修改', array('validate'=>'required;','readonly'=>'')); ?>
                <?php echo form::input('textarea', 'mobile', $data['mobile'], '手机号：', '绑定手机号码，多个手机号用","分割', array('validate'=>'required;')); ?>
			</div>
			<div class="padding">
				<?php if(isset($data['id'])):?>
					<input type="hidden" name="id" value="<?php echo $data['id']?>" />
				<?php endif;?>
				<input type="submit" class="button bg-main" value="保存" />
				<a href="<?php echo url('index')?>" class="button margin-left bg-gray" >返回</a>
			</div>
			</form>
		</div>
		<script>
            $(window).otherEvent();

            $('.select-search-field').click(function (e) {
                e.stopPropagation();
            });

            $(function(){
                var member_edit = $("[name=member_edit]").Validform({
                    ajaxPost:false,
                    tipSweep: true
                });
            })
		</script>
<?php include template('footer','admin');?>
