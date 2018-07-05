<?php include template('header','admin');?>
<body>
	<div class="fixed-nav layout">
		<ul>
			<li class="first">
				日报统计
				<a id="addHome" title="添加到首页快捷菜单">
					[+]
				</a>
			</li>
			<li class="spacer-gray">
			</li>
			<li>
				<a class="current" href="javascript:;">
				</a>
			</li>
		</ul>
		<div class="hr-gray">
		</div>
	</div>
	<div class="content padding-big have-fixed-nav">
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-left padding-big-left">
						本日数据
					</th>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<tr class="line-height-40">
									<td class="text-left">
										新增用户数：<?php echo $datas['today']['user'];?>
									</td>
									<td class="text-left">
										登陆用户数：<?php echo $datas['today']['login_user'];?>
									</td>
									<td class="text-left">
										下单量：<?php echo $datas['today']['order'];?>
									</td>
									<td class="text-left">
										成交量：<?php echo $datas['today']['payment'];?>
									</td>
									<td class="text-left">
										成交总额：￥<?php echo $datas['today']['amount'];?>
									</td>
									<td class="text-left">
										下单率：<?php echo $datas['today']['xiadanlv'];?>%
									</td>
									<td class="text-left">
										成交率：<?php echo $datas['today']['chengjiaolv'];?>%
									</td>
									<td class="text-left">
										退货量：<?php echo $datas['today']['refund'];?>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
			<tbody>
				<tr class="bg-gray-white line-height-40 border-bottom">
					<th class="text-center padding-big-center">
						日平均成交量趋势
					</th>
				</tr>

				<tr>
					<td class="padding">
						<div id="statistics" style="height: 400px;">
						      <div id="month_payments" style="width: 50%;height: 400px;float:left;border-right:2px dashed #100" align="center">
						      </div>
						      <div id="day_payments" style="width: 50%;height: 400px;float:right" align="center">
						      </div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="layout margin-big-top clearfix">
			<div class="fl w50 padding-right">
				<table cellpadding="0" cellspacing="0" class="border bg-white layout">
					<tbody>
						<tr class="bg-gray-white line-height-40 border-bottom">
							<th class="text-center padding-big-center">
								近七日订单数量
							</th>
						</tr>
						<tr>
							<td class="padding">
								<div id="order" style="height: 400px;">
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="fl w50 padding-left">
				<table cellpadding="0" cellspacing="0" class="border bg-white layout">
					<tbody>
						<tr class="bg-gray-white line-height-40 border-bottom">
							<th class="text-center padding-big-center">
                                近七日转化率趋势
							</th>
						</tr>
						<tr>
							<td class="padding">
								<div id="conversion_rate" style="height: 400px;">
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<div class="layout margin-big-top clearfix">
			<div class="fl w50 padding-right">
				<table cellpadding="0" cellspacing="0" class="border bg-white layout">
					<tbody>
						<tr class="bg-gray-white line-height-40 border-bottom">
							<th class="text-center padding-big-center">
								用户总量趋势
							</th>
						</tr>
						<tr>
							<td class="padding">
								<div id="regist_user" style="height: 400px;">
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="fl w50 padding-left">
				<table cellpadding="0" cellspacing="0" class="border bg-white layout">
					<tbody>
						<tr class="bg-gray-white line-height-40 border-bottom">
							<th class="text-center padding-big-center">
								近30日成交机型TOP10
							</th>
						</tr>
						<tr>
							<td class="padding">
								<div id="jixing" style="height: 400px;">
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

<script type="text/javascript" src="https://cdn.bootcss.com/echarts/4.0.2/echarts.min.js"></script>
<script type="text/javascript">
	var search = <?php echo json_encode($datas['search']); ?>;
	var create_orders = <?php echo json_encode($create_orders); ?>;
    var complete_orders = <?php echo json_encode($complete_orders); ?>;
    var member_total = <?php echo json_encode($member_total); ?>;
    var machine_num = <?php echo json_encode($machine_num); ?>;
    var order_rate = <?php echo json_encode($order_rate); ?>;
    var complete_order_rate = <?php echo json_encode($complete_order_rate); ?>;
	showchart(create_orders,complete_orders,member_total,machine_num, order_rate,complete_order_rate);
	function showchart(create_orders,complete_orders,member_total,machine_num, order_rate,complete_order_rate){
	    console.log(create_orders);
        //日平均趋势1
        myArea = echarts.init(document.getElementById('month_payments'));
        myArea.setOption({
            color: ['#006699'],
            tooltip : {
                trigger: 'axis'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '8%',
                containLabel: true
            },
            backgroundColor:'#827d7d',
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: complete_orders.months.dates
            },
            yAxis: {
                show :false,
                type: 'value'
            },

            series : [
                {	name:'成交量',
                    type:'line',
                    data:complete_orders.months.orders
                }
            ]
        });
        //日平均趋势2
        myArea = echarts.init(document.getElementById('day_payments'));
        myArea.setOption({
            color: ['#006699'],
            tooltip : {
                trigger: 'axis'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '8%',
                containLabel: true
            },
            backgroundColor:'#827d7d',
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: complete_orders.days.dates
            },
            yAxis: {
                show :false,
                type: 'value'
            },

            series : [
                {	name:'成交量',
                    type:'line',
                    data:complete_orders.days.orders
                }
            ]
        });
        //七日订单统计
        myArea = echarts.init(document.getElementById('order'));
        myArea.setOption({
            title : {
                text: '近七日订单数量'
            },
            color: ['#003366', '#006699'],
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                data: ['下单量','成交量']
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    axisTick: {show: false},
                    data: create_orders.days.dates
                }
            ],
            yAxis: [
                {
                    type: 'value'
                }
            ],
            series: [
                {
                    name: '下单量',
                    type: 'bar',
                    barGap: 0,
                    data: create_orders.days.orders
                },
                {
                    name: '成交量',
                    type: 'bar',
                    data: complete_orders.days.orders
                }
            ]
        });

        //近七日转化率趋势
        myArea = echarts.init(document.getElementById('conversion_rate'));
        myArea.setOption({
            title: {
                text: '近七日转化率趋势（单位：%）'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data:['下单率','成交率']
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: order_rate.days.dates
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name:'下单率',
                    type:'line',
                    stack: '总量',
                    data:order_rate.days.create_order_rate
                },
                {
                    name:'成交率',
                    type:'line',
                    stack: '总量',
                    data:complete_order_rate.days.complete_order_rate
                }
            ]
        });

        //七日新增用户数
        myArea = echarts.init(document.getElementById('regist_user'));
        myArea.setOption({
            color: ['#006699'],
            title : {
                text: '用户总量趋势'
            },
            tooltip : {
                trigger: 'axis'
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    boundaryGap : true,
                    data : member_total.days.dates
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : [
                {	name:'当日总用户数',
                    type:'line',
                    stack: '总量',
                    data:member_total.days.members,
                    itemStyle : { normal: {label : {show: true}}}
                }
            ]
        });
        //近30日成交机型TOP10
        myArea = echarts.init(document.getElementById('jixing'));
        myArea.setOption({
            color:['#006699'],
            title: {
                text: '近30日成交机型TOP10'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'value',
                boundaryGap: [0, 0.01]
            },
            yAxis: {
                type: 'category',
                data: machine_num.days.machine_name
            },
            series: [
                {
                    name: '近30日',
                    type: 'bar',
                    data: machine_num.days.machine_value
                }
            ]
        });

	}
</script>
<?php include template('footer','admin');?>
	