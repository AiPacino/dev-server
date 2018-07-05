<?php include template('header','admin');?>
<script type="text/javascript">
	$(function(){
		$.message.start();
		$.message.error('<?php echo $msg;?>', 4);
		setTimeout(function(){
			top.dialog.get(window).close();
		},3000)

	})
</script>
<?php include template('footer','admin');?>
