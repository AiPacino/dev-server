<?php
hd_core::load_class('init', 'admin');
class daily_control extends init_control {

	public function _initialize() {
		parent::_initialize();
		$this->member_service = $this->load->service('member');
		$this->service_order = $this->load->service('statistics/statistics');
		$this->order2_service = $this->load->service('order2/order');
		$this->certification_alipay_service = $this->load->service('member2/certification_alipay');
		helper('order/function');
	}

	public function index(){
		$datas = $this->service_order->build_sqlmap(array('days' => 7,'month'=>6))->output('sales,districts,payments');
		$this->load->librarys('View')->assign('datas',$datas)->display('daily');
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

	public function get_data(){
	    $count_condition_service = $this->load->service('count_condition');
        $order_rate = $count_condition_service->build_sqlmap(array('days' => 7))->get_order_rate();
        $complete_order_rate = $count_condition_service->build_sqlmap(array('days' => 7))->get_complete_order_rate();
        $create_orders = $count_condition_service->build_sqlmap(array('days' => 7))->get_order_create_num();
        $complete_orders = $count_condition_service->build_sqlmap(array('days' => 7,'month'=>6))->get_order_complete_num();
        $member_total = $count_condition_service->build_sqlmap(array('days' => 7))->get_member_total();
        $machine_num = $count_condition_service->build_sqlmap(array('days' => 30,'month'=>6))->get_machine_num();
        $today_num = $count_condition_service->get_today_total();

        $this->load->librarys('View')
            ->assign('create_orders',$create_orders)
            ->assign('complete_orders',$complete_orders)
            ->assign('member_total',$member_total)
            ->assign('machine_num',$machine_num)
            ->assign('order_rate',$order_rate)
            ->assign('complete_order_rate',$complete_order_rate)
            ->assign('datas',$today_num)
            ->display('daily');
    }

    public function get_count_data(){
        $count_total_service = $this->load->service('count_total');
        $count_member_service = $this->load->service('count_member');
        $count_condition_service = $this->load->service('count_condition');
        $datas = $count_total_service->build_sqlmap(array('days' => 7,'month'=>6))->date_count_search();
        $machine_num = $count_condition_service->build_sqlmap(array('days' => 30))->get_machine_num();
        $member_datas = $count_member_service->build_sqlmap(array('days' => 7))->get_member_num();
        $member_rate = $count_member_service->get_member_rata();
        $this->load->librarys('View')
            ->assign('datas',$datas)
            ->assign('member_datas',$member_datas)
            ->assign('machine_num',$machine_num)
            ->assign('member_rate', $member_rate)
            ->display('every_day_data');
    }
	/**
	 * 导出每日业务数据统计（指标分析：每日新增用户数，下单拒绝用户数，通过申请的用户芝麻分统计分布(600-650,650-700,700-750,750-800，>800)，每日下单量，下单通过量）
	 */
	public function daily_data_export() {
        // 不限制超时时间
        @set_time_limit(0);
        // 内存2M   
        @ini_set('memory_limit', 20*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'每日数据统计'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');
		
		//获取查询的起始时间，未设置则默认从2017-12-12【机市上线时间】开始
		$start_time = $_REQUEST['start_time'] ? strtotime($_REQUEST['start_time']): strtotime(date('2017-12-12'));
		$end_time = strtotime(date('Y-m-d',strtotime('-1 day')));
		//初始化最终导出头部数据
		$header_data = array(
			'日期',
			'新增用户数',
			'拒绝用户数',
			'每日下单数',
			'下单通过量',
			'芝麻分(<600)',
			'芝麻分[600-650)',
			'芝麻分[650-700)',
			'芝麻分[700-750)',
			'芝麻分[750-800)',
			'芝麻分(>=800)',
		);
		//输出头部数据
		$this->export_csv_wirter_row($handle, $header_data);
		//存储时间数组
		$date_arr = array();
		//将时间拼接成日期格式
		while (($end_time - $start_time) >= 0){
			$date_arr[] = date('Y-m-d',$start_time);
			$start_time = $start_time + 3600*24;
		}
		//循环日期查找数据
		foreach ($date_arr as $key => $date) {
			//当天的起止时间
			$start_time = strtotime($date);
			$end_time = strtotime("$date +1 day");
			//初始化一天数据
			$body_data = array();
			//日期数据
			$data_date = $date;
			//新增用户数
			$data_newly_member = $this->member_service->get_newly_member($start_time,$end_time);
			//拒绝用户数
			$data_refuse_member = $this->certification_alipay_service->get_refuse_member($start_time,$end_time);
			//每日下单数
			$data_order_place = $this->certification_alipay_service->get_order_place($start_time,$end_time);
			//下单通过量
			$data_order_pass = $this->order2_service->get_order_pass($start_time,$end_time);
			//芝麻分段数据
			$data_zm_group = $this->certification_alipay_service->get_zm_group($start_time,$end_time);
			$body_data = array(
				"\t" . $data_date,
				"\t" . $data_newly_member,
				"\t" . $data_refuse_member,
				"\t" . $data_order_place,
				"\t" . $data_order_pass,
				"\t" . $data_zm_group['a'],
				"\t" . $data_zm_group['b'],
				"\t" . $data_zm_group['c'],
				"\t" . $data_zm_group['d'],
				"\t" . $data_zm_group['e'],
				"\t" . $data_zm_group['f'],
			);
			//芝麻分
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