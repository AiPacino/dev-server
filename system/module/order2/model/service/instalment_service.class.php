<?php
/**
 * 支付宝代扣协议 服务层
 */
use zuji\Config;
use zuji\payment\Instalment;
use zuji\coupon\CouponStatus;
use zuji\payment\Withhold;


class instalment_service extends service {

    public function _initialize() {
        $this->instalment_table = $this->load->table('order2/order2_instalment');
        $this->order_service    = $this->load->service('order2/order');
        $this->coupon_service   = $this->load->service('order2/coupon');
        $this->member_table   = $this->load->table('member/member');
        $this->withhold_service = $this->load->service('payment/withhold');
        $this->fund_auth_record_table = $this->load->table('payment/payment_fund_auth_record');
    }


    private function _parse_where( $param=[] ){
        // 参数过滤
        $param = filter_array($param, [
            'order_id' => 'required',
            'mobile' => 'required',
            'status' => 'required',
            'trade_no' => 'required',
            'oun_trade_no' => 'required',
            'begin_time' => 'required',
        ]);


        // 开始时间（可选）
        if( isset($param['begin_time'])){

            $where['term'] = intval(date("Ym",strtotime($param['begin_time'])));
        }

        if( isset($param['status']) ){
            $where['status'] = intval($param['status']);
        }

        if( isset($param['order_id']) ){
            $where['order_id'] = $param['order_id'];
        }

        if( isset($param['mobile']) ){
            $where['mobile'] = $param['mobile'];
        }

        if( isset($param['trade_no']) ){
            $where['trade_no'] = $param['trade_no'];
        }

        if( isset($param['oun_trade_no']) ){
            $where['oun_trade_no'] = $param['oun_trade_no'];
        }
        return $where;
    }


