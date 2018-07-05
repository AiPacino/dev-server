<?php
/**
 * 		押金配置服务层
 *      @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
class deposit_service extends service {

	public function _initialize() {
        $this->deposit_table = $this->load->table('payment/deposit');
        $this->payment_style_table = $this->load->table('payment/payment_style');
	}
     /**
      * 查询支付单列表
      * @param array    $where	【可选】
      * array(
      *      'channel_id' => '',            //【可选】int 渠道ID，string|array （string：多个','分割）（array：渠道ID数组）
      *      'deposit_name' => '',          //【可选】string 押金配置名称：%like%
      *      'is_open'=>''                  //【可选】int 是否开启
      *      'is_default'=>''               //【可选】int 是否默认显示
      *      'deposit_type_id'=>''          //【可选】string 押金类型ID
      * )
      * @param array $additional	    【可选】附加选项
      * array(
      *	    'page'	=> '',	           【可选】int 分页码，默认为1
      *	    'size'	=> '',	           【可选】int 每页大小，默认20
      *	    'orderby'	=> '',         【可选】string 默认 id_DESC
      * )houbao.com.cn>
      * @author wuhaiyan <wuhaiyan@huis
      * @return array(
      *      array(
                  'id',                 //【必须】int 主键ID
                  'deposit_name',       //【必须】int  押金配置名称
                 'deposit_type_id',     //【必须】int 押金类型 1 全部押金 2 阶梯押金
                 'is_open',             //【必须】int 是否开启 0未开启 1 已开启
                 'is_default',          //【必须】int 是否默认 0 不是 1 默认
                 'channel_id',          //【必须】int 渠道ID 多个用","分开
                 'xinyong_type_id',     //【必须】int  信用分类型 1 芝麻
                 'xinyong_begin',       //【必须】int 信用分的范围开始（包含）
                 'xinyong_end',         //【必须】int 信用分的范围结束（包含）
                 'age_begin',           //【必须】int 年龄的范围开始（包含）
                 'age_end',             //【必须】int 年龄的范围结束(包含)
                 'zujin_begin',         //【必须】int 总租金（租金+碎屏险）范围开始（包含）
                 'zujin_end',           //【必须】int 总租金（租金+碎屏险）范围结束（包含）
                 'deposit_config',      //【必须】int 押金配置方式1 具体金额 2 百分比
                 'deposit_config_mode', //【必须】int 总租金百分比 2 市场价百分比
                 'create_time',         //【必须】int 创建时间
                 'update_time',         //【必须】int 更新时间
                 'admin_id',            //【必须】int 操作员
      *      )
      * )
      * 支付单列表，没有查询到时，返回空数组
      */
     public function get_list($where=[],$additional=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        //过滤附加查询条件
        $additional = $this->__parse_additional($additional);
        $deposit_list = $this->deposit_table->get_list($where,$additional);
        return $deposit_list;
    }
    /**
     * 过滤where条件
     * @param array $where 查看 get_list()定义
     */
    private function __parse_where( $where=[] ) {
        //过滤查询条件
        $where = filter_array($where, [
            'id' => 'required',
            'deposit_name' => 'required',
            'payment_style_id' => 'required',
            'is_open' => 'required',
        ]);
        
        if(isset($where['deposit_name'])){
            // deposit_name  押金名称，使用前后缀模糊查询
            $where['deposit_name'] = ['LIKE', '%'.$where['deposit_name'] . '%'];
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
            $additional['orderby']='id ASC';
        }

        return $additional;
    }
    
     /**
     * 根据支付方式 获取押金数组
     * @param int $id  支付方式ID
     * @return array
     *[
     *      'id' => '',             //【必须】int 主键ID，string|array （string：多个','分割）
     *      'deposit_name' => '',   //【必须】string 押金名称
     *      'payment_style_id'=>''  //【必须】int 支付id
     *      'is_open'=>''           //【必须】int 是否开启
     *      'create_time'=>''       //【必须】int 创建时间
     *      'update_time'=>''       //【必须】int 修改时间
     *      'admin_id'=>''          //【必须】int 操作员
     * ]
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function get_info_by_payment($id){
        if($id <1){
            set_error("支付方式ID错误");
            return false;
        }

        $where = ['payment_style_id'=>$id];
        $additional = $this->__parse_additional();
        $deposit_list = $this->deposit_table->get_list($where, $additional);

        if(!$deposit_list){
            return [];
        }
        return $deposit_list;
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
        return $result=$this->deposit_table->get_count($where);
     }
    /**
     * 获取单个配置
     * @param array   $id	【必选】
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_info($id, $additional=[])
    {
        $deposit_info = $this->deposit_table->get_info($id, $additional);

        if( !$deposit_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($deposit_info);
        
        return $deposit_info;
    }
        private function _output_format(&$deposit_info){
            $_admin = model('admin/admin_user')->find($deposit_info['admin_id']);
            $deposit_info['admin_name'] =$_admin['username'];
            $deposit_info['create_time_show'] =$deposit_info['create_time']>0?date('Y-m-d H:i:s',$deposit_info['create_time']):'--';
            $deposit_info['update_time_show'] =$deposit_info['update_time']>0?date('Y-m-d H:i:s',$deposit_info['update_time']):'--';
        ;
    }

    /**
     * [search_brand 关键字查找机型]
     * @param  [type] $keyword [description]
     * @return [type]          [description]
     */
    public function ajax_machine($keyword){
        $sqlmap = array();
        if($keyword){
            $sqlmap = array('pay_name'=>array('LIKE','%'.$keyword.'%'));
        }
        $result = $this->payment_style_table->where($sqlmap)->getField('id,pay_name',TRUE);

        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * 添加、修改
     * @param $id
     * @param $params
     * @return bool|mixed
     */
    public function save_params( $params ){
        $id = $params['id'];

        if(empty($id)){
            $params['create_time'] = time();
            $params['update_time'] = time();
            $result = $this->deposit_table->add($params);
        }else{
            $params['update_time'] = time();
            $result = $this->deposit_table->where(['id'=>$id])->save($params);
        }
        if($result === false){
            $this->error = $this->getError();
            return false;
        }
        return $result;
    }


    /**
     * 开启事务
     */
    public function startTrans(){
        return $this->deposit_table->startTrans();
    }
    public function rollback(){
        return $this->deposit_table->rollback();
    }
    public function commit(){
        return $this->deposit_table->commit();
    }

    public function get_list_by_payment(int $payment_style_id){
        $credits =$this->deposit_table->where(['payment_style_id' => $payment_style_id,'is_open'=>1])->select();
        return $credits;
    }
}