<?php include template('header','admin');?>
<link rel="stylesheet" href="<?php echo __ROOT__;?>statics/css/code-table.css" media="screen" />
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

	<div class="fixed-nav layout">
	    <ul>
		<li class="first">短信信息</li>
		<li class="spacer-gray"></li>
	    </ul>
	    <div class="hr-gray"></div>
	</div>
	<div class="content padding-big have-fixed-nav">
	    <div class="hr-gray"></div>
	    <div class="clearfix">
            <div class="syntaxhighlighter  js " >
                <div class="toolbar"></div>
                <table cellspacing="0" cellpadding="0" border="0">
                    <tbody>
                    <tr>
                        <td class="gutter">
                            <?php echo $info['sms_name'];?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
		</div>
	</div>
<?php include template('footer','admin');?>