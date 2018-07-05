<?php
use zuji\order\OrderStatus;
use zuji\order\PaymentStatus;
use zuji\order\DeliveryStatus;
use zuji\order\RefundStatus;
/**
 * 订单模型
 *
 * @outhor wang jinlin
 */
class order2_table extends table {

    protected $where = array();

    protected $counts = array();

    protected $result = array();

    protected $fields = [
        'business_key',
        'order_id',
        'order_no',
        'goods_id',
        'goods_name',
        'user_id',
        'appid',
        'mobile',
        'certified_platform',
        'credit',
        'realname',
        'cert_no',
        'zujin',
        'chengse',
        'zuqi',
        'zuqi_type',
        'yajin',
        'mianyajin',
        'yiwaixian',
        'buyout_price',
        'amount',
        'discount_amount',
        'all_amount',
        'payment_amount',
        'payment_time',
        'refund_amount',
        'refund_time',
        'create_time',
        'update_time',
        'remark_id',
        'remark',
        'order_status',
        'status',
        'business_key',
        'payment_status',
        'payment_id',
        'delivery_status',
        'delivery_id',
        'return_status',
        'return_id',
        'receive_status',
        'receive_id',
        'evaluation_status',
        'evaluation_id',
        'refund_status',
        'refund_id',
        'service_status',
        'service_id',
        'reason_id',
        'reason_text',
        'admin_id',
        'trade_no', // 租机交易编号
        'address_id',	// 关联的收货地址ID
        'protocol_no', //租机协议编号
        'discount_amount',
        'all_amount',
        'payment_type_id',
        'authorized_yajin',
        'authorized_zujin',
        'similar_status',
    ];
    protected $pk = 'order_id';

    /**
     * 获取一条订单
     * @param array $where	【必须】查询条件    order_id或order_no二选一，都不存在时，返回false
     * [
     *      'order_id' => '',	//【可选】订单ID
     *      'order_no' => '',	//【可选】string；订单编号
     * ]
     * @return mixed	    false：未找到； array：订单基本信息
     * [
     *      'order_id'      =>'',   //【必须】int；订单ID
     *      'order_no'	    =>'',   //【必须】string；订单编号
     *      'goods_id'          =>'',   //【必须】int；sku ID
     *      'goods_name'        =>'',   //【必须】string；sku 名称
     *      'user_id'         =>'',   //【必须】int；用户ID
     *      'certified_platform'         =>'',   //【必须】int；认证平台
     *      'credit'         =>'',   //【必须】int；认证信用分
     *      'realname'         =>'',   //【必须】string；真实姓名
     *      'cert_no'         =>'',   //【必须】string；身份证号
     *      'zujin'           =>'',   //【必须】int；租金（单位：分）
     *      'zuqi'            =>'',   //【必须】int；租期（单位：月）
     *      'yajin'           =>'',   //【必须】int；押金（单位：分）
     *      'mianyajin'       =>'',   //【必须】int；免押金（单位：分）
     *      'yiwaixian'         =>'',   //【必须】int；保险（单位：分）
     *      'amount'          =>'',   //【必须】int；应付金额（单位：分）
     *      'payment_amount'    =>'',   //【必须】int；实付金额（单位：分）
     *      'payment_time'    =>'',   //【必须】int；实付支付时间
     *      'refund_amount'    =>'',   //【必须】int；实际退款金额（单位：分）
     *      'refund_time'    =>'',   //【必须】int；实际退款时间
     *      'order_time'      =>'',     //【必须】int；下单时间
     *      'order_status'          =>'',   //【必须】int；订单状态
     *      'payment_status'  =>'',   //【必须】int；支付状态
     *      'payment_id'    =>'',	      //【必须】int；支付状态
     *      'delivery_status'    =>'',//【必须】int；发货状态
     *      'delivery_id'    =>'',  //【必须】int；发货状态
     *      'return_status'    =>'',//【必须】int；退货状态
     *      'return_id'    =>'',    //【必须】int；退货单ID
     *      'refund_status'  =>'',  //【必须】int；退款状态
     *      'refund_id'  =>'',	    //【必须】int；退款状态
     *      'evaluation_status'   =>'',//【必须】int；检测状态
     *      'evaluation_id' => '',  //【必须】int；检测单ID
     *      'service_status'  =>'', //【必须】int；使用状态
     *      'goods_info'  =>[]',    //【可选】array；订单商品信息
     *      'address_info'  =>[]',    //【可选】array；订单收货人地址信息
     * ]
     */
    public function get_info($where,$lock=false) {
        return $this->field($this->fields)->where($where)->find(['lock'=>$lock]);
    }
    /**
     *  超时 更改订单状态
     */
    public function order_timeout($order_id,$data) {
        
        return $this->where(['order_id'=>$order_id])->save($data);
    }
    /**
     * 获取某用户是否存在有效免押的订单
     */
    public function has_open_order($user_id, $cert_no) {
       $mianyajin = ['GT',0];
       $where = '`order_status`='.OrderStatus::OrderCreated.' AND (`user_id`='.$user_id.' OR `cert_no`="'.$cert_no.'") AND `mianyajin`>0';
       $result =$this->where($where)->count('order_id');
       if($result>0)
           return 'Y';
       else
           return 'N';
    }
    /**
     * 获取订单列表
     *
     * @param array  【可选】查询条件
     * [
     *      'order_id' => '',	//【可选】int；订单ID
     *      'order_no' => '',	//【可选】string；订单编号
     *      'sku_name'=>''           //【可选】string   商品名称
     * ]
     * @return array    数组键名查看 get_info() 方法
     */
    public function get_list($where,$options) {
        // 字段替换
        $where = replace_field( $where,[
            'order_id' => 'order_id',
            'order_no' => 'order_no',
            'sku_name' => 'sku_name',
        ]);
        $where['_logic'] = "AND";
        $table_name = $this->getTableName();
        if($options['size'] =="all"){
            $order_list = $this->field($this->fields)->where($where)->select();
        }else{
            $order_list = $this->field($this->fields)->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();
        }
        if(!is_array($order_list)){
            return [];
        }
        return $order_list;
    }

