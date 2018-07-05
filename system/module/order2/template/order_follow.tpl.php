<?php

use zuji\order\ReturnStatus;

include template('header', 'admin');
?>
<style type="text/css">
    *{margin: 0;padding: 0}
    li{list-style: none;}
    ul{
        margin-left: 5%;
        margin-top: 10px;
    }
    ul li{
        float: left;
        margin-top:15px;
        margin-bottom: 10px;
    }
    ul li .circle{
        width: 15px;
        height: 15px;
        background-color: white;
        border: 3px #CCCCCC solid;
        -webkit-border-radius: 100px;
        display: inline-block;
    }
    ul li .line{
        height:2px;
        border:none;
        border-top:2px solid #555555;
        width:100px;
        margin: 7px 0;
        display: inline-block;
    }
    ul li:last-child .line{
        border:none;
    }
    ul li .text{
        margin-left: -40px;
        width: 150px;
        text-align: center;
    }
    .ziti{
        width:65px;
        height:50px;
        /* Rotate div */
        transform:rotate(-27deg);
        -ms-transform:rotate(-27deg); /* Internet Explorer */
        -moz-transform:rotate(-27deg); /* Firefox */
        -webkit-transform:rotate(-27deg); /* Safari 和 Chrome */
        -o-transform:rotate(-27deg); /* Opera */
    }
</style>
<body>
    <div class="content" style="overflow:auto;">

	<!-- 订单日志 -->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">订单操作流</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
                <ul>
                    <?php for($i=0;$i<count($order_follows);$i++){?>
                        <?php if($order_follows[$i]['new_status'] >0){?>
                    <li>
                        <div class="ziti">
                        <p class="text"><?php echo date('Y-m-d H:i:s',$order_follows[$i]['create_time']);?></p>
                        <p class="text"><?php echo \oms\state\State::getStatusAllName($order_follows[$i]['new_status']); ?></p>
                        </div>
                            <p class="flow">
                            <span class="circle"></span>
                            <span class="line"></span>
                        </p>

                    </li>
                            <?php }?>
                    <?php }?>
                </ul>
		    </td>
		</tr>
	    </tbody>
	</table>
    </div>
<?php include template('footer', 'admin'); ?>
    <script>
	$('.table').resizableColumns();
    </script>