	/**
     * 创建扣款分期
     * @param  array
     * [
     *      'order_id' => $order_id 订单id
     * ]
	 */
	public function create_instalment($data){
		
        //过滤参数
        $param = filter_array($data,[
            "order_id"  => "required"
        ]);
        if(count($param) < 1){
            set_error('参数错误');
            return false;
        }
        $order_id = $param['order_id'];
        $order_info = $this->order_service->get_order_info(['order_id'=>$order_id]);
        if(!$order_info){
            return false;
        }
        // 租期
        $zuqi   = $order_info['zuqi'];
        if($zuqi <= 0){
            set_error('租期查询失败');
            return false;
        }

        $member = $this->member_table->find($order_info['user_id']);
        //认证平台为小程序，不需要签代扣协议
        if($member['certified_platform'] == 2){
            $agreement_no = '';
        }else{
            if(!$member['withholding_no']){
                set_error('用户代扣协议码不存在');
                return false;
            }
            $agreement_no = $member['withholding_no'];
        }


		// 订单原始金额
        $all_amount = intval($order_info['all_amount']*100);
		// 实际订单金额
        $amount = intval($order_info['amount']*100);
		
        // 租期数组
        $terms  = get_terms($zuqi);
		// 月租金
        $zujin = intval($order_info['zujin']*100);
		// 优惠金额
        $discount_amount = intval($order_info['discount_amount']*100);
		// 碎屏险金额
        $yiwaixian = intval($order_info['yiwaixian']*100);
		
        // 优惠券信息
        $coupon = $this->coupon_service->get_info($order_id);

		// 每月租金优惠金额
		$m_discount_amount = 0;
		
		// 碎屏险优惠金额
		$yiwaixian_discount_amount = 0;
		
		// 意外险
		$reset_yiwaixian = $yiwaixian;
		
		$reset_zujin = $zujin;
		
		// 订单金额 小于等于 碎屏险金额 （大额优惠券处理）
		if( $amount <= $yiwaixian ){
			// 重置 意外险金额
			$reset_yiwaixian = $amount;
			
			// 重置 每月租金优惠金额
			$m_discount_amount = $zujin;
			
			// 重置 碎屏险优惠金额 = 碎屏险 - 实际订单金额
			$yiwaixian_discount_amount = $yiwaixian - $amount;
			
			// 重置 月租金额
			$reset_zujin = 0;
			
			// 重置 总优惠金额（目的：不走后面的优惠处理）
			$discount_amount = 0;
		}
		
		// 
		$instalment_data = [];
        // 默认分期
        for($i = 1; $i <= $zuqi; $i++){
            $_data['agreement_no']    = $agreement_no;    //代扣协议号
            $_data['order_id']        = $order_id;        //订单ID
            $_data['term']            = $terms[$i];       //期(yyyymm)
            $_data['times']           = $i;               //第几期
            $_data['amount']          = $reset_zujin;          //应付金额（分）
			// $reset_zujin=0，则为支付成功状态
            $_data['status']          = $reset_zujin>0?Instalment::UNPAID:Instalment::SUCCESS; //状态
            $_data['discount_amount'] = $m_discount_amount;    //优惠金额
			$instalment_data[$i] = $_data;
        }
		
		// 如果有优惠
		if( $discount_amount>0 ){
			
			// 首页0租金
			if( $coupon && $coupon['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree ){
				// 优惠 首月租金
				$instalment_data[1]['discount_amount'] = $instalment_data[1]['amount'];
				$instalment_data[1]['amount'] = 0;
			}else{
				// 普通优惠
				
				// 未优惠租金总金额 = 总金额 - 碎屏险 - 已优惠金额
				$_amount = $all_amount - $reset_yiwaixian - $discount_amount;
				// 先取余
				$m = $_amount % $zuqi;
				// 
				$avg = intval(($_amount-$m)/$zuqi);
				
				foreach( $instalment_data as &$it ){
					// 分期优惠金额
					$it['discount_amount'] =  $it['amount']-$avg;
					// 分期
					$it['amount'] =  $avg;
				}
				// 首页扣款 加 余数
				$instalment_data[1]['amount'] += $m;
			}
		}
		
		// 首页扣款金额 添加 碎屏险
		$instalment_data[1]['amount'] += $reset_yiwaixian;
		if( $instalment_data[1]['amount'] > 0 ){
			$instalment_data[1]['discount_amount'] += $yiwaixian_discount_amount;
			$instalment_data[1]['status'] = Instalment::UNPAID;
		}
		//zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '创建分期数据', $instalment_data);
		return $instalment_data;
	}
	

    /**
     * 创建扣款分期
     * @param  array
     * [
     *      'order_id' => $order_id 订单id
     * ]
     * @return bool : true false
     */
    public function create($data){
		$instalment_data = $this->create_instalment( $data );
		if( $instalment_data === false ){
			return false;
		}
		foreach( $instalment_data as $it ){
			$it['unfreeze_status'] = 2;// 扣款成功时，不需要对预授权资金进行解冻
			$b = $this->instalment_table->create($it);
		}
		return true;
    }


    /**
     *  取消分期
     * @param   int  $order_id
     * @return  mixed  false:失败；int：影响记录数
     */
    public function cancel_instalment($order_id){
        $instalment_info = $this->get_info(['order_id'=>$order_id]);
        if(!$instalment_info){
            return true;
        }

        // 取消分期
		$where = [
			'order_id' => $order_id,		// 订单ID
			'status' => Instalment::UNPAID,	// 未支付状态
		];
		$data = [
			'status' => Instalment::CANCEL,	// 已取消
			'update_time' => time(),
		];
        $result = $this->instalment_table->where($where)->save($data);
		return $result;
    }

    /**
     * 获取符合条件的记录数
     * @param   array	$where
     * @return int 查询总数
     */
    public function get_count($where=[]){
        // 参数过滤
        $where = $this->_parse_where($where);
        if( $where===false ){
            return 0;
        }
        return $this->instalment_table->get_count($where);

    }


    /**
     * 是否允许扣款
     * @param  int  $instalment_id 订单分期付款id
     * @return bool true false
     */
    public function allow_withhold($instalment_id){
        $alllow = 0;
        $instalment_info = $this->get_info(['id'=>$instalment_id]);
        $status = $instalment_info['status'];

        $year   = date("Y");
        $month  = intval(date("m"));
        if($month < 10 ){
            $month = "0".$month;
        }
        $term 	= $year.$month;
        $day 	= intval(date("d"));

        // 是否有扣款记录
        $fund_auth_record = $this->fund_auth_record_table->where(['instalment_id'=>$instalment_id,'status'=>1])->find();
        //查询订单记录
        $order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']]);

        if(!$fund_auth_record && ($status == Instalment::UNPAID || $status == Instalment::FAIL)){
            // 本月15后以后 可扣当月 之前没有扣款的可扣款
            if(($term == $instalment_info['term'] && $day >= 15) || $term > $instalment_info['term']){
                //判断订单状态 必须是租用中 或者完成关闭的状态 才允许扣款
                if($order_info['status']== \oms\state\State::OrderInService || $order_info['status'] == oms\state\State::OrderClosed){
                    $alllow = 1;
                }
            }
        }


        return $alllow;
    }
    /**
     * 查询未支付和支付失败的订单分期
     * @param   int	$order_id
     * @return int 查询总数
     */
    public function get_order_instalment_unpaid($order_id){
        // 参数过滤
        if($order_id < 0){
            return [];
        }
        $where['order_id'] = $order_id;
        $where['status'] =['IN',[Instalment::UNPAID,Instalment::FAIL]];
        $where['term'] =['ELT',date("Ym")];
        return $this->instalment_table->get_order_list($where);
    }
    /**
     * 获取订单分期扣款信息
     * @param   int	$order_id
     * @return int 查询总数
     */
    public function get_order_instalment($order_id){
        // 参数过滤
        if($order_id < 0){
            return [];
        }
        $where['order_id'] = $order_id;
        return $this->instalment_table->get_order_list($where);
    }

    /**
     * 获取符合条件的列表
     * @param   array	$where
     * @return array
     */
    public function get_list($where=[],$additional=[]){

        // 参数过滤
        $where = $this->_parse_where($where);
        if( $where===false ){
            return [];
        }

        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required|is_int',
            'orderby' => 'required',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = Config::Page_Size;
        }
        $additional['size'] = max( $additional['size'], Config::Page_Size );

        if( !isset($additional['orderby']) ){   // 排序默认值
            $additional['orderby']='time_ASC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'times DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'times ASC';
            }
        }

        // 列表查询
        return $this->instalment_table->get_list($where,$additional);
    }

    /**
     * 
     * @param   array	$where
     * @return int 查询详情
     */
    public function get_info($where=[],$additional=[]){
        return $this->instalment_table->get_info($where,boolval($additional['lock']));
    }
	
	/**
	 * 更新分期扣款的租机交易码
	 * @param int $id	主键ID
	 * @param string $trade_no	交易码
	 * @return mixed  false：更新失败；int：受影响记录数
	 */
	public function set_trade_no(int $id,string $trade_no){
		return $this->instalment_table->where([
			'id' => $id
		])->limit(1)->save(['trade_no'=>$trade_no]);
	}

    /**
     * @param   array	$where
     * @return int 查询总数
     */
    public function save($where, $data = []){
        return $this->instalment_table->where($where)->save($data);
    }
}