    /**
     * 获取订单列表导出数据
     *
     * @param array  【可选】查询条件
     * [
     *      'order_id' => '',	//【可选】int；订单ID
     *      'order_no' => '',	//【可选】string；订单编号
     *      'sku_name'=>''           //【可选】string   商品名称
     * ]
     * @return array    数组键名查看 get_info() 方法
     */
    public function get_list_csv($where,$options) {
        // 字段替换
        $where = replace_field( $where,[
            'order_id' => 'order_id',
            'order_no' => 'order_no',
            'sku_name' => 'sku_name',
        ]);
        $where['_logic'] = "AND";
        $where['user_id'] = ['GT','20'];
        $table_name = $this->getTableName();
        if($options['size'] =="all"){
            $order_list = $this->field($this->fields)->where($where)->select();
        }else{
            $order_list = $this->field($this->fields)->where($where)->page($options['page'])->limit($options['size'])->order($options['orderby'])->select();
        }
        if(!is_array($order_list)){
            return [];
        }
        return $order_list;
    }

    // 组合字段
    public function filte_fields($add_field){
        $table_name = $this->getTableName();
        $fields = $this->fields;
        foreach($fields as &$val){
            $val = $table_name . '.' . $val;
        }
        $fields[] = $add_field;
        return $fields;
    }
    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $this->where($where)->count('order_id');
    }
    /**
     * 获取一段日期内的下单总数量
	 * @param type $start_time
	 * @param type $end_time
     * @return int 查询总数
	 */
    public function get_order_pass($start_time,$end_time){
		$fields = 'count(order_id) as N';
		$where_arr['create_time'] = ['BETWEEN',[$start_time,$end_time]];
		$where_arr['user_id'] = ['GT',20];
        $count = $this->field($fields)
            ->where($where_arr)
            ->select();
//        echo( $this->getLastSql());exit;
		if( empty($count) ){
			return 0;
		}
        return $count[0]['N'];
    }
    
    /**
     * 修改收货方式
     */
    public function update_address_id($order_id, $address_id){
        return $this->where(['order_id'=>$order_id])->save(['address_id'=>$address_id]);
    }
    
    /**
     * 修改交易单号
     * 
     */
    public function edit_trade_no($order_id, $trade_no){
        return $this->where(['order_id'=>$order_id])->save(['trade_no'=>$trade_no]);
    }
    
    /**
     * 修改协议号
     * 
     */
    public function edit_protocol($where){
        return $this->update($where);
    }
    
    /**
     * 修改业务类型
     * 
     */
    public function edit_business_key($order_id, $business_key){
        return $this->where(['order_id'=>$order_id])->save(['business_key'=>$business_key]);
    }
    /**
     * 通过ID 获取一条记录
     * @param integer $order_id 订单ID
     * @return array
     */
    public function get_order_findid($order_id) {
        return $this->find($order_id);
    }
	
    /**
     * 查询一段时间的不同渠道的下单量
     *
     * @param array  【可选】查询条件
     * [
     *      'start_time' => '',	//int【可选】查询的开始时间
     *      'end_time' => '',	//int【可选】查询的结束时间
     * ]
     * @return array查看 get_info 定义
     */
    public function get_list_by_group_appid($where) {
		$fields = 'count(order_id) as N,appid';
		//拼接where条件
		$where_arr = array();
		if( isset($where['start_time']) && isset($where['end_time']) ){
			$where_arr['create_time'] = ['BETWEEN',[$where['start_time'],$where['end_time']]];
		}
		elseif( isset($where['start_time']) ) {
			$where_arr['create_time'] = array('GT',$where['start_time']);
		}
		elseif( isset($where['end_time']) ) {
			$where_arr['create_time'] = array('LT',$where['end_time']);
		}
        $list = $this->field($fields)
            ->where($where_arr)
			->group('appid')
            ->select();
//        echo( $this->getLastSql());exit;
		if( empty($list) ){
			return array();
		}
        return $list;
    }


    /**
     * 插入订单
     * @param   array   订单数据
     * [
     *      '' => ''，
     *      '' => ''，
     *      '' => ''，
     *      '' => ''，
     *      '' => ''，
     *      '' => ''，
     *      '' => ''，
     *      '' => ''，
     * ]
     * @return boolean
     * @author  liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function create($data){
        $order_id =  $this->add($data);
        return $order_id;
    }

    /**
     * @param int   $order_id   订单ID
     * @param int   $goods_id   订单的商品ID
     * @param string   $goods_name   订单的商品名称
     * @return bool|mixed
     */
    public function update_goods_id_and_name($order_id,$goods_id,$goods_name){

        return $this->where(['order_id' => $order_id])->save([
            'goods_id' => $goods_id,
            'goods_name' => $goods_name,
        ]);
    }


    /**
     *
     */

    /**
     * 取消订单
     * @param int   $order_id 【必须】订单ID
     * @param array $data     【必须】取消保存数据
     * [
     *      'order_status' => '',       【必须】int；订单状态
     *      'reason_id' => '',          【必须】int；取消原因ID
     *      'reason_text' => '',        【必须】int；附加原因描述
     * ]
     * @return boolean
     */
    public function cancel_order($order_id, $data){
        $data = [
//            'admin_id'=>  intval($data['admin_id']),
            'order_status'=>  $data['order_status'],//订单取消状态值
            'reason_id'  => $data['reason_id'],//取消订单原因id
            'reason_text' => $data['reason_text'],//取消订单备注
            'update_time'=> time(),//更新时间
        ];
        $b = $this->where(['order_id' => $order_id])->save($data);
	    return $b;
    }


    /**
     * 状态同步 -- 订单取消
     * @param int   $order_id   订单ID
     * @param array $data 
     * [
     *      'order_status' => '',        【必须】int；订单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function close_order( $order_id ,$data){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    
    /**
     * 状态同步 -- 除支付成功的其他状态
     * @params int  $order_id	订单ID
     * @param array $data 订单更改支付状态时必要数据【支付状态修改除成功状态】
     * [
     *      'payment_status' => '',     【必须】int；支付单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_payment_except_success( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 支付成功
     * @params int  $order_id	订单ID
     * @param array $data 订单更改支付状态时必要数据【支付成功状态】
     * [
     *      'payment_status' => '',      【必须】int；阶段
     *      'payment_time' => '',         【必须】int；支付时间
     *      'payment_amount' => '',     【必须】int；金额；单位分
     * ]
     * @params int  $payment_time	实际支付时间戳
     * @params int  $payment_amount	实际支付金额（单位：分）
     * @return boolean	true：成功；false：失败
     */
    public function notify_payment_success( $order_id,$data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    
    /**
     * 状态同步 -- 进入发货阶段
     * @params int  $order_id	订单ID
     * @param array $data 订单进入支付阶段必要数据
     * [
     *      'delivery_id' => '',         【必须】int；发货单ID
     *      'delivery_status' => '',     【必须】int；发货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function enter_delivery( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 发货状态更改
     * @params int  $order_id	订单ID
     * @param array $data 订单更改发货状态时必要数据
     * [
     *      'delivery_status' => '',     【必须】int；发货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery( $order_id, $data ){
        //$data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->where(['order_id'=>$order_id])->save($data);
    }

    /**
     * 状态同步 -- 发货状态更改
     * @params int  $order_id	订单ID
     * @param array $data 订单更改发货状态时必要数据
     * [
     *      'delivery_status' => '',     【必须】int；发货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_protocoled( $order_id, $data ){

        //$data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->where(['order_id'=>$order_id])->save($data);
    }
    
    /**
     * 状态同步 -- 进入退货申请阶段
     * @params int  $order_id	订单ID
     * @param array $data 订单进入支付阶段必要数据
     * [
     *      'return_id' => '',         【必须】int；退货单ID
     *      'return_status' => '',     【必须】int；退货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function enter_return( $order_id, $data ){
        $data['update_time'] = time();
        return $this->where(['order_id'=>$order_id])->save($data);
    }
    /**
     * 状态同步 -- 退货状态更改
     * @params int  $order_id	订单ID
     * @param array $data 脱货单单更改退货状态时必要数据
     * [
     *      'return_status' => '',     【必须】int；退货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_return( $order_id, $data ){
        $data['update_time'] = time();
        return $this->where(['order_id'=>$order_id])->save($data);
    }  
    /**
     * 状态同步 -- 服务开启
     * @params int  $order_id	订单ID
     * @param array $data 订单进入支付阶段必要数据
     * [
     *      'service_id' => '',         【必须】int；服务单ID
     *      'service_status' => '',     【必须】int；服务单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function enter_service( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 服务状态更改
     * @params int  $order_id	订单ID
     * @param array $data 
     * [
     *      'service_status' => '',     【必须】int；服务单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_service( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 服务状态更改【回寄拒签使用】
     * @params int  $order_id	订单ID
     * @param array $data 
     * [
     *      'service_status' => '',     【必须】int；服务单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_return_refuse( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    
    /**
     * 状态同步 -- 进入收货阶段
     * @params int  $order_id	订单ID
     * @param array $data 订单进入支付阶段必要数据
     * [
     *      'receive_id' => '',         【必须】int；收货单ID
     *      'receive_status' => '',     【必须】int；收货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function enter_receive( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 收货单待收货
     * @params int  $order_id	订单ID
     * @param array $data 脱货单单更改退货状态时必要数据
     * [
     *      'receive_status' => '',     【必须】int；退货单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_receive( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    
    
    /**
     * 状态同步 -- 进入检测阶段
     * @params int  $order_id	订单ID
     * @param array $data 订单进入检测阶段必要数据
     * [
     *      'evaluation_id' => '',         【必须】int；检测单单ID
     *      'evaluation_status' => '',     【必须】int；检测单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function enter_evaluation( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 检测中
     * @params int  $order_id	订单ID
     * @param array $data 订单进入检测阶段必要数据
     * [
     *      'evaluation_status' => '',     【必须】int；检测单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_evaluation_except_finished( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 检测完成
     * @params int  $order_id	订单ID
     * @param array $data 订单进入检测阶段必要数据
     * [
     *      'evaluation_status' => '',     【必须】int；检测单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_evaluation_finished( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['evaluation_time'] = time();
        $data['update_time'] = time();
        return $this->save($data);
    }
    
    /**
     * 状态同步 -- 进入退款阶段
     * @params int  $order_id	订单ID
     * @param array $data 订单进入检测阶段必要数据
     * [
     *      'refund_id' => '',         【必须】int；退款单单ID
     *      'refund_status' => '',     【必须】int；退款单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function enter_refund( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 退款中
     * @params int  $order_id	订单ID
     * @param array $data 订单进入退款阶段必要数据
     * [
     *      'refund_status' => '',     【必须】int；退款单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_refund_waiting( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 待退款
     * @params int  $order_id	订单ID
     * @param array $data 订单进入退款阶段必要数据
     * [
     *      'refund_status' => '',     【必须】int；退款单状态
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_refund_expect_success( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }
    /**
     * 状态同步 -- 退款成功
     * @params int  $order_id	订单ID
     * @param array $data 订单进入退款阶段必要数据
     * [
     *      'refund_status' => '',       【必须】int；退款单状态
     *      'refund_time' => '',         【必须】int；退款时间
     *      'refund_amount' => '',     【必须】int；退款金额
     * ]
     * @return boolean	true：成功；false：失败
     */
    public function notify_refund_success( $order_id, $data ){
        $data['order_id'] = $order_id;
        $data['update_time'] = time();
        return $this->save($data);
    }

    // ------------------------------  统计条数 (连贯操作) -------------------------------
    public function buyer_id($buyer_id) {
        if(!empty($buyer_id) && is_numeric($buyer_id)) {
            $this->where['buyer_id'] = $buyer_id;
        }
        return $this;
    }

    /* 所有订单 */
    public function all() {
        $this->counts['all'] = $this->where($this->where)->count();
        return $this;
    }

    /* 已取消 */
    public function cancel() {
        $map = $sqlmap = array();
        $sqlmap['order_status'] = OrderStatus::OrderCanceled;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['cancel'] = $this->where($map)->count();
        return $this;
    }

    /* 已回收 */
    public function recycle() {
        $map = $sqlmap = array();
        $sqlmap['status'] = 3;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['recycle'] = $this->where($map)->count();
        return $this;
    }

    /* (会员)已删除 */
    public function deletes() {
        $map = $sqlmap = array();
        $sqlmap['status'] = 4;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['deletes'] = $this->where($map)->count();
        return $this;
    }

    /* 待支付 */
    public function pay() {
        $map = $sqlmap = array();
        $sqlmap['order_status']   = OrderStatus::OrderCreated;
        $sqlmap['_string'] = '(`payment_status` = '.PaymentStatus::PaymentWaiting.') or (`payment_status` = '.PaymentStatus::PaymentPaying.')';
        $map = array_merge($sqlmap,$this->where);
        $this->counts['pay'] = $this->where($map)->count();
        return $this;
    }

    /* 支付成功 */
    public function confirm() {
        $map = $sqlmap = array();
        $sqlmap['payment_status'] = PaymentStatus::PaymentSuccessful;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['confirm'] = $this->load->table('order2/order2_payment')->get_count($map);
        return $this;
    }

    /* 待发货 */
    public function delivery() {
        $map = $sqlmap = array();
        $sqlmap['delivery_status'] = DeliveryStatus::DeliveryWaiting;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['delivery'] = $this->load->table('order2/order2_delivery')->get_count($map);
        return $this;
    }

    /* 待收货 */
    public function receipt() {
        $map = $sqlmap = array();
        $sqlmap['receive_status'] = \zuji\order\ReceiveStatus::ReceiveWaiting;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['receipt'] = $this->load->table('order2/order2_receive')->get_count($map);
        return $this;
    }

    /* 已完成 */
    public function finish() {
        $map = $sqlmap = array();
        $sqlmap['order_status'] = OrderStatus::OrderFinished;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['finish'] = $this->where($map)->count();
        return $this;
    }

    /* 进行中的订单 */
    public function going() {
        $map = $sqlmap = array();
        $sqlmap['order_status'] = OrderStatus::OrderCreated;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['going'] = $this->where($map)->count();
        return $this;
    }

    /* 待退货商品 */
    public function load_return() {
        $map = $sqlmap = array();
        $sqlmap['receive_status'] = \zuji\order\ReceiveStatus::ReceiveWaiting;
        $map = array_merge($sqlmap,$this->where);
        $this->counts['load_return'] = $this->where($map)->count();
        return $this;
    }

    /* 待退款订单 */
    public function load_refund() {
        $map = $sqlmap = array();
        $sqlmap['_string'] = '(`refund_status` = '.RefundStatus::RefundWaiting.') or (`refund_status` = '.RefundStatus::RefundPaying.')';
        $map = array_merge($sqlmap,$this->where);
        $this->counts['load_refund'] = $this->where($map)->count();
        return $this;
    }

    /**
     * 输出统计结果
     * @param  string $fun_name 要统计的方法名，默认统计所有结果
     * @return [result]
     */
    public function out_counts($fun_name = '') {
        if (empty($fun_name)) {
            $this->all()->cancel()->recycle()->deletes()->pay()->confirm()->delivery()->receipt()->finish()->going()->load_return()->load_refund();
        } else {
            $this->$fun_name();
        }
        return $this->counts;
    }
}