<?php include template('header', 'admin'); ?>
<body>
<div class="fixed-nav layout">
    <ul>
        <li class="first">CDN文件上传</li>
        <li class="spacer-gray"></li>
        <li><a class="current" href="javascript:;">上传</a></li>
    </ul>
    <div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav" style="margin-left:50px; margin-top:50px">
    <form name="form" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" enctype="multipart/form-data">
        <div class="form-box clearfix" style="padding:30px">
            <input type="file" class="file"><input type="button" id="upload" class="button bg-main" value="提交"/>
        </div>
        <div class="file_list" style="margin-top:20px">
        
        </div>
    </form>
</div>
<script>
$(function(){
	function imgUpload(imgobj,inputObj){
		$(inputObj).attr("disabled","disabled");
		var alt = $(imgobj).attr('data-alt');
        var oFormData = new FormData();
        oFormData.append('file', imgobj[0].files[0]);
        $.ajax({
            url: '<?php echo url('upload')?>',
            data: oFormData,
            type: 'POST',
            contentType: false,
            processData: false,
            /*上传图片成功回调*/
            'success': function (data) {
				data = $.parseJSON(data);
				if(data['ret']==0){
					uploadFlag = true;//上传成功，图片标识修改为正确
					$(imgobj).val("");
					$(inputObj).removeAttr("disabled");
					var html = '<div style="margin:10px">'+data['path']+'</div>';
					$('.file_list').append(html);
				}
				
            }
        });
    };

    var uploadFlag = false;//上传图片标识默认为false
    $('#upload').click(function(){
        imgUpload($(".file"),$(this));
    });
})
</script>
<?php include template('footer', 'admin'); ?>
