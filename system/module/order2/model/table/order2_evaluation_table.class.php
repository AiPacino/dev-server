<?php
/**
 * 检测单表
 * @author yaodongxu <yaodongxu@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class order2_evaluation_table extends table {
    /**
     * 检测单表的数据库字段
     */
    protected $fields = [
        'evaluation_id',
        'business_key',
        'order_id',
        'order_no',
        'goods_id',
        'evaluation_status',
        'evaluation_time',
	'evaluation_remark',
	'evaluation_admin_id',
        'level',
	
        'unqualified_result',
        'unqualified_remark',
        'unqualified_admin_id',
	
        'create_time',
        'update_time'
    ];
    /**
     * 主键id
     */
    protected $pk = 'evaluation_id';


    public function _initialize() {
        
    }
    
    /**
     * 创建表单
     * @param array $data                      【必须】
     * $data = array(
     *      'order_id' => '',//订单id           【必须】
     *      'business_key' => '',//业务类型     【必须】
     *      'evaluation_status' => '',//状态    【必须】
     *      'create_time' => '',//创建时间      【必须】
     *      'update_time' => '',//更新时间      【必须】
     * )
     * @return boolean
     */
    public function create($data){
        return $this->add($data);
    }
    
    /**
     * 检测表根据主键更新
     * @param array $data                                      【至少两个参数】
     * array(
     *	    'evaluation_id' => '',   //int                              【必须】
     *	    'evaluation_status' => '',//int 检测状态1合格 2不合格       【可选】
     *	    'evaluation_time' => '',//int 检测时间                      【可选】
     *	    'qualified' => '',//int 检测是否合格                        【可选】 
     *	    'level' => '',//int 检测合格时设备级别                      【可选】 
     *	    'unqualified_result' => '',//int 检测不合格时处理结果        【可选】 
     *	    'admin_id' => '',//int 管理员ID                             【可选】
     *	    'unqualified_remark' => '',//string 检测备注                 【可选】 
     * )
     * @return boolean  true :成功  false:失败
     */
    public function update( $data ){
        //更新数据过滤
        $data = filter_array($data, [
            'evaluation_id' => 'required|is_id',
            'evaluation_status' => 'required',
            'evaluation_time' => 'required|is_int',
            'qualified' => 'required|zuji\order\EvaluationStatus::verifyResult',
            'level' => 'required|zuji\order\EvaluationStatus::verifyQualified',
            'unqualified_result' => 'required|zuji\order\EvaluationStatus::verifyUnqualified',
            'admin_id' => 'required',
            'unqualified_remark' => 'required|is_string',
            'evaluation_remark' => 'required|is_string',
        ]);
        //至少包含id和另外一个参数才能更新
        if( !isset($data['evaluation_id']) || count($data) < 2 ) {
            set_error('检测表更新数据有误');
            return false;
        }
        //拼接更新时间
        $data['update_time'] = time();
        return $this->save($data);
    }

    /**
     * 查询列表
     * @return array 
     */
    public function get_list($where=[],$additional=[]) {
        $evaluation_list = $this->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->field($this->fields)->where($where)->select();
        if($evaluation_list){
            return $evaluation_list;
        }       
        return [];
    }
    /**
     * 查询单条数据
     * @param int $evaluation_id 检测的主键
     * @return array 
     */
    public function get_info( $evaluation_id, $additional=[] ) {
        $evaluation_info = $this->field($this->fields)->where(['evaluation_id'=>$evaluation_id])->select($additional);
        if($evaluation_info){
            return $evaluation_info;
        }       
        return [];
    }
    /**
     * 根据订单id查询单条数据
     * @param int $order_id 订单id
     * @return array
     */
    public function get_info_by_order_id( $order_id, $additional=[] ) {
        $evaluation_info = $this->field($this->fields)->where(['order_id'=>$order_id])->find($additional);
        if($evaluation_info){
            return $evaluation_info;
        }
        return [];
    }
    /**
     * 根据条件查询总条数
     */
    public function get_count($where=[]){
        $evaluation_count = $this->where($where)->count();
        if($evaluation_count === false){
            return 0;
        }
        return $evaluation_count;
    }
}