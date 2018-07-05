<?php include template('header','admin');?>

	<div class="fixed-nav layout">
		<ul>
			<li class="first"><?php echo $tpl_name?><a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
			<li class="spacer-gray"></li>
		</ul>
		<div class="hr-gray"></div>
	</div>
	<div class="content padding-big have-fixed-nav">
		<div class="margin-top">
			<form method="post" action="<?php echo url('order/admin_order/tpl_parcel'); ?>">
				<?php echo form::editor('content',$info['content'], '', '', array('mid' => $admin['id'], 'path' => 'common')); ?>
                <input type="hidden" name="id" value="<?php echo $info['id']?>" />
                <input type="hidden" name="name" value="<?php echo $tpl_name?>" />
				<div class="margin-top">
			        <input type="submit" class='button bg-main' name="dosubmit" value='保存'/>
				</div>
			</form>
		</div>
	</div>
	<script>
		$(function(){
			$("input[type=submit]").live('click',function(){
				um.execCommand('source');
			});
		})
	</script>
<?php include template('footer','admin');?>
