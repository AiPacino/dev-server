<?php
use zuji\order\EvaluationStatus;
/**
 * 检测单生成服务
 * @access public
 * @author yaodongxu <yaodongxu@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 *
 */
class evaluation_service extends service {
    public function _initialize() {
        //实例化数据层
        $this->evaluation_table = $this->load->table('order2/order2_evaluation');
        $this->order_service = $this->load->service('order2/order');
    }
    
    /**
     * 生成检测单
     * @param array $data  生成检测单必要信息                       【必选】 
     * array(
     *	    'order_id' => '',//int;订单id                            【必须】
     *	    'order_no' => '',//int;订单编号                          【必须】
     *      'business_key' => '', //zuji\Business::BUSINESS_前缀常量 【必须】
     *	    'goods_id' => '',//int;商品id                            【必须】
     * )
     * @return mixed	false：失败；int：支付单ID
     * 当创建失败时返回false；当创建成功时返回支付单ID
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>
     */
    public function create($data){
        //-+--------------------------------------------------------------------
        // | 过滤和验证检测单生成的信息
        //-+--------------------------------------------------------------------
        $params = filter_array($data, [
            //验证订单id
            'order_id' => 'required|is_id',
            //验证订单编号
            'order_no' => 'required',
            //验证业务类型
            'business_key' => 'required|is_int',
            //验证商品id
            'goods_id' => 'required|is_id',
        ]);

        //判断插入的必要信息是否存在
        if ( count($params) != 4 ){
            set_error('检测单创建时参数不全');
            return false;
        }
        //拼接插入数据库数据
        $datas = array(
            'order_id' => $params['order_id'],
            'order_no' => $params['order_no'],
            'goods_id' => $params['goods_id'],
            'business_key' => $params['business_key'],
            'evaluation_status' => zuji\order\EvaluationStatus::EvaluationWaiting,//待检测状态
            'create_time' => time(),//检测单生成时间
            'update_time' => time(),//检测单更新时间
        );
        
        //-+--------------------------------------------------------------------
        // | 生成检测单、通知订单处于检测中状态【事务处理】
        //-+--------------------------------------------------------------------
        
        try {
            //创建表单,返回id
            $evaluation_id =$this->evaluation_table->create($datas);
            if(!$evaluation_id){
                $this->evaluation_table->rollback();
                set_error('创建检测单失败');
                return false;
            }       
            //创建成功通知订单：检测单创建
            $order_result_create = $this->order_service->enter_evaluation($params['order_id'],$evaluation_id);
         
            //创建成功通知订单：检测单检测中
            if(!$order_result_create){
                $this->evaluation_table->rollback();
                set_error('同步到订单状态失败');
                return false;
            }
            //验证检测单检测创建是否同步到订单成功，成功后同步检测中状态到订单
            $order_result_wait = $this->order_service->notify_evaluation_waiting($params['order_id']);
            //验证订单检测中通知是否成功
            if( !$order_result_wait ) {//业务逻辑处理不成功
               $this->evaluation_table->rollback();
               set_error('同步到订单状态失败');
                return false;
            }
            
        } catch (\Exception $exc) {
           $this->evaluation_table->rollback();
           set_error('异常错误：'.$exc->getMessage());
           return false;
        }
        $this->evaluation_table->commit();
        return $evaluation_id;
    }
    /**
 * 查询检测单列表
 * @param array    $where	【可选】
 * array(
 *      'evaluation_id' => '',         // 【可选】  支付单ID，string|array （string：多个','分割）（array：检测单ID数组）
 *      'order_id' => '',   // 【可选】  订单ID，string|array （string：多个','分割）（array：订单ID数组）
 *      'order_no' => '',   // 【可选】  订单编号，string|array （string：多个','分割）（array：订单标号数组）
 *      'status'=>''        //【可选】检测状态：0初始化，1合格 2不合格
 *      'business_key'=>''  //【可选】检测类别：1还机，2换机，3退货
 *      'unqualified_result'=>''  //【可选】处理结果：0：未处理，1入库，2用户买断，3用户赔付后入库
 *      'begin_time'=>''         //【可选】int 检测开始时间
 *      'end_time'=>''           //【可选】int 检测结束时间
 * )
 *
 * @param array $additional	    【可选】附加选项
 * array(
 *	    'page'	=> '',	           【可选】int 分页码，默认为1
 *	    'size'	=> '',	           【可选】int 每页大小，默认20
 *	    'orderby'	=> '',  【可选】string	排序；evaluation_time_DESC：时间倒序；evaluation_time_ASC：时间顺序；默认 id_DESC
 * )
 * @author yaodongxu <yaodongxu@huishoubao.com.cn>
 * @return array(
 *      array(
 *          'id'=>'',//检测单ID
 *          'order_id'=>'',//订单ID
 *          'order_no'=>'',//订单编号
 *          'goods_id'=>'',//商品id
 *          'user_id'=>'',//用户id
 *          'status'=>'',//检测状态：0初始化，1合格 2不合格
 *          'evaluation_time'=>'',//检测时间
 *          'admin_id'=>'',//管理员ID
 *          'unqualified_remark'=>'',//检测备注
 *          'business_key'=>'',//测类别：1还机，2换机，3退货
 *          'create_time'=>'',//检测单生成时间
 *          'update_time'=>'',//检测单更新时间
 *      )
 * )
 * // 列表，没有查询到时，返回空数组
 */
    public function get_list($where=[],$additional=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        //过滤附加查询条件
        $additional = $this->__parse_additional($additional);
        //获取检测单列表
        $evaluation_list = $this->evaluation_table->get_list($where,$additional);
        //获取当前列表内的商品id数组
        $goods_ids = array_column($evaluation_list, 'goods_id');
        //获取当前列表所有商品信息
        $goods_lists = $this->order_service->get_goods_list(['goods_id'=>$goods_ids]);
        //获取当前列表所有收货单信息
        $receive_service = $this->load->service('order2/receive');
        $receive_lists = $receive_service->get_list(['goods_id'=>$goods_ids],['size'=>40]);
        //合并数据
        if(!mixed_merge($evaluation_list,$goods_lists,  'goods_id','goods_info')){
            return [];
        }
        //合并数据
        if( !mixed_merge( $evaluation_list,$receive_lists, 'goods_id','receive_info') ) {
            return [];
        }

        return $evaluation_list;
    }
   //重写检测单多条数据查询
    public function get_list2($where=[],$additional=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        //过滤附加查询条件
        $additional = $this->__parse_additional($additional);
        //获取检测单列表
        $evaluation_list = $this->evaluation_table->get_list($where,$additional);

        return $evaluation_list;
    }
    /**
     * 查询检测单信息
     * @param int $evaluation_id 检测单主键id
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>    
     * @return array(
     *      array(
     *          'id'=>'',//检测单ID
     *          'order_id'=>'',//订单ID
     *          'order_no'=>'',//订单编号
     *          'goods_id'=>'',//商品id
     *          'user_id'=>'',//用户id
     *          'status'=>'',//检测状态：0初始化，1合格 2不合格
     *          'evaluation_time'=>'',//检测时间
     *          'admin_id'=>'',//管理员ID
     *          'unqualified_remark'=>'',//检测备注
     *          'business_key'=>'',//测类别：1还机，2换机，3退货
     *          'create_time'=>'',//检测单生成时间
     *          'update_time'=>'',//检测单更新时间
     *      )
     * )     
     * // 列表，没有查询到时，返回空数组
     */
    public function get_info( $evaluation_id, $additional=[] ){
        //id为空直接返回空数组不进行数据查询
        $evaluation_id = intval($evaluation_id);
        if( $evaluation_id == 0 ) {
            set_error('查询传入的检测单id为空');
            return [];
        }
        //获取检测单信息
        $evaluation_info = $this->evaluation_table->get_info($evaluation_id, $additional);
        //获取当前列表内的商品id数组
        $goods_ids = array_column($evaluation_info, 'goods_id');
        //获取所有商品信息
        $goods_lists = $this->order_service->get_goods_list(['goods_id'=>$goods_ids],[]);
        if(mixed_merge($goods_lists, $evaluation_info, 'goods_id')){
            return $goods_lists[0];
        }
        set_error('查询信息不存在');
        return [];
    }
    public function get_info_by_order_id( $order_id ){
        //id为空直接返回空数组不进行数据查询
        $order_id = intval($order_id);
        if( $order_id == 0 ) {
            set_error('查询传入的订单id为空');
            return [];
        }
        //获取检测单信息
        $evaluation_info = $this->evaluation_table->get_info_by_order_id($order_id);
        if($evaluation_info){
            return $evaluation_info;
        }
        return [];
    }
    /**
     * 根据条件，查询总条数
     * @param array $where		查看 get_list() 定义
     * @return int  符合条件的记录总数  
     */
    public function get_count($where=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
       return $result=$this->evaluation_table->get_count($where);
    }
    /**
     * 过滤where条件
     * @param array $where 查看 get_list()定义
     */
    private function __parse_where( $where=[] ) {
        //过滤查询条件
        $where = filter_array($where, [
            'evaluation_id' => 'required',
            'order_no' => 'required',
            'evaluation_status' => 'required|zuji\order\EvaluationStatus::verifyStatus',
            'unqualified_result' => 'required|zuji\order\EvaluationStatus::verifyUnqualified',
            'business_key' => 'required|zuji\Business::verifyBusinessKey',
            'begin_time' => 'required|is_int',
            'end_time' => 'required|is_int',
        ]);
        $b = $this->_parse_where_field_array("evaluation_id",$where);
        if(!$b){
            return false;
        }
        //当订单编号存在时，先查找订单id，然后在进行检测列表获取
        if( isset($where['begin_time']) ){
            if( !isset($where['end_time']) ){
                $where['end_time'] = time();
            }
            // 时间错误
            if( $where['begin_time']>$where['end_time'] ){
                set_error('查询的开始时间大于结束时间');
                return [];
            }
        }
        if(isset($where['begin_time'])) {$time[] = array('GT',$where['begin_time']);}
        if(isset($where['end_time'])) {$time[] = array('LT',$where['end_time']);}
        if($time){$where['create_time'] = $time;}
        return $where;
    }
    /**
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'orderby'	=> '',  【可选】string	排序；create_time_DESC：时间倒序；create_time_ASC：时间顺序；默认 create_time_DESC
     * )
     */
    private function __parse_additional( $additional=[] ) {
        $additionals = [];
        $additionals['page'] = isset($additional['page']) ? intval($additional['page']) : 1;
        $additionals['size'] = isset($additional['size']) ? intval($additional['size']) : 20;
        if( isset($additional['orderby']) && in_array($additional['orderby'],['create_time_DESC','create_time_ASC']) ){
            if( $additional['orderby'] == 'create_time_DESC' ){
                $additionals['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'create_time_ASC' ){
                $additionals['orderby'] = 'create_time ASC';
            }
        }else{
            $additionals['orderby']="create_time DESC";
        }
        return $additionals;
    }


}