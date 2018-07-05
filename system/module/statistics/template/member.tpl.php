<?php include template('header','admin');?>
<style>
	.upload{
		padding: 0px 10px;
		height: 30px;
		line-height: 30px;
		position: relative;
		border: 1px solid #999;
		text-decoration: none;
		vertical-align: middle;
		color: #666;
	}
	.change{
		position: absolute;
		overflow: hidden;
		right: 0;
		top: 0;
		opacity: 0;
		width: 66px;
		cursor: pointer;
	}
</style>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">会员统计<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
				<li class="spacer-gray"></li>
				<li><a class="current" href="javascript:;"></a></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		<div class="content padding-big have-fixed-nav">
			<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
				<tbody>
					<tr class="bg-gray-white line-height-40 border-bottom">
						<th class="text-left padding-big-left">会员统计</th>
					</tr>
					<tr class="border">
						<td class="padding-big-left padding-big-right">
							<table cellpadding="0" cellspacing="0" class="layout">
								<tbody>
									<tr class="line-height-40">
										<td class="text-left">今日新增会员：<?php echo $member['today']?></td>
										<td class="text-left">本月新增会员：<?php echo $member['tomonth']?></td>
										<td class="text-left">会员总数：<?php echo $member['num']?></td>
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
						<th class="text-left padding-big-left">导出数据</th>
					</tr>
					<tr class="border">
						<td class="padding-big-left padding-big-right">
							<table cellpadding="0" cellspacing="0" class="layout">
								<tbody>
									<tr class="line-height-40">
										<td class="text-left" style="margin-top: 10px;">一、不同入口每日注册会员导出(时间截止：至今)</td>
									</tr>
									<tr class="line-height-40">
										<td class="text-left" style="margin-top: 10px;">开始时间(默认一周)：<input style="width: 150px;height: 28px;display: inline-block" type="text" onclick="laydate({istime: true, format: 'YYYY-MM-DD', max: laydate.now()}),laydate.skin('danlan')" tabindex="0" placeholder="YYYY-MM-DD " value="" name="exporttime" class="input laydate-icon hd-input"> <input id="export" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="导出" /> </td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
