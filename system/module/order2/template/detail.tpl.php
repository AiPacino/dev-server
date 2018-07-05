<?php use zuji\order\ReturnStatus;
use zuji\Business;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>
<script type="text/javascript">
    var order = <?php echo json_encode($order); ?>;
    $(document).ready(function() {
	order_action.init();
    });
</script>
<style>
.order-edit-btn a, .order-edit-btn .a { float: left; display: inline-block; margin: 5px; padding: 6px 20px; height: 30px; line-height: 18px; font-family: "微软雅黑"; border: 0; border-radius: 3px; color: #fff; font-size: 12px; text-align: center; cursor: pointer; }
</style>
<body>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">订单详情</li>
	    <li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
    </div>
    <div class="content padding-big have-fixed-nav">
	<!--订单概况-->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">
			订单概况
			<?php if($create_delivery){?>
			<div class="order-edit-btn fr">
			<a class="bg-main btn" href ='<?php echo url('order2/delivery/create',array('order_id' => $order['order_id'])); ?>' data-iframe="true" data-iframe-width="300">确认订单</a>
			</div>
			<?php }?>
	    </th>
	    </tr>
	    <?php if($order['service_id']){?>
	    <iframe class="detail_iframe" src="<?php echo url('order2/service/detail',['inner'=>'1','service_id'=>$order['service_id']])?>" name="service_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	    <?php }?>
	    <tr class="border">
		<td class="padding-big-left padding-big-right">
		    <table cellpadding="0" cellspacing="0" class="layout">
			<tbody>
			    <tr class="line-height-40">
				<th class="text-left">订单ID：<?php echo $order['order_id']; ?> </th>
				<th class="text-left">订单号：<?php echo $order['order_no']; ?> </th>
				<th class="text-left">订单状态：<?php echo $order['status_name']; ?></th>
			    </tr>
			    <tr class="line-height-40">
                <td class="text-left">支付方式：<?php echo $order['payment_type']; ?></td>
				<td class="text-left">下单时间：<?php echo $order['create_time_show']; ?></td>
				<?php if($order['business_key']!=Business::BUSINESS_STORE){?>
                <td class="text-left">用户协议编号：<?php echo $order['protocol_no']; ?>
				<?php
					if($contract_info['viewpdf_url']){
				?>
						&nbsp;<a href="<?php echo $contract_info['viewpdf_url'];?>" target="_blank">合同查看</a>&nbsp;
				<?php
					}
				?>
				<?php
				if($contract_info['download_url']){
					?>
					<a href="<?php echo $contract_info['download_url'];?>" target="_blank">合同下载</a>&nbsp;
					<?php
				}
				?>
				</td>
                <?php }?>
			    </tr>
			    <tr class="line-height-40">
				<td class="text-left">订单金额：￥<?php echo $order['amount']; ?></td>
				<td class="text-left">支付金额：￥<?php echo $order['payment_amount_show']; ?></td>
				<td class="text-left">退款金额：￥<?php echo $order['refund_amount_show']; ?></td>
				<td class="text-left">入口：<?php echo $order['appid']; ?></td>
			    </tr>
			    <tr class="line-height-40">
				<td class="text-left">租金：￥<?php echo $order['zujin']; ?></td>
				<td class="text-left">租期：<?php echo $order['zuqi']; ?></td>
				<td class="text-left">总租金：￥<?php echo $order['zujin_total']; ?></td>
				<td class="text-left">碎屏意外险：￥<?php echo $order['yiwaixian']; ?></td>
			    </tr>
                </tr>
                <?php if($order['payment_type_id'] == \zuji\Config::WithhodingPay){?>
                <tr class="line-height-40">
                    <td class="text-left">授权总金额：￥<?php echo $order['amount']; ?></td>
                    <td class="text-left">已支付金额：￥<?php echo $order['payment_amount']; ?></td>
                    <td class="text-left">待支付金额：￥<?php echo ($order['amount']-$order['payment_amount']); ?></td>
                </tr>
            <?php }?>
			</tbody>
		    </table>
		</td>
	    </tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<!-- 人 -->
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left" colspan="1">
					用户ID：<?php echo ($order['user_id']); ?>
				    </th>
				    <th class="text-left" colspan="1">
					手机号：<?php echo ($order['mobile']); ?>
				    </th>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">认证平台：<?php echo $order['certified_platform_name']; ?></td>
				    <td class="text-left">信用分：<?php echo $order['credit']; ?></td>
				    <td class="text-left">姓名：<?php echo $order['realname']; ?></td>
				    <td class="text-left">身份证：<?php echo $order['cert_no']; ?></td>
				</tr>
                <tr class="line-height-40">
                    <td class="text-left">蚁盾描述：<font color="<?php echo $order['yidun']['score_color']?>" ><?php echo $order['yidun']['decision_text']; ?></font></td>
                    <td class="text-left">蚁盾分数：<font color="<?php echo $order['yidun']['score_color']?>" ><?php echo $order['yidun']['score']; ?></font></td>
                    <td class="text-left">等级：<?php echo $order['yidun']['level']; ?></td>
                </tr>
				<tr class="line-height-40">
				    <td class="text-left">实际押金：￥<?php echo $order['yajin']; ?></td>
				    <td class="text-left">免押金额：￥<?php echo $order['mianyajin']; ?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
		
		<tr class="border">
		    <td class="padding-big-left padding-big-right line-height-40">
			<span class="text-main">订单备注：</span><?php echo $order['order_beizhu']; ?>
            <th class="text-right padding-big-right">
                <a id="add-beizhu" class="bg-gray-edit" href="<?php echo url('order_beizhu_edit', array("order_id" => $order['order_id'])); ?>" data-iframe="true" data-iframe-width="350">编辑</a>
            </th>
            </td>
		</tr>
	    </tbody>
	</table>

        <?php if(count($order['similar'])>0){?>
            <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
                <tbody>
                <tr class="bg-gray-white line-height-40 border-bottom">
                    <th class="text-left padding-big-left">近两天地址相似度 >70% 的订单共 <font color="red" ><?php echo count($order['similar'])?></font> 个</th>
                </tr>
                <tr class="border">
                    <td class="padding-big-left padding-big-right">
                        <table cellpadding="0" cellspacing="0" class="layout">
                            <tbody>
                            <tr class="line-height-40">
                                <td class="text-left">
                                    <?php
                                    foreach ($order['similar'] as $k=>$v){
                                       ?>
                                        <a href="javascript:;" onclick="order_action.dialog({'title':'订单详情','url':'<?php echo url('order2/order/detail',array('order_id' =>$k)); ?>'})"><?php echo $v?></a>&nbsp;&nbsp;
                                    <?php
                                    }
                                    ?>
                                </td>

                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        <?php }?>
	
<?php if($order['business_key']!=Business::BUSINESS_STORE){?>
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">收货人信息</th>
		    <th class="text-right padding-big-right">
			<?php if ($status !== 1): ?>
    			<a id="add-address" class="bg-gray-edit" href="<?php echo url('address_edit', array("address_id" => $order['address_info']['address_id'])); ?>" data-iframe="true" data-iframe-width="680">编辑</a>
			<?php endif; ?>
		    </th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <td class="text-left w25">收货人姓名：<?php echo $order['address_info']['name']; ?></td>
				    <td class="text-left w25">电话号码：<?php echo $order['address_info']['mobile']; ?></td>
				    <td class="text-left w50">详细地址：<?php echo $order['address_info']['address']; ?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
	    </tbody>
	</table>
<?php }?>
	<!-- 商品信息 -->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">商品信息</th>
		</tr>
		<tr class="border">
		    <td>
			<div class="table resize-table high-table clearfix">
			    <div class="tr">
				<span class="th" data-width="40">
				    <span class="td-con">商品信息</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">金额</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">租金</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">租期</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">成色</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">押金</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">免押金</span>
				</span>
			    </div>
			    <div class="order-detail-merge layout">
				<?php $goods_info = $order['goods_info']; ?>
				<div class="tr">
				    <div class="td">
					<div class="td-con td-pic text-left">
					    <span class="pic"><img src="<?php echo $goods_info['thumb'] ?>"></span>
					    <span class="title text-ellipsis txt margin-none padding-small-top"><a href="<?php echo url('goods/index/detail', array('sku_id' => $sku['sku_id'])) ?>" target="_blank"><?php echo $sku['sku_name'] ?></a></span>
					    <span class="icon">
						<em class="text-main"><?php echo $goods_info['sku_name'] ?></em>
					    </span>
					    <span class="icon">
						<em class="text-main"><?php echo $goods_info['spec_value_list'] ?></em>
					    </span>
					    <!-- <i class="return-ico"><img src="../images/ico_returning.png" height="60"></i> -->
					</div>
				    </div>
				    <div class="td"><span class="td-con">￥<?php echo $order['zujin_total']; ?></span></div>
				    <div class="td"><span class="td-con">￥<?php echo $order['zujin']; ?></span></div>
				    <div class="td"><span class="td-con"><?php echo $order['zuqi'] ?></span></div>
				    <div class="td"><span class="td-con"><?php echo $order['chengse'] ?></span></div>
				    <div class="td"><span class="td-con">￥<?php echo $order['yajin'] ?></span></div>
				    <div class="td"><span class="td-con">￥<?php echo $order['mianyajin'] ?></span></div>

				</div>		    
			    </div>
			</div>
		    </td>
		</tr>
	    </tbody>
	</table>



		<?php if($order['order_image'] != ""){?>
		<!-- 商品信息 -->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
			<tr class="bg-gray-white line-height-40 border-bottom">
				<th class="text-left padding-big-left">证件信息</th>
			</tr>
			<tr class="border">
				<td>
					<div class="table resize-table high-table clearfix">
						<div class="tr">
							<span class="th" data-width="25">
							    <span class="td-con">手持身份证相片</span>
							</span>
							<span class="th" data-width="25">
							    <span class="td-con">身份证正面相片</span>
							</span>
							<span class="th" data-width="25">
							    <span class="td-con">身份证背面相片</span>
							</span>
							<span class="th" data-width="25">
							    <span class="td-con">商品交易书相片</span>
							</span>
						</div>

						<div class="order-detail-merge layout">
							<?php $order_image = $order['order_image']; ?>
							<div class="tr">
								<div class="td">
									<?php if(files_exists($order_image['card_hand'])){?>
										<span class="pic"><img style="width: 50%" src="<?php echo $order_image['card_hand'] ?>"></span>
									<?php }?>
								</div>
								<div class="td">
									<?php if(files_exists($order_image['card_positive'])){?>
										<span class="pic"><img style="width: 50%" src="<?php echo $order_image['card_positive'] ?>"></span>
									<?php }?>
								</div>
								<div class="td">
									<?php if(files_exists($order_image['card_negative'])){?>
										<span class="pic"><img style="width: 50%" src="<?php echo $order_image['card_negative'] ?>"></span>
									<?php }?>
								</div>
								<div class="td">
									<?php if(files_exists($order_image['goods_delivery'])){?>
										<span class="pic"><img style="width: 50%" src="<?php echo $order_image['goods_delivery'] ?>"></span>
									<?php }?>
								</div>

							</div>
						</div>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
		<?php }?>
	<?php if($order['coupon']){?>
		<!-- 优惠券信息 -->
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
			<tr class="bg-gray-white line-height-40 border-bottom">
				<th class="text-left padding-big-left">优惠券信息</th>
			</tr>
			<tr class="border">
				<td>
					<div class="table resize-table high-table clearfix">
						<div class="tr">
				<span class="th" data-width="25">
				    <span class="td-con">优惠券码</span>
				</span>
				<span class="th" data-width="25">
				    <span class="td-con">优惠券类型</span>
				</span>
				<span class="th" data-width="25">
				    <span class="td-con">优惠券名称</span>
				</span>
				<span class="th" data-width="25">
				    <span class="td-con">优惠金额</span>
				</span>

						</div>
						<div class="order-detail-merge layout">
							<?php $coupon = $order['coupon']; ?>
							<div class="tr">

								<div class="td"><span class="td-con"><?php echo $coupon['coupon_no']; ?></span></div>
								<div class="td"><span class="td-con"><?php echo $coupon['coupon_type_show']; ?></span></div>
								<div class="td"><span class="td-con"><?php echo $coupon['coupon_name']; ?></span></div>
								<div class="td"><span class="td-con"><?php echo $order['discount_amount']; ?></span></div>

							</div>
						</div>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
	<?php }?>


	<?php if($order['payment_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/payment/detail',['inner'=>'1','payment_id'=>$order['payment_id']])?>" name="payment_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>

	<!-- 支付分期判断展示 -->
	<?php if($order['payment_type_id'] == \zuji\Config::WithhodingPay || $order['payment_type_id'] == \zuji\Config::MiniAlipay){?>
		<iframe class="detail_iframe" src="<?php echo url('order2/payment/instalment_detail',['inner'=>'1','order_id'=>$order['order_id']])?>" name="payment_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>


	<?php if($order['delivery_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/delivery/detail',['inner'=>'1','delivery_id'=>$order['delivery_id']])?>" name="delivery_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($order['delivery_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/wuliu/detail',['inner'=>'1','delivery_id'=>$order['delivery_id']])?>" name="wuliu_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($order['return_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/return/detail',['inner'=>'1','return_id'=>$order['return_id']])?>" name="return_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($order['receive_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/receive/detail',['inner'=>'1','receive_id'=>$order['receive_id']])?>" name="receive_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($order['receive_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/wuliu/detail',['inner'=>'1','receive_id'=>$order['receive_id']])?>" name="wuliu_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($order['refund_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/refund/detail',['inner'=>'1','refund_id'=>$order['refund_id']])?>" name="refund_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($order['evaluation_id']){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/evaluation/detail',['inner'=>'1','evaluation_id'=>$order['evaluation_id']])?>" name="evaluation_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	<?php if($delivery_count !=0){?>
	<iframe class="detail_iframe" src="<?php echo url('order2/evaluation/delivery_detail',['inner'=>'1','evaluation_id'=>$order['evaluation_id'],'order_id'=>$order['order_id']])?>" name="evaluation_delivery_detail" width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
	<?php }?>
	
	<iframe class="detail_iframe" src="<?php echo url('order2/order/order_log',['inner'=>'1','order_no'=>$order['order_no']])?>"width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
    <iframe class="detail_iframe" src="<?php echo url('order2/order/order_follow',['inner'=>'1','order_id'=>$order['order_id']])?>"width="100%" allowtransparency="yes" frameborder="no" scrolling="no" style="display: inline;"></iframe>
    <!-- 维修记录 -->
    <?php if(!empty($repair_record)):?>
    <table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
        <tbody>
            <tr class="bg-gray-white line-height-40 border-bottom">
                <th class="text-left padding-big-left">维修记录</th>
            </tr>
            <tr class="border">
                <td>
                    <div class="table resize-table clearfix">
                        <div class="tr">
                            <span class="th" data-width="30">
                                <span class="td-con">维修时间</span>
                            </span>
                            <span class="th" data-width="70">
                                <span class="td-con">维修原因</span>
                            </span>
                        </div>
                        <?php foreach ($repair_record as $k => $item) : ?>
                        <div class="tr">
                            <span class="td" data-width="40">
                                <span class="td-con"><?php echo date('Y-m-d H:i:s', $item['repair_time']); ?></span>
                            </span>
                            <span class="td" data-width="60">
                                <span class="td-con"><?php echo $item['reason_name']; ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php endif;?>

	<!--保险信息-->
	<!--table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">维修记录</th>
		</tr>
		<tr class="border">
		    <td>
			<div class="table resize-table high-table clearfix">
			    <div class="tr">
				<span class="th" data-width="30">
				    <span class="td-con">保险适用记录</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">用户申请时间</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">维修收到时间</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">维修邮回时间</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">用户收到时间</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">维修状态</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">维修内容</span>
				</span>
				<span class="th" data-width="10">
				    <span class="td-con">维修适用额度</span>
				</span>

			    </div>
			    <?php foreach ($order['_skus'] as $delivery_id => $skus){?>
    			    <div class="order-detail-merge layout">
				<?php foreach ($skus as $key => $sku){?>
					<div class="tr">
					    <div class="td">
						<div class="td-con td-pic text-left">
						    <span class="pic"><img src="<?php echo $sku['sku_thumb'] ?>"></span>
						    <span class="title text-ellipsis txt margin-none padding-small-top"><a href="<?php echo url('goods/index/detail', array('sku_id' => $sku['sku_id'])) ?>" target="_blank"><?php echo $sku['sku_name'] ?></a></span>
						    <span class="icon">
							<?php foreach ($sku['sku_spec'] as $spec){ ?>
	    						<em class="text-main"><?php echo $spec['name'] ?>：</em><?php echo $spec['value'] ?>&nbsp;
							<?php }?>
							<!- <em class="text-main">处理时间：2015-05-03 10:12:12</em> ->
							<!- <a href="javascript:;">查看详情</a> ->
							<br/>
							<?php if ($sku['is_give'] == 1){ ?>
	    						<span class="bg-blue text-white padding-small-left padding-small-right fl margin-small-top text-lh-little">赠品</span>
							<?php } ?>
							<?php if ($sku['promotion']) { ?>
	    						<p class="text-gray text-ellipsis"><span class="bg-red text-white padding-small-left padding-small-right fl margin-small-top text-lh-little margin-small-right"><?php echo ch_prom($sku['promotion']['type']); ?></span><?php echo $sku['promotion']['title']; ?></p>
							<?php } ?>
						    </span>
					    <!- <i class="return-ico"><img src="../images/ico_returning.png" height="60"></i> ->
						</div>
					    </div>
					    <div class="td"><span class="td-con">￥<?php echo $sku['sku_price']; ?></span></div>
					    <div class="td"><span class="td-con">￥<?php echo $sku['real_price']; ?></span></div>
					    <div class="td"><span class="td-con"><?php echo $sku['buy_nums'] ?></span></div>
					    <div class="td"><span class="td-con"><?php echo $sku['delivery_template_name'] ? $sku['delivery_template_name'] : '-' ?></span></div>

					    <div class="td detail-logistics" <?php if ($sku['_is_delivery'] == 'true'): ?>style="padding: 15px 15px 0px 0px;"<?php endif ?>>
						<?php if ($delivery_id > 0){ ?>
	    					<div class="order-edit-btn fr">
						    <?php if ($sku['delivery_status'] == 1){ ?>
							<button class="bg-main look-log" data-did="<?php echo $delivery_id; ?>">查看物流</button>
							<button class="bg-main" onclick="order_action.delivery('<?php echo url("order/admin_order/delivery_edit", array("sub_sn" => $sku["sub_sn"], "delivery_id" => $delivery_id)); ?>');">修改物流信息</button>
						    <?php }else{ ?>
							<a class="button bg-sub text-ellipsis look-log" href="javascript:;" data-did="<?php echo $delivery_id; ?>">查看物流</a>
						    <?php } ?>
	    					</div>
						<?php }else{ ?>
	    					<a class="button bg-gray text-ellipsis" href="javascript:;">暂未发货</a>
					    <?php } ?>
					    </div>
					</div>
				<?php }?>
    			    </div>
			    <?php } ?>
			</div>
		    </td>
		</tr>
	    </tbody>
	</table -->


	<!-- 物流信息 -->
	<!--<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">物流信息</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
    				<tr class="line-height-40">
    				    <td class="text-left"> </td>
    				    <td class="text-left"> </td>
    				    <td class="text-left"> </td>
    				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
	    </tbody>
	</table>-->


	<div class="padding-tb">
	    <input class="button margin-left bg-gray border-none" id="closebtn" type="button" value="返回" />
	</div>
    </div>
<?php include template('footer', 'admin'); ?>
    <script>
        $(function(){
            try {
                var dialog = top.dialog.get(window);
            } catch (e) {
                return;
            }
            var $val=$("textarea").first().text();
            $("textarea").first().focus().text($val);
            dialog.title('订单详情');
            dialog.reset();     // 重置对话框位置


            $('#closebtn').on('click', function () {
                dialog.remove();
                return false;
            });
        })
    </script>
    <script>
	$(".detail_iframe").load(function(){
	    var mainheight = $(this).contents().find("body").height()+20;
	    $(this).height(mainheight);
	});
    </script>
    <script>
	$('.table').resizableColumns();

	$(".look-log").live('click', function() {
	    if ($(this).hasClass('bg-gray'))
		return false;
	    $(this).removeClass('bg-sub').addClass('bg-gray').html("加载中...");
	    var $this = $(this);
	    var txt = '';
	    $.getJSON('<?php echo url("order/cart/get_delivery_log") ?>', {o_d_id: $(this).attr('data-did')}, function(ret) {
		if (ret.status == 0) {
		    alert(ret.message);
		    return false;
		}
		if (ret.result.logs.length > 0) {
		    $.each(ret.result.logs, function(k, v) {
			txt += '<p>' + v.add_time + '&nbsp;&nbsp;&nbsp;&nbsp;' + v.msg + '</p>';
		    });
		    top.dialog({
			content: '<div class="logistics-info padding-big bg-white text-small"><p class="border-bottom border-dotted padding-small-bottom margin-small-bottom"><span class="margin-big-right">物流公司：' + ret.result.delivery_name + '</span>&nbsp;&nbsp;物流单号：' + ret.result.delivery_sn + '</p>' + txt + '</div>',
			title: '查看物流信息',
			width: 680,
			okValue: '确定',
			ok: function() {
			    $this.removeClass('bg-gray').addClass('bg-sub').html("查看物流");
			},
			onclose: function() {
			    $this.removeClass('bg-gray').addClass('bg-sub').html("查看物流");
			}
		    })
			    .showModal();
		}
	    });
	})
    </script>
