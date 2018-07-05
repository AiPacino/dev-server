<?php

/**
 * 增加发送短信的记录
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
class sms_table extends table {

    protected $fields = [
	'id',
	'sms_no',
	'user_mobile',
	'order_no', //
	'json_data', //
    'response',
	'create_time', //
    ];

    /**
     * 保存sms记录
     * @param array   $data	      【必选】
     * @return bool  true：成功；false：失败
     */
    public function create( $data ){
	$data['create_time'] = time();
	$r = $this->add($data);
	return $r? true:false;
    }

    /**
     * 获取列表
     *
     * @param array  $where 【可选】查询条件 
     * [
     *      'sms_no' => '',	//【可选】
     *      'user_mobile' => '',	//【可选】int；主编号
     *      'order_no' => '',	//【可选】int；位置
     *      'begin_time' => '',	//【可选】int；开始时间戳
     *      'end_time' => '',	//【可选】int；结束时间戳
     * ]
     * @return array    数组键名查看 get_info() 方法
     */
    public function get_list($where=[], $additional=[]) {
	// 字段替换
	$where = $this->_parse_where($where);
	if( $where==false ){
	    return 0;
	}
	$additional = $this->_parse_additional($additional);
	$_list = $this->field($this->fields)
		->where($where)
		->page($additional['page'])
		->limit($additional['size'])
		->order($additional['orderby'])
		->select();
	if (!is_array($_list)) {
	    return [];
	}
	return $_list;
    }

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
	// 字段替换
	$where = $this->_parse_where($where);
	if( $where==false ){
	    return 0;
	}
	return $this->where($where)->count('id');
    }

    /**
     * 解析查询条件
     * @param type $where
     * @return mixed	false：查询条件参数异常；array：查询条件集合
     */
    private function _parse_where( $where ){
	// 字段替换
	$where = filter_array($where, [
	    'sms_no' => 'required',
	    'user_mobile' => 'required',
	    'order_no' => 'required',
	    'begin_time' => 'required|is_time',
	    'end_time' => 'required|is_time',
	]);

	// 结束时间（可选），默认为为当前时间
	if( !isset($where['end_time']) ){
	    $where['end_time'] = time();
	}

	// 开始时间（可选）
	if( isset($where['begin_time'])){
	    if( $where['begin_time']>$where['end_time'] ){
		        return false;
	    }
	    $where['create_time'] = ['between',[$where['begin_time'], $where['end_time']]];
	}else{
	    $where['create_time'] = ['LT',$where['end_time']];
	}
	unset($where['begin_time']);
	unset($where['end_time']);

	
	// 前缀模糊查询
        if( isset($where['order_no']) ){
	    $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
	    }
        if( isset($where['user_mobile']) ){
            $where['user_mobile'] = ['LIKE', $where['user_mobile'] . '%'];
        }
        if( isset($where['sms_no']) ){
            $where['sms_no'] = ['LIKE', $where['sms_no'] . '%'];
        }

	    return $where;
    }

    /**
     * 
     * @param type $additional
     * @return array
     * [
     *	    'page' => '',
     *	    'size' => '',
     *	    'orderby' => '',
     * ]
     */
    private function _parse_additional( $additional ){
	
        $additional = filter_array($additional, [
            'page' => 'required|is_page',
            'size' => 'required|is_size',
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

        if( !isset($additional['orderby']) ){	// 排序默认值
            $additional['orderby']='create_time DESC';
        }
        
//        if( in_array($additional['orderby'],['create_time_DESC','create_time_ASC']) ){
//            if( $additional['orderby'] == 'create_time_DESC' ){
//                $additional['orderby'] = 'create_time DESC';
//            }elseif( $additional['orderby'] == 'create_time_ASC' ){
//                $additional['orderby'] = 'create_time ASC';
//            }
//        }
	return $additional;
    }

    public function get_info($id) {
        if( $id <1  ){
            set_error('参数错误');
            return false;
        }
        return $this->field($this->fields)->where([
            'id' => $id,
        ])->limit(1)->find();
    }
}
