<?php
/**
 * 		维修服务层
 */
class weixiu_service extends service{
    public function _initialize() {
        $this->weixiu_table = $this->load->table('weixiu/weixiu');
    }
    /**
     * 添加维修订单
     * @params array	维修单信息
     * [
     *     'order_no'   => '',        //【必须】string;订单编号
     *     'user_id'   => '',          //【必须】int;用户id
     *	   'weixiu_content' => '',	  //【必须】string;维修内容
     *     'weixiu_cause' => '',     //【必须】string;维修原因
     *     'pictures' => '',        //【必须】string;图片
     *     'user_remark' => '',     //【必须】string;用户备注
     * ]
     * @return mixed	false：失败； int：维修单ID
     * 当创建失败时返回false；当创建成功时返回维修单ID
     * @author chenchun <chenchun@huishoubao.com.cn>
     */
    public function create($data){
        if($data['repair_time']){
            $data['repair_time'] = strtotime($data['repair_time']);
        }else{
            $data['repair_time'] = time();
        }
        $data = filter_array($data,[
            'order_id' => 'required|is_id',
            'order_no' => 'required',
            'user_id' => 'required|is_id',
            'reason_name' =>   'required',
            'repair_time' => 'required',
        ]);
        if(count($data) < 4) {return false;}
        $data['create_time'] = time();
        $result = $this->weixiu_table->create_table($data);
        return $result;
    }
    /**
     * 单条查看维修单
     * @params array $where	维修单信息
     * [
     *     'order_no'   => '',        //【必须】string;订单编号
     *     'user_id'   => '',          //【必须】int;用户id
     * ]
     * @return mixed	false：失败； int：维修单ID
     * 当创建失败时返回false；当创建成功时返回维修单数据
     * @author chenchun <chenchun@huishoubao.com.cn>
     * array(
     *     'record_id' => '',           //【必须】int;维修记录ID
     *     'order_no'   => '',        //【必须】string;订单编号
     *     'user_id'   => '',          //【必须】int;用户id
     *	   'weixiu_content' => '',	  //【必须】string;维修内容
     *     'weixiu_cause' => '',     //【必须】string;维修原因
     *     'weixiu_info' => '',      //【必须】string;维修记录详情
     *     'pictures' => '',        //【必须】string;图片
     *     'user_remark' => '',     //【必须】string;用户备注
     *     'guest_remark' => '',    //【必须】string;客服备注
     *     'user_wuliu_no' => '',    //【必须】string;用户物流单号
     *     'weixin_wuliu_no' => '',     //【必须】string;维修物流单号
     *     'comment'   => '',      //【必须】string;评论
     *     'status'    => '',      //【必须】string;状态
     *     'create_time'   => '',    //【必须】string;添加时间
     *     'update_time'   => '',    //【必须】string;修改时间
     *     'check_time'   => '',     //【必须】string;审核时间
     * )
     */
    public function get_weixiu_info($where,$fields = 0){
        $where = filter_array($where,[
            'order_no' => 'required',
            'user_id' => 'required|is_id',
            'record_id' => 'required|is_id',
        ]);
        if(count($where) < 2 ){return false;}
        $result = $this->weixiu_table->get_info($where,$fields);
        return $result;
    }
    /**
     * 添加维修订单
     * @params array	维修单信息
     * [
     *    'record_id'   => '',         //【必须】int;维修记录ID
     *    'status'  => '',        //【必须】int;状态值
     * ]
     * @return mixed	false：失败； int：维修单ID
     * 当创建失败时返回false；当创建成功时返回修改条数
     * @author chenchun <chenchun@huishoubao.com.cn>
     */
    public function update($data){
        $data = filter_array($data,[
            'record_id' => 'required|is_id',
            'status' => 'required|is_int',
            'weixiu_info' => 'required',
            'user_remark'=>'required|is_string',
            'guest_remark'=>'required|is_string',
            'user_wuliu_no'=>'required',
            'weixin_wuliu_no'=>'required',
            'pictures'=>'required',
            'update_time'=>'update_time|is_int',
        ]);
        if(count($data) < 4) {return false;}
        $result = $this->weixiu_table->update_table($data);
        return $result;
    }
    /**
     * 查看维修单列表
     * @params array $where	维修单信息
     * [
     *     'order_no'   => '',        //【必须】string;订单编号
     *     'service_name'   => '',          //【必须】string;维修内容
     *     'status' => ''，              //【必须】int;维修状态
     *     'time_type' => '',            //【可选】string;按某一时间收索
     *     'begin_time' => '',           //【可选】int;开始时间
     *     'end_time' => '',             //【可选】int;结束时间
     * ]
     * @return mixed	false：失败； int：维修单ID
     * 当创建失败时返回false；当创建成功时返回维修单数据
     * @author chenchun <chenchun@huishoubao.com.cn>
     * array(
     *     'record_id' => '',           //【必须】int;维修记录ID
     *     'order_no'   => '',        //【必须】string;订单编号
     *     'user_id'   => '',          //【必须】int;用户id
     *	   'weixiu_content' => '',	  //【必须】string;维修内容
     *     'weixiu_cause' => '',     //【必须】string;维修原因
     *     'weixiu_info' => '',      //【必须】string;维修记录详情
     *     'pictures' => '',        //【必须】string;图片
     *     'user_remark' => '',     //【必须】string;用户备注
     *     'guest_remark' => '',    //【必须】string;客服备注
     *     'user_wuliu_no' => '',    //【必须】string;用户物流单号
     *     'weixin_wuliu_no' => '',     //【必须】string;维修物流单号
     *     'comment'   => '',      //【必须】string;评论
     *     'status'    => '',      //【必须】string;状态
     *     'create_time'   => '',    //【必须】string;添加时间
     *     'update_time'   => '',    //【必须】string;修改时间
     *     'check_time'   => '',     //【必须】string;审核时间
     * )
     */
    public function get_list($where,$options){
        // 参数过滤
        $where = $this->_pars_where($where);
        $result = $this->weixiu_table->get_list($where,$options);
        return $result;
    }
    /**
    * 获取符合条件的维修单数量
    * @param   array	$where  参考 get_list() 参数说明
    * @return int 查询总数
    */
    public function get_count($where){
        // 参数过滤
        $where = $this->_pars_where($where);
        if( $where === false ){
            return 0;
        }
        return $this->weixiu_table->get_count($where);
    }

