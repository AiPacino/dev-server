<?php include template('header','admin');?>
<link rel="stylesheet" href="<?php echo __ROOT__;?>statics/css/code-table.css" media="screen" />
<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

	<div class="fixed-nav layout">
	    <ul>
		<li class="first">Debug错误查询</li>
		<li class="spacer-gray"></li>
	    </ul>
	    <div class="hr-gray"></div>
	</div>
	<div class="content padding-big have-fixed-nav">
	    <div class="tips margin-tb">
		<div class="tips-info border">
		    <h6>温馨提示</h6>
		    <a id="show-tip" data-open="true" href="javascript:;">关闭操作提示</a>
		</div>
		<div class="tips-txt padding-small-top layout">
		    <p>- 错误记录，必须可以转换成 Array 才可以正确解析和展示</p>
		</div>
	    </div>
	    <div class="hr-gray"></div>
	    <div class="clearfix">
            <div class="syntaxhighlighter  js " >
                <div class="toolbar"></div>
                <table cellspacing="0" cellpadding="0" border="0">
                    <tbody>
                    <tr>
                        <td class="gutter">
                            <?php
                            $str_arr = show_array( $data['data'] );
                            if( is_array($str_arr) ){
                                $i = 1;
                                foreach( $str_arr as $k=>$it){
                                    echo '<div class="line">'. $i++ .'</div>';
                                }
                            }
                            ?>
                        </td>
                        <td class="code">
                            <div class="container">
                                <?php
                                echo $str = implode(' ',$str_arr);
                                ?>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
		</div>
	</div>
<?php include template('footer','admin');?>

<script>

</script>
<?php

?>
