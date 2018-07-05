<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class log_control extends init_control {
    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        ''=>'全部',
        'user_name'=> '操作者名称',
        'user_id'=>'操作者ID',
        'action_ip'=>'操作者IP',
    ];
	public function _initialize() {
		parent::_initialize();
		$this->service = $this->load->service('log');
		$this->admin = $this->load->service('admin_user');
	}

	/* 日志列表 */
	public function index() {
		$sqlmap = array();
        if($_GET['begin_time']!='' ){
            $sqlmap['begin_time'] = strtotime($_GET['begin_time']);
        }
        if( $_GET['end_time']!='' ){
            $sqlmap['end_time'] = strtotime($_GET['end_time']);
        }
        if($_GET['option_id']>'0' ){
            $sqlmap['option_id'] = intval($_GET['option_id']);
        }
        if($_GET['keywords']!='') {
            if ($_GET['kw_type'] == 'user_id') {
                $sqlmap['user_id'] = $_GET['keywords'];
            } elseif ($_GET['kw_type'] == 'action_ip') {
                $sqlmap['action_ip'] = $_GET['keywords'];
            } elseif ($_GET['kw_type'] == 'user_name') {
                $where['username'] = $_GET['keywords'];
                $user_info = $this->admin->getAll($where);
                if(isset($user_info)){
                    $sqlmap['user_id'] = $user_info[0]['id'];
                } else {
                    $sqlmap['user_id'] = 'asd';
                }
            }
        }
		$count = $this->service->count($sqlmap);
		$limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
		$log = $this->service->get_lists($sqlmap,$limit,$_GET['page']);
		foreach ($log as $k => $v){
            $log[$k]['dateline'] = date('Y-m-d H:i:s',$v['dateline']);
        }
		$pages = $this->admin_pages($count, $limit);
		$this->load->librarys('View')
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('opreation_list', array_merge( ['0'=>'全部'], \zuji\debug\Opreation::getOpreationList() ) )
            ->assign('log',$log)->assign('pages',$pages)->display('log_index');
	}
	
	/* 删除 */
	public function del() {
		$id = (array)$_GET['id'];
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$result = $this->service->delete($id);
		if($result === false) showmessage($this->service->error);
		showmessage(lang('删除成功'), url('index'), 1);
	}
}