<!--					<tr class="border">
						<td class="padding-big-left padding-big-right">
							<table cellpadding="0" cellspacing="0" class="layout">
								<tbody>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;">二、拒绝用户数据统计【时间默认：当前时间前3天(开始时间可设置)至今】（指标分析：拒绝用户的基本信息（身份证、手机号、姓名），申请时间（如'2018-01-01 15:23:21'），拒绝原因，用户的芝麻分）</td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;padding-left: 25px;">选择开始时间：<input style="width: 150px;height: 28px;display: inline-block" type="text" onclick="laydate({istime: true, format: 'YYYY-MM-DD', max: laydate.now()}),laydate.skin('danlan')" tabindex="0" placeholder="YYYY-MM-DD " value="" name="export_reject_time" class="input laydate-icon hd-input"> <input id="export_reject_user" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="导出" /> </td>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr class="border">
						<td class="padding-big-left padding-big-right">
							<table cellpadding="0" cellspacing="0" class="layout">
								<tbody>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;">三、下单用户数据统计【时间默认：当前时间前7天(开始时间可设置)至今】（指标分析：正常下单的用户基本信息，下单时间，业务信息（商品名、商品金额、租赁周期、每月还款/扣款金额）、收货地址、收货手机号）</td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;padding-left: 25px;">选择开始时间：<input style="width: 150px;height: 28px;display: inline-block" type="text" onclick="laydate({istime: true, format: 'YYYY-MM-DD', max: laydate.now()}),laydate.skin('danlan')" tabindex="0" placeholder="YYYY-MM-DD " value="" name="export_order_time" class="input laydate-icon hd-input"> <input id="export_order_user" class="button bg-main text-normal" style="padding: 3px 20px;" type="button" value="导出" /> </td>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr class="border">
						<td class="padding-big-left padding-big-right">
							<table cellpadding="0" cellspacing="0" class="layout">
								<tbody>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;">四、下单用户蚁盾风控值请求</td>
								</tr>
								<tr class="line-height-40">
									<td class="text-left" style="margin-top: 10px;padding-left: 25px;">
									<a href="javascript:;" class="upload button bg-main">选择文件
										<input class="change" id="export_member_yidun_score" type="file" multiple="multiple" />
									</a>
									</td>
								</tr>
								</tbody>
							</table>
						</td>
					</tr>-->
				</tbody>
			</table>
			<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
				<tbody>
					<tr class="bg-gray-white line-height-40 border-bottom">
						<th class="text-left padding-big-left">
							会员统计图表
							<div class="count-date fr">
							<a href="javascript:;" class="current" onclick="showchart(6,0,0)">
								最近7天
							</a>
							<a href="javascript:;" onclick="showchart(29,0,0)">
								最近30天
							</a>
							<input type="text" onclick="laydate({istime: true, format: 'YYYY-MM-DD', max: laydate.now()}),laydate.skin('danlan')" tabindex="0" placeholder="YYYY-MM-DD " value="" name="stime" class="input laydate-icon hd-input">
							&emsp; ~&emsp;
							<input type="text" onclick="laydate({istime: true, format: 'YYYY-MM-DD', max: laydate.now()}),laydate.skin('danlan')" tabindex="0" placeholder="YYYY-MM-DD " value="" name="etime" class="input laydate-icon hd-input">
							<input id="submit" class="button bg-main text-normal" type="button" value="确定" />
						</div>
						</th>
					</tr>
					<tr>
						<td class="padding">
							<div id="statistics" style="height: 400px;"></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<script type="text/javascript" src="<?php echo __ROOT__;?>statics/js/echarts/dist/echarts.js"></script>
		<script src="<?php echo __ROOT__;?>statics/js/laydate/laydate.js" type="text/javascript"></script>
		<script type="text/javascript">
			var ret ;
			showchart(6,0,0);
			function showchart(days,stime,etime){
				getdata(days,stime,etime);
				// 路径配置
				require.config({
					paths: {
						echarts: '<?php echo __ROOT__;?>statics/js/echarts/dist' //配置路径
					}
				});
				// 使用
				require(
				[
					'echarts',
					'echarts/chart/line'// K线图
				],DrawEChart);
				function DrawEChart(ec) {
					//销售统计
					myChart = ec.init(document.getElementById('statistics'), 'macarons');
					myChart.setOption({
						/*title : {
						text: '销量',//标题
						subtext: '描述',//文字
						x:'center'//坐标
						},*/
						tooltip: {
							trigger: 'axis'
						},
						legend: {
							data: ['新增会员','登录会员','认证会员']
						},
						toolbox: {
							show: false
						},
						calculable: false,
						xAxis: [{
							type: 'category',
							boundaryGap: false,
							data: ret.member.xAxis,
						}],
						yAxis: [{
							type: 'value'
						}],
						series: [{
							name: '新增会员',
							type: 'line',
							smooth: true,
							itemStyle: {
								normal: {
									areaStyle: {
										type: 'default'
									}
								}
							},
							data: ret.member.reg[0],
						},{
                            name: '登录会员',
                            type: 'line',
                            smooth: true,
                            itemStyle: {
                                normal: {
                                    areaStyle: {
                                        type: 'default'
                                    }
                                }
                            },
                            data: ret.member.login[0],
                        },{
                            name: '认证会员',
                            type: 'line',
                            smooth: true,
                            itemStyle: {
                                normal: {
                                    areaStyle: {
                                        type: 'default'
                                    }
                                }
                            },
                            data: ret.member.credit[0],
                        }]
					});
				}
			}
		
			//通过Ajax获取数据
			function getdata(days,stime,etime){
				$.ajax({
					type: "get",
					async: false,
					//同步执行
					url: "<?php echo url('ajax_getdata')?>",
					dataType: "json",
					data:{days:days,stime:stime,etime:etime,formhash:formhash},
					success: function(result) {
						ret = result;
					},
					error: function(errorMsg) {
						alert("不好意思，大爷，图表请求数据失败啦!");
					}
				});
			}
			
			$(function(){
				$('.count-date a').on('click',function(){
					$(this).addClass('current').siblings().removeClass('current');
				})
				$('#submit').on('click',function(){
					var stime = $('input[name="stime"]').val();
					var etime = $('input[name="etime"]').val();
					showchart(0,stime,etime);
				})
				var $val=$("input[type=text]").first().val();
				$("input[type=text]").first().focus().val($val);
				//导出
				$('#export').click(function(){
					//获取导出的开始时间
					var start_time = $('input[name="exporttime"]').val();
					window.location.href = "<?php echo url('diff_memeber_export')?>" + "&start_time=" + start_time;
				});
				//导出拒绝用户信息
				$('#export_reject_user').click(function(){
					//获取导出的开始时间
					var start_time = $('input[name="export_reject_time"]').val();
					window.location.href = "<?php echo url('export_reject_user')?>" + "&start_time=" + start_time;
				});
				//导出下单用户信息
				$('#export_order_user').click(function(){
					//获取导出的开始时间
					var start_time = $('input[name="export_order_time"]').val();
					window.location.href = "<?php echo url('export_order_user')?>" + "&start_time=" + start_time;
				});
				//获取会员蚁盾分数
				$('#export_member_yidun_score').on('change', function () {
					var oFormData = new FormData();
					oFormData.append('file_data', $(this)[0].files[0]);
					$.ajax({
						async:false,
						url: "<?php echo url('export_member_yidun_score')?>",
						data: oFormData,
						type: 'POST',
						contentType: false,
						processData: false,
						success: function(result) {
							result = JSON.parse(result);
							if( result.status == 1 ){
								window.location.href = "<?php echo url('export_member_yidun_score_file')?>";
							}else{
								alert(result.info)
							}	
						},
						error: function(errorMsg) {
							alert("请求出错，请从新尝试!");
						}
					})
				})
			})
		</script>
<?php include template('footer','admin');?>
