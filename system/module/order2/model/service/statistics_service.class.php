<?php
/**
 * 		订单统计服务层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class statistics_service extends service {

	protected $where = array();
	protected $result = array();
	protected $payments_ch = array();

	public function _initialize() {
		$this->db_order = $this->load->table('order2/order2');
		$this->order2_address = $this->load->table('order2/order2_address');
		// $this->db_order_sub = $this->load->table('order/order_sub');
		// $this->db_order_sku = $this->load->table('order/order_sku');
		// $this->db_order_return = $this->load->table('order/order_return');
		// $this->db_order_refund = $this->load->table('order/order_refund');
		// $this->db_district = $this->load->table('admin/district');
        $this->order2_payment_table = $this->load->table('order2/order2_payment');
        $this->order2_refund_table = $this->load->table('order2/order2_refund');
		$this->payments_ch = array(	'alipay' => '支付宝');
	}

	/**
	 * 组装搜索条件
	 * @param  array  $params
	 *         				$params[user_id] : 会员主键id (int)
	 *         				$params[days] : 最近{多少}天 (int)
	 *         				$params[start_time] : 开始时间 (int, 时间戳)
	 *         				$params[end_time] : 结束时间 (int, 时间戳)
	 * @return [obj]
	 */
	public function build_sqlmap($params = array()) {
		if(isset($params['user_id']) && is_numeric($params['user_id'])) {
            $this->where['user_id'] = $params['user_id'];
        }
        if (isset($params['days']) && $params['days'] > 0) {
        	$days = $params['days'];
        	$days -= 1;
        	$this->where['search']['time'] = array('BETWEEN',array(strtotime("-{$params['days']}day",strtotime(date('Y-m-d 00:00:00'))) ,time()));
        	$this->where['search']['days'] = $params['days'];
        } else if (isset($params['start_time']) && isset($params['end_time'])) {
	        $this->where['search']['time'] = array('BETWEEN',array($params['start_time'] ,$params['end_time']));
			//两个时间戳之间的天数
	        $this->where['search']['days'] = round(($params['end_time'] - $params['start_time'])/86400);
        }
        return $this;
	}

	/**
	 * 销售统计 
	 * @return [result] (本日、本周、本月、本年[、日期搜索]):订单数,订单销售额,人均客单价,已取消订单
	 */
	public function sales() {
		// 按日期搜索
        $table = $this->order2_payment_table->getTableName();
        $order_table=$this->db_order->getTableName();
        
		if ($this->where['search']) {
			$days = $this->where['search']['days'];
			$search = $this->where['search']['time'];
			unset($this->where['search']);
			$sqlmap[$table.'.create_time'] = $search;
			$sqlmap[$table.'.payment_status'] = \zuji\order\PaymentStatus::PaymentSuccessful;
			$field = "FROM_UNIXTIME({$table}.create_time,'%Y-%m-%d') days,SUM({$table}.amount) amount,count(distinct O.user_id) as peoples";
			$sqlquery = $this->order2_payment_table->join(config("DB_PREFIX").'order2 AS O ON '.$table.'.order_id=O.order_id')->where($sqlmap)->field($field)->group('days')->buildSql();
			$_searchs = $this->order2_payment_table->query($sqlquery);

			$order_sqlmap[$order_table.'.create_time'] = $search;
			$order_field = "FROM_UNIXTIME({$order_table}.create_time,'%Y-%m-%d') days,count({$order_table}.order_id) orders";
			$order_searchs =$this->db_order->where($order_sqlmap)->field($order_field)->group('days')->select();
			$order_sqlmap[$order_table.'.payment_status'] = \zuji\order\PaymentStatus::PaymentSuccessful;
			
			$order_field = "FROM_UNIXTIME({$order_table}.create_time,'%Y-%m-%d') days,count({$order_table}.order_id) orders";
			$payment_searchs =$this->db_order->where($order_sqlmap)->field($order_field)->group('days')->select();
			
			foreach ($_searchs as $k => $val) {
				$val['average'] = sprintf("%.2f",$val['amount']/$val['peoples']);
				$_searchs[$val['days']] = $val;
				unset($_searchs[$k]);
			}
			foreach ($order_searchs as $k => $val) {
			    $order_searchs[$val['days']] = $val['orders'];
			    unset($order_searchs[$k]);
			}
			foreach ($payment_searchs as $k => $val) {
			    $payment_searchs[$val['days']] = $val['orders'];
			    unset($payment_searchs[$k]);
			}
			for ($i = 0; $i <= $days; $i++) {
				$today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
				$this->result['search']['dates'][$i] = $today;
				$_amounts[] = isset($_searchs[$today]['amount']) ? sprintf("%.2f", $_searchs[$today]['amount']/100) : '0.00';
				$_averages[] = isset($_searchs[$today]['average']) ? sprintf("%.2f", $_searchs[$today]['average']/100) : '0.00';
			    
			
			}
			$this->result['search']['series']['amounts']  = $_amounts;
			$this->result['search']['series']['averages'] = $_averages;

			$where['create_time'] = $search;
            $where['refund_status'] = \zuji\order\RefundStatus::RefundSuccessful;
            $field = "FROM_UNIXTIME(create_time,'%Y-%m-%d') days,SUM(refund_amount) amount";
            $sqlquery = $this->order2_refund_table->where($where)->field($field)->group('days')->buildSql();
            $_searchs = $this->order2_refund_table->query($sqlquery);
            foreach ($_searchs as $k => $val) {
                $_searchs[$val['days']] = $val;
                unset($_searchs[$k]);
            }
            for ($i = 0; $i <= $days; $i++) {
                $today = date('Y-m-d',strtotime("+{$i}day",$search[1][0]));
                $_refund_amounts[] = isset($_searchs[$today]['amount']) ? sprintf("%.2f", $_searchs[$today]['amount']/100) : '0.00';
            }
            $this->result['search']['series']['refund_amounts'] = $_refund_amounts;

            $field = "FROM_UNIXTIME(create_time,'%Y-%m-%d') days,count(order_id) as orders";
            $sqlquery = $this->db_order->where(['create_time' => $search])->field($field)->group('days')->buildSql();
            $_searchs = $this->db_order->query($sqlquery);
            foreach ($_searchs as $k => $val) {
                $_searchs[$val['days']] = $val;
                unset($_searchs[$k]);
            }
            for ($i = 0; $i <= $days; $i++) {
                $today = date('Y-m-d',strtotime("+{$i}day",$search[1][0]));
                $_orders[] = isset($_searchs[$today]['orders']) ? $_searchs[$today]['orders'] : '0';
            }
            $this->result['search']['series']['orders']   = $_orders;

		}

		// 本日查询条件
		$start = strtotime(date('Y-m-d 00:00:00'));
		$end   = strtotime(date('Y-m-d 23:59:59'));
		$today = array('BETWEEN',array($start, $end));
		// 本周查询条件
		$start = mktime(0, 0, 0,date("m"),date("d")-date("w")+1,date("Y"));
		$end   = mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));
		$week  = array('BETWEEN',array($start ,$end));
		// 本月查询条件
		$start = strtotime(date('Y-m-01 00:00:00'));
		$end   = strtotime(date('Y-m-d H:i:s'));
		$month = array('BETWEEN',array($start ,$end));
		// 本年查询条件
		$start = strtotime(date('Y-01-01 00:00:00'));
		$end   = strtotime(date('Y-m-d H:i:s'));
		$year  = array('BETWEEN',array($start ,$end));

		/* 订单数 */
		$sqlmap = $this->where;
		$sqlmap['create_time'] = $today;
		$this->result['today']['orders'] = (int) $this->db_order->where($sqlmap)->count();

		$sqlmap['create_time'] = $week;
		$this->result['week']['orders'] = (int) $this->db_order->where($sqlmap)->count();

		$sqlmap['create_time'] = $month;
		$this->result['month']['orders'] = (int) $this->db_order->where($sqlmap)->count();

		$sqlmap['create_time'] = $year;
		$this->result['year']['orders'] = (int) $this->db_order->where($sqlmap)->count();

		/* 订单销售额 */ 
		$sqlmap = $this->where;
		$sqlmap['create_time'] = $today;
        $sqlmap['payment_status'] = \zuji\order\PaymentStatus::PaymentSuccessful;
		$this->result['today']['amount'] = sprintf("%.2f",$this->order2_payment_table->where($sqlmap)->sum("amount")/100);

		$sqlmap['create_time'] = $week;
		$this->result['week']['amount'] = sprintf("%.2f",$this->order2_payment_table->where($sqlmap)->sum("amount")/100);

		$sqlmap['create_time'] = $month;
		$this->result['month']['amount'] = sprintf("%.2f",$this->order2_payment_table->where($sqlmap)->sum("amount")/100);

		$sqlmap['create_time'] = $year;
		$this->result['year']['amount'] = sprintf("%.2f",$this->order2_payment_table->where($sqlmap)->sum("amount")/100);

		/* 人均客单价 */
		$sqlmap = $this->where;
		$sqlmap[$table.'.create_time'] = $today;
        $sqlmap[$table.'.payment_status'] = \zuji\order\PaymentStatus::PaymentSuccessful;
		$peoples = (int) $this->order2_payment_table->join(config("DB_PREFIX").'order2 AS O ON '.$table.'.order_id=O.order_id')->where($sqlmap)->count('distinct O.user_id');
		$this->result['today']['average'] = sprintf("%.2f",$this->result['today']['amount']/($peoples));

		$sqlmap['create_time'] = $week;
		$peoples = (int) $this->order2_payment_table->where($sqlmap)->count('distinct user_id');
		$data = $this->db_order->where($sqlmap)->select();
		$this->result['week']['average'] = sprintf("%.2f",$this->result['week']['amount']/($peoples));

		$sqlmap['create_time'] = $month;
		$peoples = $this->order2_payment_table->where($sqlmap)->count('distinct user_id');
		$this->result['month']['average'] = sprintf("%.2f",$this->result['month']['amount']/($peoples));

		$sqlmap['create_time'] = $year;
		$peoples = (int) $this->order2_payment_table->where($sqlmap)->count('distinct user_id');
		$this->result['year']['average'] = sprintf("%.2f",$this->result['year']['amount']/($peoples));

		/* 已取消订单 */
		$sqlmap = $this->where;
		$sqlmap['order_status'] = \zuji\order\OrderStatus::OrderCanceled;
		$sqlmap['create_time'] = $today;
		$this->result['today']['cancels'] = (int) $this->db_order->where($sqlmap)->count();

		$sqlmap['create_time'] = $week;
		$this->result['week']['cancels'] = (int) $this->db_order->where($sqlmap)->count();

		$sqlmap['create_time'] = $month;
		$this->result['month']['cancels'] = (int) $this->db_order->where($sqlmap)->count();

		$sqlmap['create_time'] = $year;
		$this->result['year']['cancels'] = (int) $this->db_order->where($sqlmap)->count();

        /* 退款金额 */
        $sqlmap = $this->where;
        $sqlmap['refund_status'] = \zuji\order\RefundStatus::RefundSuccessful;
        $sqlmap['create_time'] = $today;
        $this->result['today']['refund_amount'] = sprintf("%.2f",$this->order2_refund_table->where($sqlmap)->sum("refund_amount")/100);

        $sqlmap['create_time'] = $week;
        $this->result['week']['refund_amount'] = sprintf("%.2f",$this->order2_refund_table->where($sqlmap)->sum("refund_amount")/100);

        $sqlmap['create_time'] = $month;
        $this->result['month']['refund_amount'] = sprintf("%.2f",$this->order2_refund_table->where($sqlmap)->sum("refund_amount")/100);

        $sqlmap['create_time'] = $year;
        $this->result['year']['refund_amount'] = sprintf("%.2f",$this->order2_refund_table->where($sqlmap)->sum("refund_amount")/100);
       
        //查询新增用户数
        $users = $this->load->service('member2/member')->get_list(['register_time'=>$today]);
        $this->result['today']['user'] =count($users);
        //查询下单量
        $orders =$this->load->service('order2/order')->get_order_list(['create_time'=>$today]);
        $this->result['today']['order']=count($orders);
        //登陆用户数
        
        $login_users = $this->load->service('member2/member')->get_list(['login_time'=>$week]);
        $this->result['today']['login_user'] =count($login_users);
        //查询成交量
        $orders =$this->load->service('order2/order')->get_order_list(['create_time'=>$today,'payment_status' =>\zuji\order\PaymentStatus::PaymentSuccessful]);
        $this->result['today']['payment']=count($orders);
        //下单率为 ：下单/登陆用户数 成交率：成交量/登陆用户数
        $login =count($login_users)==0?1:count($login_users);
        $this->result['today']['xiadanlv'] =sprintf("%.2f",$this->result['today']['order']/$login*100);
        $this->result['today']['chengjiaolv'] =sprintf("%.2f",$this->result['today']['payment']/$login*100);
        if($this->result['today']['xiadanlv'] >100){
            $this->result['today']['xiadanlv'] ="100.00";
        }
        if($this->result['today']['chengjiaolv'] >100){
            $this->result['today']['chengjiaolv'] ="100.00";
        }  
        //退货量
        $refund = $this->load->service('order2/refund')->get_list(['refund_time' =>$today]);
        $this->result['today']['refund'] =count($refund);
        
        
        return $this;
	}

	/* 地区订单统计 */
	public function districts() {
		// 组装省级地区为一维数组
		$districts = $this->load->service('admin/district')->get_children(0);
		$arr = $areas = array();
		foreach ($districts as $k => $v) {
			if ($v['id'] == 820000) {
				$macao = $v;
				continue;
			}
			$arr[] = $this->load->service('admin/district')->get_children($v['id']);
		}
		foreach ($arr as $val) {
			foreach ($val as $v) {
				$areas[] = $v;
			}
		}
		if ($macao) $areas[] = $macao;
		foreach ($areas as $key => $area) {
			$sqlmap['province_id'] = $area['id'];
			$areas[$key]['value'] = (int) $this->order2_address->distinct()->where($sqlmap)->count();

		}
		$this->result['districts'] = $areas;
		return $this;
	}

	/* 支付方式订单统计 */
	public function payments() {
		$pays_count = array();
		$code = 'alipay';
        $pays_count[0]['code'] = $code;
        $pays_count[0]['name'] = $this->payments_ch[$code];
        $pays_count[0]['value'] = (int) $this->load->table('order2/order2_payment')->where(['payment_status' => \zuji\order\PaymentStatus::PaymentSuccessful])->count();
		$this->result['payments'] = $pays_count;
		return $this;
	}

	/**
     * 输出统计结果
     * @param  string $fun_name 要统计的方法名（多个用 ，分割），默认统计所有结果
     * @return [result]
     */
    public function output($fun_name = '') {
        if (empty($fun_name)) {
            $this->sales()->districts()->payments();
        } else {
        	$fun_names = explode(',', $fun_name);
        	foreach ($fun_names as $name) {
        		if (method_exists($this,$name)) {
        			$this->$name();
        		}
        	}
        }
        return $this->result;
    }
    public function get_data(){
    	$datas = array();
		/* 订单提醒 */
		$datas['orders'] = $this->load->table('order2/order2')->out_counts();
		/* 商品管理 */
		$datas['goods']['goods_in_sales'] = $this->load->service('goods/goods_spu')->count_spu_info(1);
		$datas['goods']['goods_load_online'] = $this->load->service('goods/goods_spu')->count_spu_info(0);
		$datas['goods']['goods_number_warning'] = $this->load->service('goods/goods_spu')->count_spu_info(2);
		/* 待处理咨询 */
		$datas['consult_load_do'] = $this->load->service('goods/goods_consult')->handle();
		/* 资金管理 */
		$datas['sales'] = $this->output('sales');
		/* 注册会员总数 */
		$datas['member_total'] = $this->load->table('member/member')->count();
		/* 数据库大小 */
		$querysql = "select round(sum(DATA_LENGTH/1024/1024)+sum(DATA_LENGTH/1024/1024),2) as db_length from information_schema.tables where table_schema='".config('DB_NAME')."'";
		$datas['dbsize'] = $this->load->table('member/member')->query($querysql);
		return $datas;
    }
    public function today_data(){
        $datas =array();
        // 本日查询条件
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end   = strtotime(date('Y-m-d 23:59:59'));
        $today = array('BETWEEN',array($start, $end));
        // 本周查询条件
        $start = mktime(0, 0, 0,date("m"),date("d")-date("w")+1,date("Y"));
        $end   = mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));
        $week  = array('BETWEEN',array($start ,$end));
        // 本月查询条件
        $start = strtotime(date('Y-m-01 00:00:00'));
        $end   = strtotime(date('Y-m-d H:i:s'));
        $month = array('BETWEEN',array($start ,$end));
        //查询新增用户数
        $users = $this->load->service('member2/member')->get_list(['register_time'=>$today]);
        $datas['today']['user'] =count($users);
        //查询下单量
        $orders =$this->load->service('order2/order')->get_order_list(['create_time'=>$today]);
        $datas['today']['order']=count($orders);
        return $datas;
    }
    
	public function get_list_by_group_appid($where) {
		return $this->db_order->get_list_by_group_appid($where);
	}

}