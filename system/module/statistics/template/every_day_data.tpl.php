<?php include template('header','admin');?>
<?php
    $today_data = end($datas['days']['count']);
    $today_data = array_merge($today_data, end($member_datas['days']['count']));
?>
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
										新增用户数：<?php echo $today_data['register_num'];?>
									</td>
									<td class="text-left">
										登陆用户数：<?php echo $today_data['login_num'];?>
									</td>
									<td class="text-left">
										下单量：<?php echo $today_data['order_num'];?>
									</td>
									<td class="text-left">
										成交量：<?php echo $today_data['complete_order_num'];?>
									</td>
									<td class="text-left">
										成交总额：￥<?php echo $today_data['pay_amount']/100;?>
									</td>
									<td class="text-left">
										下单率：<?php echo $today_data['create_order_rate'];?>%
									</td>
									<td class="text-left">
										成交率：<?php echo $today_data['complete_order_rate'];?>%
									</td>
									<td class="text-left">
										退货量：<?php echo $today_data['refund_num'];?>
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
					<th class="text-left padding-big-left">导出列表</th>
				</tr>
				<tr class="border">
					<td class="padding-big-left padding-big-right">
						<table cellpadding="0" cellspacing="0" class="layout">
							<tbody>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;">一、每日业务数据统计【时间默认：2017-12-12(开始时间可设置)至今】（指标分析：每日新增用户数，下单拒绝用户数，通过申请的用户芝麻分统计分布(600-650,650-700,700-750,750-800,>800)，每日下单量，下单通过量）</td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;padding-left: 25px;">选择开始时间：<input style="width: 150px;height: 28px;display: inline-block" type="text" onclick="laydate({istime: true, format: 'YYYY-MM-DD', max: laydate.now()}),laydate.skin('danlan')" tabindex="0" placeholder="YYYY-MM-DD " value="" name="exporttime" class="input laydate-icon hd-input"> <input id="export" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="导出" /> </td>
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
        <div class="layout margin-big-top clearfix">
            <div class="fl w50 padding-right">
                <table cellpadding="0" cellspacing="0" class="border bg-white layout">
                    <tbody>
                    <tr class="bg-gray-white line-height-40 border-bottom">
                        <th class="text-center padding-big-center">
                            用户性别分布
                        </th>
                    </tr>
                    <tr>
                        <td class="padding">
                            <div id="user_sex" style="height: 400px;">
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
                            用户年龄分布
                        </th>
                    </tr>
                    <tr>
                        <td class="padding">
                            <div id="user_age" style="height: 400px;">
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
	</div>

