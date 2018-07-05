<?php
use zuji\debug\Debug;
use zuji\debug\Location;
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
hd_core::load_class('init', 'admin');
class member_control extends init_control {
    public function _initialize() {
        parent::_initialize();
        $this->service          = $this->load->service('member/member');
        $this->channel_service  = $this->load->service('channel/channel');
        $this->member_table     = $this->load->table('member/member');
        $this->member_group_table = $this->load->table('member/member_group');
        $this->member_address_service = $this->load->service('member/member_address');
    }

    public function index() {
        $sqlmap = array();
	$_GET['keyword'] = trim($_GET['keyword']);
	if(strlen($_GET['keyword'])>0 ){
	    $sqlmap['username|email|mobile'] = array("LIKE", $_GET['keyword'].'%');
	}
        if($_GET['certified'] && in_array($_GET['certified'], ['y','n']) ){
            $sqlmap['certified'] = array('EQ',$_GET['certified']=='y'?1:0);
        }

        $limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $lists = $this->service->get_lists($sqlmap,$_GET['page'],$limit);
        $count = $this->service->count($sqlmap);
        $pages = $this->admin_pages($count, $limit);
        $member_group = $this->load->service('member/member_group')->getfield('id,name');
        array_unshift($member_group,'所有等级');
        $lists = array(
            'th' => array(
                'username' => array('title' => '会员','length' => 15,'style' => 'member'),
                'certified_show' => array('length' => 5,'title' => '认证状态'),
                'certified_platform_name' => array('length' => 10,'title' => '认证平台'),
                'realname' => array('length' => 8,'title' => '真实姓名'),
                'cert_no' => array('length' => 8,'title' => '身份证'),
                'credit' => array('length' => 5,'title' => '信用分'),
                'face_show' => array('length' => 5,'title' => '人脸识别'),
                'risk_show' => array('length' => 5,'title' => '风控值'),
                'withholding_no' => array('length' => 10,'title' => '代扣协议码'),
                //'credit_time_show' => array('length' => 8,'title' => '认证时间'),
                'login' => array('title' => '注册&登录','length' => 10,'style' => 'login'),
                'block' => array('length' => 10,'title' => '用户下单解封'),
                'lock' => array('length' => 5,'title' => '状态'),
            ),
            'lists' => $lists,
            'pages' => $pages,
        );
        $this->load->librarys('View')->assign('lists',$lists)->assign('pages',$pages)->assign('member_group',$member_group)->display('member_index');
    }

//    public function update() {
//        $id = (int) $_GET['id'];
//        $member = $this->service->fetch_by_id($id);
//        if(!$member) showmessage($this->service->error);
//        if(checksubmit('dosubmit')) {
//            foreach ($_POST['info'] as $t => $v) {
//                if(is_numeric($v['num']) && !empty($v['num'])) {
//                    $v['num'] = ($v['action'] == 'inc') ? '+'.$v['num'] : '-'.$v['num'];
//                    $this->service->change_account($id, $t, $v['num'], $_POST['msg']);
//                }
//            }
//            showmessage('_operation_success_', url('index'), 1);
//        } else{
//            $this->load->librarys('View')->assign('member',$member)->display('member_update');
//        }
//    }
//
//    public function delete() {
//        if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) {
//            showmessage('_token_error_',url('index'),0);
//        }
//        $result = $this->service->delete_by_id($_GET['ids']);
//        showmessage('_operation_success_',url('index'),1);
//    }

