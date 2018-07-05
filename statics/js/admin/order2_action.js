/**
 * 		后台订单JS操作类
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */

var order_action = (function() {

	// 参数(tpl_url:必填)、宽度(可选) 
	function alert_tpl(options , width) {
	    var default_options = {
		    title:"加载中...",
		    content: "<div align='center'><img src='./statics/images/ajax_loader.gif' /></div>",
		    onclose:function(){
			    if(this.returnValue.status == 1) {
				    window.location.href= this.returnValue.referer;
			    }
		    }
	    }
	    default_options = $.extend(default_options, options)
	    return top.dialog( default_options ).showModal();
	};
	
	return {
		// 通用弹出框
		dialog:function(param){
			if(!param.width){
				param.width = 1000;
			}
		    var d = alert_tpl(param);
		    d.addEventListener('close', function () {
			if( this.returnValue == 'reload' ){
			    window.location.reload();
			}
		    });
		},
		
		/* 取消  */
		cancel:function(tpl_url) {
		    var param = {
			tpl_url : tpl_url,

		    };
		    alert_tpl(param,'350');
		},
		/* 发货完成后  生成租机协议  */
		xieyi:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'350');
		},
		
		/* 点击发货 */
		complete: function(tpl_url) {
			var param = {
				tpl_url : tpl_url,
				action:'complete'
			};
			alert_tpl(param,'350');
		},
		
		/* 导出 */
		daochu: function(tpl_url) {
			var param = {
				tpl_url : tpl_url,
				action:'daochu'
			};
			alert_tpl(param);
		},
		/* 异常单检测处理  */
		chuli:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'450');
		},
		/* 异常单检测报告  */
		baogao:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'550');
		},
		/* 退款处理  */
		tuikuan:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'400');
		},
		
		/* 退款处理  */
		tuikuan:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'400');
		},
		/* 退货审核  */
		tui_shenhe:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'400');
		},
		
		/* 退货物流单填写 */
		tui_wuliu:function(tpl_url) {
			var param = {
					tpl_url : tpl_url,
			
				};
				alert_tpl(param,'400');
		},
		
	// 初始化
		init:function() {
			if (typeof(order) != 'object') {
				alert('无法读取此订单信息！');
				location.href = '/';
				return;
			}
		}
	};
})();

var common_action = (function() {

	// 参数(tpl_url:必填)、宽度(可选) 
	function common_alert_tpl(data , width) {
		width = parseInt(width) > 0 ? width : 300;
		top.dialog({
                        title:"加载中...",
			url: data.tpl_url,
			// title:"<div align='center'><img src='./statics/images/ajax_loader.gif' /></div>",
			width: width,
			data : data,
			onclose:function(){
				if(this.returnValue.status == 1) {
					window.location.href= this.returnValue.referer;
				}
			}
		})
		.showModal();
	};
	return {
            /* 通用  */
            alerts:function(tpl_url) {
                var param = {
                    tpl_url : tpl_url,
                };
                common_alert_tpl(param,'350');
            },
	};
})();