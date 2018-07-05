<?php include template('header', 'admin'); ?>
<body>
<div class="fixed-nav layout">
    <ul>
        <li class="first">电子合同管理</li>
        <li class="spacer-gray"></li>
        <li><a class="current" href="javascript:;">合同模板设置</a></li>
    </ul>
    <div class="hr-gray"></div>
</div>
<div class="content padding-big have-fixed-nav">
    <form name="form" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" enctype="multipart/form-data">
        <div class="form-box clearfix">
            <?php echo form::input('text', 'name', $rom['name'], '合同名称<b style="color:red">*</b>：', '【必填】请填写合同名称。', array('datatype' => '*', 'nullmsg' => '合同名称不能为空','maxlength'=>20)); ?>
            <!--<?php echo "模板文件地址:".$rom['file_url']." ";?><input type="file" class="file file-view" style="margin-top: 20px; margin-bottom: 40px"><br/>-->
            <?php echo form::input('file', 'content_pic', $rom['file_url'], '模板文件<b style="color:red">*</b>：', '请选择PDF格式模板文件', array('preview' =>'')); ?>
            <input class="input hd-input" type="hidden" name="path" value="<?php echo $rom['file_url']; ?>"/>
            <input class="input hd-input" type="hidden" name="id" value="<?php echo $rom['id']; ?>"/>

        </div>
        <div class="padding">
            <input type="submit" class="button bg-main" value="保存"/>
            <input type="button" class="button margin-left bg-gray" value="返回"/>
        </div>
    </form>
</div>

<?php include template('footer', 'admin'); ?>
