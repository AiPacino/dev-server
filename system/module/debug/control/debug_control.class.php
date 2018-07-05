<?php
/**
 * 订单控制器 基类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 *
 */
// 加载 goods 模块中的 init_control
hd_core::load_class('base', 'debug');
class debug_control extends base_control {



    public function _initialize() {
	parent::_initialize();
    }
    
    public function index(){
	
        // 查询条件
        $where = [];
        if($_GET['begin_time']!='' ){
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if( $_GET['end_time']!='' ){
            $where['end_time'] = strtotime($_GET['end_time']);
	}else{
	    $_GET['end_time'] = date('Y-m-d H:i:s');
	}
        if($_GET['location_id'] && $_GET['location_id']!='all'){
	    $where['location_id'] = intval($_GET['location_id']);
        }
        if($_GET['keywords']!=''){
	    if( $_GET['kw_type']=='debug_no' ){
		$where['debug_no'] = $_GET['keywords'];
	    }elseif( $_GET['kw_type']=='subject' ){
		$where['subject'] = $_GET['keywords'];
	    }
        }
	
        $limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $additional['page'] = intval($_GET['page']);
        $additional['size'] = intval($limit);
	
        $count = $this->debug_service->get_count($where,$additional);
        $debug_list = $this->debug_service->get_list($where,$additional);
        $pages  = $this->admin_pages($count, $limit);

        $data_table = array(
            'th' => array(
		'debug_no' => array('length' => 15,'title' => '序号'),
                'location_name' => array('title' => '位置','length' => 15),
                'subject' => array('title' => '标题','length' => 15),
                'create_time_show' => array('title' => '创建时间','length' => 15),
            ),
            'record_list' => $debug_list,
            'pages' => $pages,
        );
	$location_list = ['all'=>'全部'];
	foreach( $this->debug_service->get_location_list() as $id => $name ){
	    $location_list[''.$id] = $name;
	}
	$keywords_type_list = ['debug_no'=>'序号','subject'=>'标题'];
        $this->load->librarys('View')
            ->assign('location_list',$location_list)
            ->assign('keywords_type_list',$keywords_type_list)
            ->assign('data_table',$data_table)->assign('pages',$pages)->display('index');
    }

    public function detail(){
	$params  = filter_array($_GET, [
	    'debug_no' => 'required',
	]);
	if( count($params)!=1 ){
	    showmessage('参数错误');
	}
	$debug_no = $_GET['debug_no'];
	// 
	$info = $this->debug_service->get_info($debug_no);
	if( !$info ){
	    showmessage('查询失败');
	}
	if($info['data_type'] == 1){
	    $info['data'] = array($info['data']);
    }elseif ($info['data_type'] == 2){
        $info['data'] = json_decode(json_encode($info['data']), true);
    }
	//var_dump( $info );exit;
        $this->load->librarys('View')
            ->assign('data',$info)->display('detail');
    }
    
}