    /**
     * 通过条件 获取维修记录多条
     * @return array
     */
    public function get_info_all($where){
        // 参数过滤
        return $this->weixiu_table->get_info_All($where);
    }
    /**
     * 过滤列表收拾条件
     * @param   array	$where  参考 get_list() 参数说明
     * @return int 查询总数
     */
    private function _pars_where($where){
        $where = filter_array($where,[
            'order_no' => 'required',
            'service_name' => 'required|is_string',
            'status' => 'required|is_int',
            'time_type' => 'required|is_string',
            'begin_time' => 'required|is_int',
            'end_time' => 'required|is_int',
        ]);
        if(isset($where['order_no'])){
            $where['order_no'] = ['LIKE', $where['order_no']. '%'];
        }
        if(isset($where['service_name'])){
            $where['service_name'] = ['LIKE', $where['service_name']. '%'];
        }
        if(isset($where['status'])){
            $where['status'] = ['ET',$where['status']];
        }
        // 时间查询条件，先根据 time_type 获取查询哪个时间，
        $time_key = '';
        // time_type (支持 create_time和receive_time)
        if(isset($where['time_type'])){
            if( $where['time_type']=='create_time' ){
                $time_key = 'create_time';
            } else if($where['time_type']=='update_time'){
                $time_key = 'update_time';
            } else if($where['time_type']=='check_time'){
                $time_key = 'check_time';
            } else {
                return false;// 时间类型参数错误
            }
            unset($where['time_type']);
        }
        // 找到要查询的时间，然后判断 结束时戳间和开始时间戳
        if( $time_key ){
            // 结束时间（可选），默认为为当前时间
            if( !isset($where['end_time']) ){
                $where['end_time'] = time();
            }
            // 开始时间（可选）
            if( isset($where['begin_time'])){
                if( $where['begin_time']>$where['end_time'] ){
                    return false;// 查询参数错误
                }
                $where[$time_key] = ['between',[$where['begin_time'], $where['end_time']]];
            }else{
                $where[$time_key] = ['LT',$where['end_time']];
            }
        }
        unset($where['begin_time']);
        unset($where['end_time']);

        return $where;
    }
}