<?php
/**
 * 		信用管理服务层
 *      @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
class credit_service extends service {

	public function _initialize() {
            $this->credit_table = $this->load->table('payment/credit');
	}
     /**
      * 查询信用管理列表
      * @param array    $where	【可选】
      * array(
      *      'id' => '',            //【可选】int 信用主键ID，string|array （string：多个','分割）
      *      'credit_name' => '',   //【可选】string 信用名称：%like%
      *      'min_credit_score'=>'' //【可选】int 信用最小分
      *      'max_credit_score'=>'' //【可选】int 信用最大分
      *      'is_open'=>''         //【可选】int 是否启用
      * )
      * @param array $additional	    【可选】附加选项
      * array(
      *	    'page'	=> '',	           【可选】int 分页码，默认为1
      *	    'size'	=> '',	           【可选】int 每页大小，默认20
      *	    'orderby'	=> '',         【可选】string 默认 id_DESC
      * )
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      * @return array(
      *      array(
      *      'id' => '',            //【必须】int 信用主键ID，string|array （string：多个','分割）
      *      'credit_name' => '',   //【必须】string 信用名称：%like%
      *      'min_credit_score'=>'' //【必须】int 信用最小分
      *      'max_credit_score'=>'' //【必须】int 信用最大分
      *      'is_open'=>''         //【必须】int 是否启用
      *      )
      * )
      * 支付单列表，没有查询到时，返回空数组
      */
     public function get_list($where=[],$additional=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        //过滤附加查询条件
        $additional = $this->__parse_additional($additional);
        $list = $this->credit_table->get_list($where,$additional);
        return $list;
    }
    /**
     * 过滤where条件
     * @param array $where 查看 get_list()定义
     */
    private function __parse_where( $where=[] ) {
        //过滤查询条件
        $where = filter_array($where, [
            'id' => 'required',
            'credit_name' => 'required',
            'min_credit_score' => 'required',
            'max_credit_score' => 'required',
            'is_open' => 'required',
        ]);
        if(isset($where['id'])){
            $this->_parse_where_field_array('id',$where);
        }
        
        if(isset($where['credit_name'])){
            // deposit_name  押金名称，使用前后缀模糊查询
            $where['credit_name'] = ['LIKE', '%'.$where['credit_name'] . '%'];
        }
        return $where;
    }
    /**
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     * )
     */
    private function __parse_additional( $additional=[] ) {
        // 附加条件
        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required|is_int',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }
        $additional['size'] = min( $additional['size'], 20 );
        
        if( !isset($additional['orderby']) ||$additional['orderby'] ==""){	// 排序默认值
            $additional['orderby']='id DESC';
        }

        return $additional;
    }
    /**
     * @param  string $field 获取的字段
     * @param  array  $where sql条件
     * @return [type]
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function getField($field = '',$sqlmap = array()) {

        return $this->credit_table->getFields($field,$sqlmap);
    }
    /**
     * 根据支付方式 获取信用数组
     * @param int $id  支付方式ID
     * @return array
     *[
     *       'id' => '',            //【必须】int 信用主键ID，string|array （string：多个','分割）
     *      'credit_name' => '',   //【必须】string 信用名称：%like%
     *      'min_credit_score'=>'' //【必须】int 信用最小分
     *      'max_credit_score'=>'' //【必须】int 信用最大分
     *      'is_open'=>''         //【必须】int 是否启用
     * ]
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function get_info_by_payment($id){
        if($id <1){
            set_error("支付方式ID错误");
            return false;
        }
        $this->service = $this->load->service('payment/payment_style');
        $info = $this->service->modelId($id);
        if(empty($info)){
            return [];
        }
        $credits =$this->credit_table->where(['id' => $info['credit_id'],'is_open'=>1])->select();
        return $credits;
    }

     /**
      * 根据条件，查询总条数
      * @param array $where		查看 get_list() 定义
      * @return int  符合条件的记录总数
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      */
     public function get_count($where=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        return $result=$this->credit_table->get_count($where);
     }
    /**
     * 获取单个配置
     * @param array   $id	【必选】
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_info($id, $additional=[])
    {
        $credit_info = $this->credit_table->get_info($id, $additional);

        if( !$credit_info ){
            return false;
        }
        // 格式化输出
      //  $this->_output_format($credit_info);
        return $credit_info;
    }
//        private function _output_format(&$deposit_info){
//            $_admin = model('admin/admin_user')->find($deposit_info['admin_id']);
//            $deposit_info['admin_name'] =$_admin['username'];
//            $deposit_info['create_time_show'] =$deposit_info['create_time']>0?date('Y-m-d H:i:s',$deposit_info['create_time']):'--';
//            $deposit_info['update_time_show'] =$deposit_info['update_time']>0?date('Y-m-d H:i:s',$deposit_info['update_time']):'--';
//        }
}