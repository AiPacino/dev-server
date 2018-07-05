<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2017/12/28 0028-下午 5:12
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('init', 'admin');
class channel_member_control extends init_control
{

    public function _initialize(){
        parent::_initialize();
        $this->service = $this->load->service('channel_member');
        $this->channel_service = $this->load->service('channel/channel');
        $this->appid_service = $this->load->service('channel/channel_appid');
    }

    public function index(){
        $where = [];
        $options['page'] = $_GET['page'];
        $data = $this->service->get_lists($where, $options);
        $count = $this->service->count($where);
        $pages = $this->admin_pages($count, 20);
        $lists = array(
            'th' => array(
                'username' => array('title' => '用户名','length' => 20),
                'type' => array('title' => '账户类型','length' => 10),
                'relation_name' => array('title' => '门店/渠道名称','length' => 20),
                'login_time' => array('title' => ' 最后登录时间','length' => 15,'style' => 'date'),
                'login_num' => array('length' => 10,'title' => '共计登录次数'),
                'status' => array('title' => '启用','style' => 'ico_up_rack','length' => 10)
            ),
            'lists' => $data,
            'pages' => $pages,
        );
        $this->load->librarys('View')->assign('lists',$lists)->display('channel_member_index');
    }

    /* 删除 */
	public function delete() {
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$result = $this->service->delete($_GET['id']);
		if($result === FALSE) showmessage($this->service->error);
		showmessage(lang('_del_channel_user_success_','channel/language'), url('index'), 1);
	}

    /* 添加 */
    public function add() {
        if(checksubmit('dosubmit')){
            $r = $this->service->save($_POST, true);
            if($r == false) {
                showmessage($this->service->error, url('index'), 1);
            }else{
                showmessage(lang('_update_member_success_','channel/language'), url('index'), 1);
            }
        }else{
            $channel_list = $this->channel_service->get_list(['status' => 1]);
            $type_list = $this->service->enum_type;
            $appid_list = $this->appid_service->get_list(['status' => 1]);
            $this->load->librarys('View')
                ->assign('channel_list', $channel_list)
                ->assign('type_list', $type_list)
                ->assign('appid_list',$appid_list)
                ->display('channel_member_update');
        }
    }
    /* 编辑 */
    public function edit() {
        if (checksubmit('dosubmit')) {
            $r = $this->service->save($_POST,FALSE);
            showmessage(lang('_update_admin_group_success_','admin/language'), url('index'), 1);
        } else {
            $channel_list = $this->channel_service->get_list(['status' => 1]);
            $type_list = $this->service->enum_type;
            $appid_list = $this->appid_service->get_list(['status' => 1]);
            $data = $this->service->fetch_by_id($_GET['id']);
            $this->load->librarys('View')
                ->assign('channel_list', $channel_list)
                ->assign('type_list', $type_list)
                ->assign('appid_list',$appid_list)
                ->assign('data',$data)
                ->display('channel_member_update');
        }

    }

    /**
     * [ajax_status 更改状态]
     */
    public function ajax_status(){
        $result = $this->service->change_status($_GET['id']);
        if(!$result){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

    public function bind() {
        if (checksubmit('dosubmit')) {
            $params = $_POST;
            if(empty($params['mobile'])){
                showmessage('手机号不能为空', url('index'), 1);
            }
            $model = model('channel_member_mobile');
            $result = $model->bind($params);
            if($result == false) {
                showmessage($model->error, url('index'), 1);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        } else {
            $data = $this->service->fetch_by_id($_GET['id']);
            $this->load->librarys('View')
                ->assign('data',$data)
                ->display('channel_member_bind');
        }
    }
}