<script type="text/javascript" src="https://cdn.bootcss.com/echarts/4.0.2/echarts.min.js"></script>
<script src="<?php echo __ROOT__;?>statics/js/laydate/laydate.js" type="text/javascript"></script>
<script type="text/javascript">
	var search = <?php echo json_encode($datas); ?>;
	//月成交量
    var month_complete_order_num = <?php echo json_encode(array_column($datas['months']['count'], 'complete_order_num'))?>;
    //日成交率
    var day_complete_order_rate = <?php echo json_encode(array_column($datas['days']['count'], 'complete_order_rate'))?>;
    //日下单量
    var day_order_num = <?php echo json_encode(array_column($datas['days']['count'], 'order_num'))?>;
    //日成交量
    var day_complete_order_num = <?php echo json_encode(array_column($datas['days']['count'], 'complete_order_num'))?>;
    //日下单率
    var day_create_order_rate = <?php echo json_encode(array_column($datas['days']['count'], 'create_order_rate'))?>;
    //会员总量
    var day_total_member_num = <?php echo json_encode(array_column($member_datas['days']['count'], 'total_num'))?>;
    var machine_num = <?php echo json_encode($machine_num); ?>;
    var member_rate = <?php echo json_encode($member_rate); ?>;
	showchart();
	function showchart(){
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
                data: search.months.dates
            },
            yAxis: {
                show :false,
                type: 'value'
            },

            series : [
                {	name:'成交量',
                    type:'line',
                    data:month_complete_order_num
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
                data: search.days.dates
            },
            yAxis: {
                show :false,
                type: 'value'
            },

            series : [
                {	name:'成交量',
                    type:'line',
                    data:day_complete_order_num
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
                    data: search.days.dates
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
                    data: day_order_num
                },
                {
                    name: '成交量',
                    type: 'bar',
                    data: day_complete_order_num
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
                data: ['下单率', '成交率']
            },
            toolbox: {
                show: false
            },
            calculable: false,
            xAxis: [{
                type: 'category',
                boundaryGap: false,
                data: search.days.dates
            }],
            yAxis: [{
                type: 'value',
                axisLabel: {
                    show: true,
                    interval: 'auto',
                    formatter: '{value} %'
                }
            }],
            series: [{
                name: '下单率',
                type: 'line',
                smooth: true,
                itemStyle: {
                    normal: {
                        areaStyle: {
                            type: 'default'
                        }
                    }
                },
                data: day_create_order_rate
            }, {
                name: '成交率',
                type: 'line',
                smooth: true,
                itemStyle: {
                    normal: {
                        areaStyle: {
                            type: 'default'
                        }
                    }
                },
                data: day_complete_order_rate
            }]
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
                    data : search.days.dates
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
                    data:day_total_member_num,
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
                    z: 3,
                    label: {
                        normal: {
                            position: 'right',
                            show: true
                        }
                    },
                    data: machine_num.days.machine_value
                }
            ]
        });

        //用户性别分布
        myArea = echarts.init(document.getElementById('user_sex'));
        var sex_data = [];
        for (var i=0;i<member_rate.sex.count.length;i++){
            sex_data.push({
                value:member_rate.sex.count[i].value,
                name: member_rate.sex.count[i].name,
                label:{            //饼图图形上的文本标签
                    normal:{
                        show:true,
                        position:'inner', //标签的位置
                        textStyle : {
                            fontWeight : 300 ,
                            fontSize : 16    //文字的字体大小
                        },
                        formatter:'{d}%'


                    }
                }
            });
        }
        myArea.setOption({
            title: {
                text: '用户性别分部'
            },
            legend: {
                // orient: 'vertical',
                // top: 'middle',
                bottom: 10,
                left: 'center',
                data: member_rate.sex.datas
            },
            series : [
                {
                    type: 'pie',
                    radius : '65%',
                    center: ['50%', '50%'],
                    data:sex_data,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 20,
                            shadowOffsetX: 10,
                            shadowColor: 'rgba(0, 0, 0, 0.6)'
                        }
                    }
                }
            ]
        });

        //用户年龄分布
        myArea = echarts.init(document.getElementById('user_age'));
        var age_data = [];
        for (var i=0;i<member_rate.age.count.length;i++){
            age_data.push({
                value:member_rate.age.count[i].value,
                name: member_rate.age.count[i].name,
                label:{            //饼图图形上的文本标签
                    normal:{
                        show:true,
                        position:'inner', //标签的位置
                        textStyle : {
                            fontWeight : 300 ,
                            fontSize : 16    //文字的字体大小
                        },
                        formatter:'{d}%'


                    }
                }
            });
        }
        myArea.setOption({
            title: {
                text: '用户年龄分部'
            },
            legend: {
                // orient: 'vertical',
                // top: 'middle',
                bottom: 10,
                left: 'center',
                data: member_rate.age.datas
            },
            series : [
                {
                    type: 'pie',
                    radius : '65%',
                    center: ['50%', '50%'],
                    data:age_data,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 20,
                            shadowOffsetX: 10,
                            shadowColor: 'rgba(0, 0, 0, 0.6)'
                        }
                    }
                }
            ]
        });

	}
	$(function(){
		//导出
		$('#export').click(function(){
			//获取导出的开始时间
			var start_time = $('input[name="exporttime"]').val();
			window.location.href = "<?php echo url('daily_data_export')?>" + "&start_time=" + start_time;
		});
	})
</script>
<?php include template('footer','admin');?>
	