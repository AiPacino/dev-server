<?php
use zuji\Config;

class instalment_remark_service extends service {


    public function _initialize() {
        $this->table = $this->load->table('order2/order2_instalment_remark');
    }

    public function _parse_where( $param=[] ){
        // 参数过滤
        $param = filter_array($param, [
            'instalment_id' => 'required',
            'order_id' => 'required',
            'user_id' => 'required',
            'remark' => 'required',
        ]);
        if( isset($param['instalment_id']) ){
            $where['instalment_id'] = intval($param['instalment_id']);
        }
        if( isset($param['order_id']) ){
            $where['order_id'] = $param['order_id'];
        }
        if( isset($param['user_id']) ){
            $where['user_id'] = $param['user_id'];
        }
        return $where;
    }

    /**
     * 代扣分期-联系记录
     * @param array   $data	      【必选】
     */
    public function create($data){

        $data = filter_array($data, [
            'instalment_id' => 'required',
            'contact_status' => 'required',
            'remark' => 'required',
            'create_time' => 'required',
        ]);

        $b = $this->table->add($data);
        if(!$b){
            return false;
        }
        return $b;
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

        return $this->table->where($where)->count('id');

    }



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
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = Config::Page_Size;
        }
        $additional['size'] = min( $additional['size'], Config::Page_Size );

        if( !isset($additional['orderby']) ){   // 排序默认值
            $additional['orderby']='time_ASC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }

        $list = $this->table->page($additional['page'])->limit($additional['size'])->where($where)->order($additional['orderby'])->select();

        if(!is_array($list)){
            return [];
        }
        return $list;

    }

    /**
     * 详情
     */
    public function get_info($id){

        if( $id < 0){
            return [];
        }

        return $this->table->where(['id'=>$id])->find();

    }

}