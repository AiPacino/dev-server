<?php

/**
 * 错误日志
 * @outhor 
 */
class debug_error_table extends table {

    protected $fields = [
	'main_no',
	'sub_no',
	'location_id',
	'subject',
	'data_type', //
	'data', //
	'create_time', //
    ];

    /**
     * 保存debug记录
     * @param array   $data	      【必选】
     * array(
     *      'main_no'	=> '',//【必选】  int 主编号
     *      'sub_no'	=> '',//【必选】  int 序列号
     *      'location_id'   => '',//【必选】  int 位置标识
     *      'subject'	=> '',	//【必选】  string
     *      'data'	=>'',	//【可选】 string
     *      )
     * @return bool  true：成功；false：失败
     */
    public function create( $data ){
	// 字段替换
	$data = filter_array($data, [
	    'main_no' => 'required|is_id',
	    'sub_no' => 'required|is_id',
	    'location_id' => 'required|is_id',
	    'subject' => 'required',
	    'data_type' => 'required',
	    'data' => 'required',
	]);
	$data['create_time'] = time();
	$r = $this->add($data);
	return $r? true:false;
    }
    
    
    /**
     * 
     * @param string $debug_no
     * @return mixed	false：失败；array：debug信息
     * [
     * 	    'debug_no' => '',
     * 	    'main_no' => '',
     * 	    'sub_no' => '',
     * 	    'localtion_id' => '',
     * 	    'subject' => '',
     * 	    'data_type' => '',
     * 	    'data' => '',
     * 	    'create_time' => '',
     * ]
     */
    public function get_info($debug_no) {
	list($main_no,$sub_no) = explode('-', $debug_no);
	if( !$main_no || !$sub_no ){
	    set_error('参数错误');
	    return false;
	}
	return $this->field($this->fields)->where([
	    'main_no' => $main_no,
	    'sub_no' => $sub_no,
	])->limit(1)->find();
    }

    /**
     * 获取列表
     *
     * @param array  $where 【可选】查询条件 
     * [
     *      'debug_no' => '',	//【可选】
     *      'main_no' => '',	//【可选】int；主编号
     *      'sub_no' => '',	//【可选】int；序列号
     *      'location_id' => '',	//【可选】int；位置
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
	return $this->where($where)->count('main_no');
    }

    /**
     * 解析查询条件
     * @param type $where
     * @return mixed	false：查询条件参数异常；array：查询条件集合
     */
    private function _parse_where( $where ){
	// 字段替换
	$where = filter_array($where, [
	    'debug_no' => 'required',
	    'main_no' => 'required|is_id',
	    'sub_no' => 'required|is_id',
	    'location_id' => 'required|is_id',
	    'subject' => 'required',
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
        if( isset($where['debug_no']) ){
	    list($main_no,$sub_no) = explode('-', $where['debug_no']);
	    $where['main_no'] = $main_no;
	    $where['sub_no'] = $sub_no;
	    unset($where['debug_no']);
	}
	
	// 前缀模糊查询
        if( isset($where['main_no']) ){
	    $where['main_no'] = ['LIKE', $where['main_no'] . '%'];
	}
	// 前缀模糊查询
        if( isset($where['sub_no']) ){
	    $where['sub_no'] = ['LIKE', $where['sub_no'] . '%'];
	}
	// 前缀模糊查询
        if( isset($where['subject']) ){
	    $where['subject'] = ['LIKE', $where['subject'] . '%'];
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
            $additional['orderby']='time_DESC';
        }
        
        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
	return $additional;
    }
}
