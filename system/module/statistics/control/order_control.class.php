<?php
hd_core::load_class('init', 'admin');
class order_control extends init_control {

	public function _initialize() {
		parent::_initialize();
		$this->service_order = $this->load->service('order2/statistics');
		$this->channel_appid_service = $this->load->service('channel/channel_appid');
		helper('order/function');
	}

	public function index(){
		$datas = $this->service_order->build_sqlmap(array('days' => 7))->output('sales,districts,payments');
		/* 组装地区信息 */
		if ($datas['districts']) {
			foreach ($datas['districts'] as $k => $v) {
				$datas['districts'][$k]['name'] = $v['name'];
				$datas['districts'][$k]['value'] = $v['value'];
			}
		}
		/* 组装支付方式 */
		if ($datas['payments']) {
			foreach ($datas['payments'] as $k => $v) {
				$datas['pays'][$k] = $v['name'];
			}
		}
		$this->load->librarys('View')->assign('datas',$datas)->display('order');
	}

	public function ajax_getdata(){
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$days = (int) $_GET['days'];
		$start_time = strtotime($_GET['start_time']);
		$end_time = ($_GET['end_time']) ? strtotime($_GET['end_time']) : strtotime(date('Y-m-d 00:00:00'));
		$datas = $this->service_order->build_sqlmap(array('days' => $days ,'start_time' => $start_time ,'end_time' => $end_time))->output('sales');
		showmessage(lang('request_success','statistics/language') ,'', 1 ,$datas);
	}

	/* 后台首页获取统计数据 */
	public function ajax_home() {
		$datas = $this->service_order->get_data();
		$this->load->librarys('View')->assign('datas',$datas);
        $datas = $this->load->librarys('View')->get('datas');
		echo json_encode($datas);
	}
	/**
	 * 导出不同渠道注册会员统计表
	 */
	public function diff_order_export() {
        // 不限制超时时间
        @set_time_limit(0);
        // 内存2M   
        @ini_set('memory_limit', 20*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'订单新增统计'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');
		
		//获取查询的起始时间，未设置则默认7天
		$start_time = $_REQUEST['start_time'] ? strtotime($_REQUEST['start_time']): strtotime(date('Y-m-d',strtotime('-7 day')));
		$end_time = strtotime(date('Y-m-d',strtotime('-1 day')));
		//存储时间数组
		$date_arr = array();
		//将时间拼接成日期格式
		while (($end_time - $start_time) >= 0){
			$date_arr[] = date('Y-m-d',$start_time);
			$start_time = $start_time + 3600*24;
		}
		//初始化最终导出头部数据
		$header_data = array();
		$header_data[] = '日期';
		//初始化appid数组
		$channel_id = array();
		//查询appid数据列表
		$channel_appid_list = $this->channel_appid_service->get_list();
		foreach ($channel_appid_list as $key => $value) {
			$header_data[] = "\t" . $value['name'];
			$channel_id[] = $value['id'];
		}
		//输出头部数据
		$this->export_csv_wirter_row($handle, $header_data);
		//循环日期查找数据
		foreach ($date_arr as $key => $date) {
			$member_list = $this->service_order->get_list_by_group_appid(array('start_time'=> strtotime($date),'end_time'=>strtotime("$date +1 day")));
			//获取appid=》渠道新增会员数量数组
			$appid_n_map = array();
			foreach ($member_list as $key => $value) {
				$appid_n_map[$value['appid']] = $value['N'];
			}
			//初始化一天数据
			$body_data = array();
			$body_data[] = "\t" . $date;
			//遍历appid数据列表，获取一个日期段的各个渠道的会员数据
			foreach ($channel_id as $value) {
				$body_data[] = isset($appid_n_map[$value]) ? $appid_n_map[$value] :0;
			}
			$this->export_csv_wirter_row($handle, $body_data);
		}
		ob_flush();
		flush();
        fclose($handle);
	}
	
	
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
			$row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
}