    /**
     * 详情
     */
    public function detail(){
        $user_id    = intval( $_GET['id'] );

        // 渠道
        $channel_list = $this->channel_service->get_list();

        if($user_id){

            $user_info  = $this->member_table->where(['id'=>$user_id])->find();

            $user_info['islock']        = $user_info['islock'] == 1 ? '锁定' : '正常';
            $user_info['register_time'] = date('Y-m-d H:i:s', $user_info['register_time']);
            $user_info['login_time']    = date('Y-m-d H:i:s', $user_info['login_time']);
            $user_info['credit_time']   = date('Y-m-d H:i:s', $user_info['credit_time']);
            $user_info['certified_platform']   = zuji\certification\Certification::getPlatformList($user_info['certified_platform']);
            $user_info['face']          = $user_info['face'] == 1 ? '通过' : '--';
            $user_info['risk']          = $user_info['risk'] == 1 ? '通过' : '--';
            $user_info['certified']     = $user_info['certified'] == 1 ? '已认证' : '--';

            //租机渠道
            $user_info['appid'] = isset($channel_list[$val['appid']]) ? $channel_list[$val['appid']]['name'] : '其他';

            // 会员等级
            $group_name = $this->member_group_table->fetch_by_id($user_info['group_id'],'name');
            $user_info['group_name'] = $group_name ? $group_name : '--';

            $address_list = $this->member_address_service->lists(['mid' => $user_id]);

        }

        $this->load->librarys('View')
            ->assign('user_info',$user_info)
            ->assign('address_list',$address_list['lists'])
            ->display('detail');
    }
    /**
     *    解封记录
     *
     */
    public function deblocking_record(){
        $member_id = intval($_GET['id']);
        $deblocking =$this->service->get_by_member_id($member_id);
        $where =[];
        //$size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
	    $count  = $this->service->get_count($member_id);
	    $pages  = $this->admin_pages($count,10);
	    $additional =[
	        'page'=>$_GET['page'],
	        'size'=>10,
	    ];
	    $deblocking_list =$this->service->get_by_member_id($member_id,$additional);
	    foreach ($deblocking_list as $key => &$item) {
	        $item['deblocking_time'] = date('Y-m-d H:i:s', $item['deblocking_time']);
	        $_admin = model('admin/admin_user')->find($item['admin_id']);
	        $item['admin'] = $_admin['username'];
	    }
	    $lists = array(
	        'th' => array(
	            'admin_remark' => array('title' => '解封备注','length' => 50),
	            'deblocking_time' => array('title' => '解封日期','length' => 30),
	            'admin' => array('length' => 20,'title' => '操作员'),
	        ),
	        'lists' => $deblocking_list,
	        'pages' => $pages,
	    );
	    
        $this->load->librarys('View')
        ->assign('lists',$lists)
        ->display('deblocking_record');
        //->assign('deblocking',$deblocking)
        //->display('alert_deblocking_record');
    }
/**
 *   下单限制锁定 解锁
 * 
 */
    public function deblocking(){
        $member_id = intval($_GET['id']);
        if($member_id <1){
             echo json_encode(array('status'=>0,'msg'=>'会员ID错误'));exit;
        }
        
        $this->load->librarys('View')
        ->assign('member_id',$member_id)
        ->display('alert_deblocking');  
    }
    /**
     * 解锁提交
     */
    public function confirm_deblocking(){
        $member_id =$_POST['member_id'];
        $admin_remark =$_POST['admin_remark'];
       
        $result =$this->service->on_deblocking($member_id);
        
        if(!$result){
            Debug::error(Location::L_Member, "解封会员失败", "会员ID：".$member_id);
            echo json_encode(array('status'=>0,'msg'=>'解封会员失败'));exit;
        }
        
        $block_data =[
            'member_id' =>intval($member_id),
            'admin_id' =>intval($this->admin['id']),
            'admin_remark' =>$admin_remark,
        ];
        $block =$this->service->create_deblocking($block_data);
        if(!$block){
            Debug::error(Location::L_Member, "插入解封记录失败", $block_data);
            echo json_encode(array('status'=>0,'msg'=>'插入解封记录失败'));exit;
        }
        echo json_encode(array('status'=>1,'msg'=>'解封成功'));exit;
        
    }
	public function togglelock() {
        if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) {
           showmessage('_token_error_',url('index'),0);
        }
        $ids = (array) $_GET['ids'];
        $result = $this->service->togglelock_by_id($ids,$_GET['type']);
        showmessage('_operation_success_',url('index'),1);
    }

    public function address() {
        $_GET['mid'] = (int) $_GET['mid'];
	$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 5;
	$lists = $this->load->service('member/member_address')->lists(array('mid' => $_GET['mid']), $limit,$_GET['page']);
        $pages = $this->admin_pages($lists['count'], $limit);
        $this->load->librarys('View')->assign('pages',$pages)->assign('lists',$lists)->display('member_address');
    }
}
