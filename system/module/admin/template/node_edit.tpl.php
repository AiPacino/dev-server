<?php include template('header','admin');?>
<form action="<?php echo url('edit') ?>" name="district_edit">
	<div class="form-box border-none clearfix padding-tb">
		<input type="hidden" name="id" value="<?php echo $r['id']; ?>"/>
		<?php if ($parent_pos): ?>
			<?php echo form::input("text", 'parent_id', implode($parent_pos, ' > '), '上级节点', '', array("disabled" => "disabled")) ?>
		<?php endif ?>
		<?php echo form::input('text', 'name', $r['name'], '节点名称', '', array(
			'datatype' => '*',
			'nullmsg' => '地址名称不能为空'
		)); ?>
		<?php echo form::input('text', 'mm', $r['m'], '模块'); ?>
        <?php echo form::input('text', 'cc', $r['c'], '控制器'); ?>
        <?php echo form::input('text', 'aa', $r['a'], '方法'); ?>
        <?php echo form::input('text', 'param', $r['param'], '参数'); ?>
	</div>
	<div class="padding margin-big-top text-right ui-dialog-footer">
		<input type="submit" class="button bg-main" name="dosubmit" value="确定" />
		<input type="reset" class="button margin-left bg-gray" value="取消" />
	</div>
</form>
<script type="text/javascript">
$(function() {
	var $val=$("input[type=text]").eq(1).val();
	$("input[type=text]").eq(1).focus().val($val);
	try {
		var dialog = top.dialog.get(window);
		dialog.title('节点编辑');
		dialog.reset();
	} catch (e) {
		return;
	}

	var district_edit = $("[name=district_edit]").Validform({
		ajaxPost:true,
		callback:function(ret) {
			dialog.title(ret.message);
			if(ret.status == 1) {
				setTimeout(function(){
					dialog.close(ret.message);
					dialog.remove();
				}, 1000);
			} else {
				return false;
			}
		}
	});

	$('[type=reset]').on('click', function() {
		dialog.close();
		return false;
	});
})</script>
<?php include template('footer','admin');?>