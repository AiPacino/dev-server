<?php
/**
 * 		维修控制器
 */

// 加载 goods 模块中的 init_control
hd_core::load_class('init','admin');
class weixiu_control extends init_control{
    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no'=>'订单编号',
        'user_id'=>'用户ID',
    ];
    public function _initialize() {
        parent::_initialize();
        $this->weixiu = $this->load->service('weixiu/weixiu');
        $this->order_service = $this->load->service('order2/order');
        $this->service_order_log = $this->load->service('order2/order_log');
    }
    public function index(){
        $kw_type = $_GET['kw_type'];
        $where = [];
        if($kw_type == 'order_no'){
            $where['order_no'] = intval($_GET['keywords']);
        }
        if($kw_type == 'user_id') {
            $where['user_id'] = intval($_GET['keywords']);
        }
        $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $count  = $this->weixiu->get_count($where);
        $pages  = $this->admin_pages($count,$size);
        $additional =[
            'page'=>intval($_GET['page']),
            'size'=>intval($size),
            'orderby'=>'',
        ];
        $Weixiu_list = $this->weixiu->get_list($where,$additional);
        foreach ($Weixiu_list as $k=>$v){
            $Weixiu_list[$k]['create'] = date('Y-m-d H:i:s',$v['create_time']);
            $Weixiu_list[$k]['repair_time'] = date('Y-m-d H:i:s',$v['repair_time']);
/*            if(isset($v['check_time'])) {
                $Weixiu_list[$k]['update'] = date('Y-m-d H:i:s',$v['update_time']);
            }
            $v['status'] = \zuji\order\WeixiuStatus::getStatusName($v['status']);*/
        }
        $lists = array(
            'th' => array(
                'order_no' => array('length' => 15,'title' => '订单编号'),
                'reason_name' => array('title' => '维修原因','length' => 25),
                'repair_time' => array('title' => '维修时间','length' => 15),
                'create' => array('title' => '添加时间','length' => 15),
            ),
            'lists' => $Weixiu_list,
            'pages' => $pages,
        );
//        var_dump($lists);exit;

        //赋值模板
        $this->load->librarys('View')
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('lists',$lists)
            ->display('weixiu_index');
    }
    /**
     * 客服处理维修单
     */
    public function update(){
        $record_id = intval($_GET['record_id']);
        $status = intval($_GET['status']);
        if($record_id<1){
            showmessage(lang('_error_action_'),"",0);
        }
        if (checksubmit('dosubmit')) {
            $record_id = intval($_POST['record_id']);
            $status = intval($_POST['status']);
            $guest_remark = trim($_POST['guest_remark']);
            $weixiu_info = trim($_POST['weixiu_info']);
            if(empty($record_id) && empty($status)){
                showmessage(lang('_error_action_'),"",0);
            }
            $data['record_id'] = $record_id;
            $data['status'] = $status;
            $data['update_time'] = time();
            if(isset($guest_remark)) {
                $data['guest_remark'] = $guest_remark;
            }
            if(isset($weixiu_info)) {
                $data['weixiu_info'] = $weixiu_info;
            }
            $result = $this->weixiu->update($data);
            if ($result === false) {
                showmessage("更新失败");
            }
            showmessage(lang('_operation_success_'));
        } else {
            $this->load->librarys('View')
                ->assign('record_id', $record_id)
                ->assign('status',$status)
                ->display('alert_weixiu');
        }
    }

    /**
     * 添加维修记录
     */
    public function create_record(){
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------
        $params = filter_array($_GET,[
            'order_id' => 'required|is_id',
        ]);
        if( count($params)!=1 ){
            echo_div("参数错误");
        }

        if (checksubmit('dosubmit')) {
            $order_info = $this->order_service->get_order_info(['order_id'=>intval($_POST['order_id'])]);
            if($order_info){
                $_POST['order_no'] = $order_info['order_no'];
                $_POST['user_id'] = $order_info['user_id'];
            }

            $result = $this->weixiu->create($_POST);
            if ($result === false) {
                echo_div('录入维修记录失败:'.  get_error());
            }

            // 操作日志
            if (defined('IN_ADMIN')) {
                $admin_id = (int) ADMIN_ID;
                $operator = model("admin/admin_user")->where(array('id' => $admin_id))->find();
                $operator['operator_type'] = 1;	// 操作者类型：管理员
                $operator['_operator_type'] = '管理员';
            } else {
                $operator = model('member/member','service')->init();
                $operator['operator_type'] = 2;	// 操作者类型：会员
                $operator['_operator_type'] = '会员';
            }
            $log=[
                'order_no'=>$order_info['order_no'],
                'action'=>"录入维修记录",
                'operator_id'=>$operator['id'],
                'operator_name'=>$operator['username'],
                'operator_type'=>$operator['operator_type'],
                'msg'=>'录入维修记录成功',
            ];
            $add_log = $this->service_order_log->add($log);
            if(!$add_log){
                echo_div("插入日志失败");
            }
            echo_json(1,lang('_operation_success_'));
        } else {
            $this->load->librarys('View')
                ->assign('order_id', $params['order_id'])
                ->display('create_record');
        }

    }
}