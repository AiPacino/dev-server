<?php
hd_core::load_class('init', 'admin');
class member_control extends init_control {
	public function _initialize() {
		parent::_initialize();
		$this->member_service = $this->load->service('member');
		$this->certification_service = $this->load->service('member2/certification_alipay');
		$this->order_service = $this->load->service('order2/order');
		$this->channel_appid_service = $this->load->service('channel/channel_appid');
	}

	public function index(){
		/*新增会员*/
		//今日新增
        $sqlmap['_string']='date_format(from_UNIXTIME(`register_time`),\'%Y-%m-%d\') = date_format(now(),\'%Y-%m-%d\')';
        $member['today'] = $this->member_service->_count($sqlmap);
        //本月新增
        $sqlmap['_string']='date_format(from_UNIXTIME(`register_time`),\'%Y-%m\') = date_format(now(),\'%Y-%m\')';
        $member['tomonth'] = $this->member_service->_count($sqlmap);

        //会员总数
        $member['num'] = $this->member_service->_count([]);
        $this->load->librarys('View')->assign('member',$member)->display('member');

	}

	public function ajax_getdata(){
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$row = $this->member_service->build_data($_GET);
		$this->load->librarys('View')->assign('row',$row);
        $row = $this->load->librarys('View')->get('row');
		echo json_encode($row);
	}
	/**
	 * 导出不同渠道注册会员统计表
	 */
	public function diff_memeber_export() {
        // 不限制超时时间
        @set_time_limit(0);
        // 内存2M
        @ini_set('memory_limit', 20*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'注册会员统计'.time().'-'.rand(1000, 9999).'.csv');
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
			$header_data[] =  $value['name'];
			$channel_id[] = $value['id'];
		}
		//输出头部数据
		$this->export_csv_wirter_row($handle, $header_data);
		//循环日期查找数据
		foreach ($date_arr as $key => $date) {
			$member_list = $this->member_service->get_list_by_group_appid(array('start_time'=> strtotime($date),'end_time'=>strtotime("$date +1 day")));
			//获取appid=》渠道新增会员数量数组
			$appid_n_map = array();
			foreach ($member_list as $key => $value) {
				$appid_n_map[$value['appid']] = $value['N'];
			}
			//初始化一天数据
			$body_data = array();
			$body_data[] =  $date;
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

	/**
	 * 导出拒绝用户的信息
	 * 拒绝用户：捞取近两个月拒绝用户的基本信息（身份证、手机号、姓名），申请时间（如'2018-01-01 15:23:21'），拒绝原因，用户的芝麻分
	 */
	public function export_reject_user() {
		// 不限制超时时间
		@set_time_limit(0);
		// 内存2M
		@ini_set('memory_limit', 20*1024*1024);
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename='.'拒绝用户信息表'.time().'-'.rand(1000, 9999).'.csv');
		header('Cache-Control: max-age=0');
		$handle = fopen('php://output', 'a');
		//获取查询的起始时间
		$start_time = $_REQUEST['start_time'] ? strtotime($_REQUEST['start_time']): strtotime('-3 day');
		$end_time = time();
		//存储时间数组(最后导出数组)
		$header_data = array(
			'用户姓名',
			'用户身份证号',
			'用户手机号',
			'申请时间',
			'芝麻分是否低于600',
			'扫脸是否通过',
			'账号是否有风险',
			'用户芝麻分',
		);
		//输出头部数据
		$this->export_csv_wirter_row($handle, $header_data);
		//循环日期查找数据
		$body_data = [];
		if ( $start_time < $end_time ) {
			$where = ['zm_score < 600 OR zm_face = 0 OR zm_risk = 0'];
			$where['create_time'] = ['BETWEEN',[date('Y-m-d h:i:s',$start_time),date('Y-m-d h:i:s',$end_time)]];
			$count = $this->certification_service->get_list_by_count_csv( $where );
			$page = 1;
			while($count > 0){
				$option = array(
					'page'=>$page,
					'size'=>'100',
					'address_info'=>'true',
					'orderby'=>'create_time desc',
				);
				$reject_user = $this->certification_service->get_list_by_reject_user( array('start_time'=> date('Y-m-d h:i:s',$start_time),'end_time'=>date('Y-m-d h:i:s',$end_time) ), $option );
				//获取到所有用户信息
				foreach ($reject_user as $k => $v) {
					if( array_key_exists($v['member_id'],$body_data) ){
						if( strtotime($v['create_time']) > strtotime($body_data[$v['member_id']]['create_time']) ){
							if($v['zm_score'] < 600){
								$body_data[$v['member_id']]['zm_score'] = '低于600';
								$body_data[$v['member_id']]['create_time'] = $v['create_time'];
							}else{
								$body_data[$v['member_id']]['zm_score'] = '高于600';
							}
							if($v['zm_face'] == 0){
								$body_data[$v['member_id']]['zm_face'] = '不通过';
								$body_data[$v['member_id']]['create_time'] = $v['create_time'];
							}else{
								$body_data[$v['member_id']]['zm_face'] = '通过';
							}
							if($v['zm_risk'] == 0){
								$body_data[$v['member_id']]['zm_risk'] = '有风险';
								$body_data[$v['member_id']]['create_time'] = $v['create_time'];
							}else{
								$body_data[$v['member_id']]['zm_risk'] = '无风险';
							}
						}
					}else{
						if($v['zm_score'] < 600){
							$body_data[$v['member_id']]['zm_score'] = '低于600';
						}else{
							$body_data[$v['member_id']]['zm_score'] = '高于600';
						}
						if($v['zm_face'] == 0){
							$body_data[$v['member_id']]['zm_face'] = '不通过';
						}else{
							$body_data[$v['member_id']]['zm_face'] = '通过';
						}
						if($v['zm_risk'] == 0){
							$body_data[$v['member_id']]['zm_risk'] = '有风险';
						}else{
							$body_data[$v['member_id']]['zm_risk'] = '无风险';
						}
						$body_data[$v['member_id']]['name'] = $v['name'];
						$body_data[$v['member_id']]['member_id'] = $v['member_id'];
						$body_data[$v['member_id']]['cert_no'] = $v['cert_no'];
						$body_data[$v['member_id']]['mobile'] = $v['mobile'];
						$body_data[$v['member_id']]['create_time'] = $v['create_time'];
						$body_data[$v['member_id']]['zm_fen'] = $v['zm_score'];
					}
				}
				$page++;
				$count -= 100;
			}
		}
		foreach($body_data as $key=>$val){
			$_row = [
				"\t" . $val['name'],
				"\t" . $val['cert_no'],
				"\t" . $val['mobile'],
				"\t" . $val['create_time'],
				"\t" . $val['zm_score'],
				"\t" . $val['zm_face'],
				"\t" . $val['zm_risk'],
				"\t" . $val['zm_fen'],
			];
			//初始化一天数据
			$this->export_csv_wirter_row($handle, $_row);
		}
		ob_flush();
		flush();
		fclose($handle);
	}

	/**
	 * 下单用户
	 * 下单用户：捞取近两个月正常下单的用户基本信息，下单时间，业务信息（商品名、商品金额、租赁周期、每月还款/扣款金额）、收货地址、收货手机号。
	 */
	public function export_order_user() {
		// 不限制超时时间
		@set_time_limit(0);
		// 内存2M
		@ini_set('memory_limit', 20*1024*1024);
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename='.'下单用户信息表'.time().'-'.rand(1000, 9999).'.csv');
		header('Cache-Control: max-age=0');
		$handle = fopen('php://output', 'a');
		//获取查询的起始时间，未设置则默认一周
		$start_time = $_REQUEST['start_time'] ? strtotime($_REQUEST['start_time']): strtotime(date('Y-m-d',strtotime('-7 day')));
		$end_time = time();
		//存储时间数组(最后导出数组)
		$header_data = array(
			'用户姓名',
			'用户身份证号',
			'用户手机号',
			'下单时间',
			'商品名',
			'商品金额',
			'租赁周期',
			'每月还款/扣款金额',
			'收货地址',
			'收货手机号',
		);
		//输出头部数据
		$this->export_csv_wirter_row($handle, $header_data);
		//循环日期查找数据
		if ( $start_time < $end_time ) {
			$order_count = $this->order_service->get_order_count( array('start_time'=> $start_time,'end_time'=>$end_time) );
			$page = 1;
			while($order_count > 0){
				$where = array(
					'begin_time'=> $start_time,
					'end_time'=>$end_time,
				);
				$option = array(
					'page'=>$page,
					'size'=>'100',
					'address_info'=>'true',
				);
				$order_user = $this->order_service->get_order_list_csv( $where,$option );
				foreach($order_user as $k=>$v){
					$_row = [
						"\t" . $v['realname'],
						"\t" . $v['cert_no'],
						"\t" . $v['mobile'],
						"\t" . date('Y-m-d h:i:s',$v['create_time']),
						"\t" . $v['goods_name'],
						"\t" . $v['all_amount'],
						"\t" . $v['zuqi'],
						"\t" . $v['zujin'],
						"\t" . $v['complete_address'],
						"\t" . $v['address_info']['mobile']
					];
					//初始化一天数据
					$this->export_csv_wirter_row($handle, $_row);
				}
				$page++;
				$order_count -= 100;
			};
		}
		ob_flush();
		flush();
		fclose($handle);
	}
	public function export_member_yidun_score_file() {
		$file = DOC_ROOT . 'data/yidun.txt';
		if(is_file($file)) {
			$fileName = basename($file);  //获取文件名
			header("Content-Type:application/octet-stream");  
			header("Content-Disposition:attachment;filename=".$fileName);  
			header("Accept-ranges:bytes");  
			header("Accept-Length:".filesize($file));  
			$h = fopen($file, 'r');//打开文件
			echo fread($h, filesize($file));  
        }else{
            echo "文件不存在！";
            exit;
        }
	}
	/**
	 * 获取用户
	 */
	public function export_member_yidun_score(){
		
		$tmp_file = $_FILES ['file_data'] ['tmp_name']; //临时文件
		$name = $_FILES['file_data']['name'];   //上传文件名
		
		
        // 后缀
        $file_type = substr($name,strrpos($name, '.')+1);

		//根据上传类型做不同处理
		require_once APP_PATH . 'library/PHPExcel/PHPExcel/Reader/Excel2007.php';
		require_once APP_PATH . 'library/PHPExcel/PHPExcel/IOFactory.php';
		
		if ($file_type == 'xls') {
			$reader = PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
		}
		if ($file_type == 'xlsx') {
			$reader = new PHPExcel_Reader_Excel2007();
		}
		//读excel文件

		$PHPExcel = $reader->load($tmp_file, 'utf-8'); // 载入excel文件
		$sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
		$highestRow = $sheet->getHighestRow(); // 取得总行数
		$highestColumm = $sheet->getHighestColumn(); // 取得总列数

		//把Excel数据保存数组中

		$data = array();
		for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {        //循环读取每个单元格的内容。注意行从1开始，列从A开始
			for ($colIndex = 'A'; $colIndex <= $highestColumm; $colIndex++) {
				$addr = $colIndex . $rowIndex;
				$cell = $sheet->getCell($addr)->getValue();
				if ($cell instanceof PHPExcel_RichText) { //富文本转换字符串
					$cell = $cell->__toString();
				}
				$data[$rowIndex][$colIndex] = $cell;
			}
		}
		
//		// 不限制超时时间
//		@set_time_limit(0);
//		// 内存2M
//		@ini_set('memory_limit', 200*1024*1024);
//		header('Content-Type: application/vnd.ms-excel');
//		header('Content-Disposition: attachment;filename='.'用户分数请求'.time().'-'.rand(1000, 9999).'.csv');
//		header('Cache-Control: max-age=0');
//		$handle = fopen('php://output', 'a');
		
		$file_name = DOC_ROOT . 'data/yidun.txt';
		//输出头部数据
		$body_data = array(
			'用户ID ',
			'姓名 ',
			'手机号 ',
			'身份证号 ',
			'注册时间 ',
			'收货地址 ',
			'收货地区 ',
			'收货城市 ',
			'收货省份 ',
			"蚁盾分数\r\n",
		);
		$myfile = fopen($file_name, 'w+');
		foreach ($body_data as $key => $value){
			$value = mb_convert_encoding($value,'GBK');
			fwrite($myfile, $value);
		}
		unset($data[1]);
		foreach ($data as $key => $value){
			if( isset($value['A']) && intval($value['A']) ) {
				$value['A'] = intval($value['A']);
				//获取用户信息，并请求蚁盾接口
				// 获取用户认证信息
				$member_table = \hd_load::getInstance()->table('member/member');
				$fields = ['id as user_id','realname as user_name','mobile','email','login_ip as ip','cert_no','register_time as user_reg_time'];
				$user_info = $member_table->field($fields)->where(['id'=>$value['A']])->find();
				
				$address_table = \hd_load::getInstance()->table('member2/member_address');
				$address_info = $address_table->get_address_info($value['A']);
				$address_info = $address_info[0];
				$this->address_id = $address_info['id'];
				$this->district_id = $address_info['district_id'];
				// 查询 省市区ID和名称
				$district_service = \hd_load::getInstance()->service('admin/district');

				$district_info = $district_service->get_info($this->district_id);
				$city_info = $district_service->get_info($district_info['parent_id']);
				$province_info = $district_service->get_info($city_info['parent_id']);

				$user_info['receive_name'] 		= $address_info['name'];
				$user_info['receive_mobile'] 	= $address_info['mobile'];
				$user_info['receive_address'] 	= $address_info['address'];
				$user_info['receive_county'] 	= $district_info['name'];
				$user_info['receive_city'] 		= $city_info['name'];
				$user_info['receive_province'] 	= $province_info['name'];
				$info = $user_info;
				//调取蚁盾风控验证接口
				$yidun_result = $this->__check_risk($user_info);
				if( !$yidun_result ) {
					echo json_encode(['status'=>0,'info'=> get_error()]);exit;
				}
				$body_data = array(
					$info['user_id'].' ',
					$info['user_name'].' ',
					$info['mobile'].' ',
					$info['cert_no'].' ',
					$info['user_reg_time'].' ',
					$info['receive_address'].' ',
					$info['receive_county'].' ',
					$info['receive_city'].' ',
					$info['receive_province'].' ',
					$this->score."\r\n",
				);
				foreach ($body_data as $value){
					$value = mb_convert_encoding($value,'GBK');
					fwrite($myfile, $value);
				}
			}
		}
		
		echo json_encode(['status'=>1]);exit;
	}
	/**
	 * 蚁盾验证用户风险接口
	 * @param array $data
	 * $data = [
	 *		"userName" => '',        //string,用户姓名
	 *		"userId" => '',			 //string,用户ID
	 *		"mobile" => '',			 //string,用户手机号
	 *		"email" => '',			 //string,用户邮箱
	 *		"apdIdToken" => '',		 //string,
	 *		"ip" => '',				 //string,用户ip
	 *		"wifiMac" => '',		 //string,mac地址
	 *		"imei" => '',			 //string,用户imei
	 *		"imsi" => '',			 //string,用户imsi
	 *		"latitude" => '',		 //string,纬度
	 *		"longitude" => '',		 //string,经度
	 *		"platform" => '',		 //string,平台，ios,android,windows
	 *		"userAgent" => '',		 //string,userAgent
	 *		"certNo" => '',			 //string,身份证号
	 * ]
	 * @return boolen true接口验证；false接口验证有误
	 */
	private function __check_risk($data) {
		//过滤参数
		if( !$this->__parse_data($data)){
			$this->flag = false;
			return false;
		}
		//调用curl进行post请求
		$headers = array(
			"Content-type: application/json;charset='utf-8'", 
			"Accept: application/json", 
			"Cache-Control: no-cache", 
			"Pragma: no-cache", 
		);

		$result = \zuji\Curl::post(config('YIDUN_REQUEST_URL'), json_encode($data), $headers);
		//正常的$result的参数信息
		/*
		 $result = {
			"id":"|738ad825-e560-4be9-a52c-8a7b5203ba8e",
			"code":"OK",
			"message":"业务处理成功！",
			"verifyId":null,
			"verifyUri":null,
			"decision":"accept",
			"knowledge":{
				"traceId":"bda8b3da-f0d9-464f-9d5b-64f466fc59af",
				"event":{
					"content":null,
					"userName":null,
					"amt":null,
					"orderId":null,
					"mac":null,
					"cardNo":null,
					"cookieId":null,
					"pageName":null,
					"titile":null,
					"rdsContent":null,
					"rdsSource":null,
					"platform":"linux",
					"certNo":"123456",
					"userAgent":"linux",
					"code":"EC_LOGIN",
					"mobile":"13555554444",
					"imsi":"467894",
					"latitude":"33.525",
					"email":"sdfdf@hotmail.com",
					"userId":"123456",
					"wifiMac":"12:22",
					"longitude":"36.222",
					"apdIdToken":"1231",
					"ip":"0.0.0.0",
					"imei":"1234567941"
				},
				"identification":{
					"certNo":null,
					"mobile":null
				},
				"code":"EC_LOGIN"
			},
			"models":[
				{
					"code":"",
					"score":"50.02"
				}
			],
			"strategies":[
				{
					"id":"",
					"name":"",
					"level":"",
					"decision":""
				}
			]
		}
		*/

		//蚁盾结果json串转数组
		$result = json_decode($result,true);
		$models 	= $this->__parse_is_set('models', $result, false);
		$strategies = $this->__parse_is_set('strategies', $result, false);
		
		$this->verify_id = $this->__parse_is_set('verifyId', $result);
		$this->verify_uri = $this->__parse_is_set('verifyUri', $result);
		$this->decision = $this->__parse_is_set('decision', $result);
		$this->score = $this->__parse_is_set('score', $models[0]);
		$this->level = $this->__parse_is_set('level', $strategies[0]);

		$yidun_date = [
			'event_id' =>$this->__parse_is_set('id', $result),
			'event_code' =>'SCENE_LOAN',
			'decision' =>$this->__parse_is_set('decision', $result),
			'verifyId' =>$this->__parse_is_set('verifyId', $result),
			'verifyUri' =>$this->__parse_is_set('verifyUri', $result),
			'score' =>$this->score,
			'level' =>$this->level,
			'user_name' =>$this->__parse_is_set('user_name', $data['key_value_map']),
			'user_id' =>$this->__parse_is_set('user_id', $data['key_value_map']),
			'mobile' => $this->__parse_is_set('mobile', $data['key_value_map']),
			'email' => $this->__parse_is_set('email', $data['key_value_map']),
			'ip' => $this->__parse_is_set('ip', $data['key_value_map']),
			'platform' => $this->__parse_is_set('platform',$this->__parse_is_set('event', $this->__parse_is_set('knowledge', $result, false),false)),
			'user_agent' => $this->__parse_is_set('user_agent', $data['key_value_map']),
			'cert_no' => $this->__parse_is_set('cert_no', $data['key_value_map']),
			'address_id' =>$this->address_id,
			'create_time' =>time(),
		];

		//保存记录
		$yidun_server = \hd_load::getInstance()->service('yidun/yidun');
		$this->yidun_id = $yidun_server->create($yidun_date);
		//蚁盾接口返回值有无问题，目前按照所有都是无风险处理
		$this->flag = true;
		return true;

//		//-+--------------------------------------------------------------------
//		//-| 验证蚁盾请求结果
//		//-+--------------------------------------------------------------------
//		//验证返回值的是否获取成功
//		if( $result && isset($result['code']) && $result['code'] == 'OK' ) {
//			//判断风险类型
//			if( $this->__parse_is_set('decision',$result) == self::RISK_ACCEPT ) {
//				$this->flag = true;//无风险
//			}elseif( $this->__parse_is_set('decision',$result) == self::RISK_REJECT ){
//				$this->flag = false;//有风险
//			}elseif( $this->__parse_is_set('decision',$result) == self::RISK_VALIDATE ){
//				//根据业务模型处理风险
//				$this->__deal_risk($result);
//			}else{
//				set_error('用户的风险类型不符合设置规则');
//				$this->flag = false;//未查询到结果或者风险类型不符合规则则默认用户存在风险
//			}
//		}else{
//			set_error('用户的风险验证信息获取失败');
//			$this->flag = false;//未查询到结果或者风险类型不符合规则则默认用户存在风险
//		}
	}
//	/**
//	 * 根据业务规则处理风险
//	 * @param type $result
//	 */
//	private function __deal_risk($result){
//		$setting_service = \hd_load::getInstance()->service('admin/setting');
//		$setting_info = $setting_service->get();
//		$setting_yidun_score = $this->__parse_is_set('yidun_score', $setting_info);
//		$setting_yidun_score = $setting_yidun_score ? $setting_yidun_score : 80;
//		//判断返回的分值
//		if( intval($this->score) >= intval($setting_yidun_score) ) {
//			set_error('用户的风险分数高于'.$setting_yidun_score .'分');
//			$this->flag = false;//有风险
//		}else{
//			$this->flag = true;//无风险
//		}
//	}
	/**
	 * 蚁盾验证用户风险接口的传入参数校验
	 * @param array $data
	 * $data = [
	 *		"userName" => '',        //string,用户姓名
	 *		"userId" => '',			 //string,用户ID
	 *		"mobile" => '',			 //string,用户手机号
	 *		"email" => '',			 //string,用户邮箱
	 *		"apdIdToken" => '',		 //string,
	 *		"ip" => '',				 //string,用户ip
	 *		"wifiMac" => '',		 //string,mac地址
	 *		"imei" => '',			 //string,用户imei
	 *		"imsi" => '',			 //string,用户imsi
	 *		"latitude" => '',		 //string,纬度
	 *		"longitude" => '',		 //string,经度
	 *		"platform" => '',		 //string,平台，ios,android,windows
	 *		"userAgent" => '',		 //string,userAgent
	 *		"certNo" => '',			 //string,身份证号
	 * ]
	 * @return boolen true校验无误；false参数有误【错误详情用全局函数get_error()获取】
	 */
	private function __parse_data(&$data){
		//初始化最终数据的数组
		$data_arr = [
			'user_name' => $this->__parse_is_set('user_name', $data),
			'user_id' => $this->__parse_is_set('user_id', $data),
			'mobile' => $this->__parse_is_set('mobile', $data),
			'email' => $this->__parse_is_set('email', $data),
			'ip' => $this->__parse_is_set('ip', $data),
			'platform' => $this->__parse_is_set('platform', $data),
			'user_agent' => $this->__parse_is_set('user_agent', $data),
			'cert_no' => $this->__parse_is_set('cert_no', $data),
			'user_reg_time' => $this->__parse_is_set('user_reg_time', $data),
			'receive_name' => $this->__parse_is_set('receive_name', $data),			//收货人
			'receive_mobile' => $this->__parse_is_set('receive_mobile ', $data),	//收货人手机号
			'receive_address' => $this->__parse_is_set('receive_address', $data),	//收货人地址
			'receive_county' => $this->__parse_is_set('receive_county', $data),		//收货人县或区信息
			'receive_city' => $this->__parse_is_set('receive_city', $data),			//收货人城市信息
			'receive_province' => $this->__parse_is_set('receive_province', $data),	//收货人省份信息
			'gmt_occur' => date('Y-m-d H:i:s')
		];
		//验证参数
		if( !$data_arr['user_name'] || !$data_arr['user_id'] || !$data_arr['cert_no'] || !$data_arr['mobile'] || !$data_arr['gmt_occur'] ) {
			set_error('用户参数获取失败');
			return false;
		}
		$data = [
			'event_code' => 'SCENE_LOAN',//场景贷款事件，目前只用此类事件不考虑其它
			'key_value_map' => $data_arr
		];
		return true;
	}
	/**
	 * 验证参数是否存在，不存在返回空，存在则转换为字符串
	 * @param string $key 验证的key值
	 * @param string $value 验证的值
	 * @param boolen $flag 是否转为字符串
	 */
	private function __parse_is_set($key,$value,$flag=true){
		if( isset($value[$key]) ) {
			if( $flag ) {
				return strval($value[$key]);
			}
			return $value[$key];
		}
		return '';
	}
	
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
			$row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
}