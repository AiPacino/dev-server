<?php

use zuji\payment\FundAuth;

hd_core::load_class('init', 'admin');

class credit_control extends init_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [

    ];

    public function _initialize() {
        parent::_initialize();
        // $this->credit_service  = $this->load->service('payment/credit');
        // $this->credit_table    = $this->load->table('payment/credit');
    }

    public function index() {
        $this->credit_service  = $this->load->service('payment/credit');
        $where = [];
        $additional = ['page' => 1, 'size' => 20];
        $limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $additional['page'] = intval($_GET['page']);
        $additional['size'] = intval($limit);
        // 查询

        $count = $this->credit_service->get_count($where, $additional);

        $credit_list = [];
        if ($count > 0) {
            // 授权列表查询
            $credit_list = $this->credit_service->get_list($where, $additional);
            foreach ($credit_list  as $k=>&$item) {
                // 格式化状态
                $item['is_open_show']    = $item['is_open']      == 1 ? "已开启" : "未开启";
                $item['score_fanwei'] = $item['min_credit_score']."~".$item['max_credit_score'];
            }
        }
        $lists = array(
            'th' => array(
                'credit_name' => array('title' => '信用名称', 'length' => 30,'style'=>"double_click"),
                'score_fanwei' => array('title' => '分数范围', 'length' => 30),
                'is_open_show' => array('title' => '是否启用', 'length' => 20,'style' => 'ico_up_rack'),
            ),
            'lists' => $credit_list,
            'pages' => $this->admin_pages($count, $additional['size']),
        );

        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->display('credit_index');
    }

    public function edit(){
        $this->credit_service  = $this->load->service('payment/credit');
        $this->credit_table    = $this->load->table('payment/credit');

        if(checksubmit('dosubmit')) {
            $id = (int) $_POST['id'];
            if($id > 0){
                $result = $this->credit_table->where(['id'=>$id])->save($_POST);
            }else{
                $result = $this->credit_table->add($_POST);
            }
            if($result === false){
                showmessage("保存失败");
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $id = (int) $_GET['id'];
            $info = [];
            if($id > 0){
                $info = $this->credit_service->get_info($id);
            }
            $this->load->librarys('View')->assign('info',$info)->display('credit_edit');
        }
    }


    /**
     * [ajax_status 更改is_open状态]
     */
    public function ajax_status(){
        $this->credit_service  = $this->load->service('payment/credit');
        $this->credit_table    = $this->load->table('payment/credit');

        $id = (int) $_POST['id'];
        if($id <1){
            showmessage("参数错误",'',0,'','json');
        }
        $info = $this->credit_service->get_info($id);
        $is_open =$info['is_open']?0:1;
        $result = $this->credit_table->where(['id'=>$id])->save(['is_open'=>$is_open]);
        if($result === false){
            showmessage("修改失败",'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

    /**
     * [ajax_name ajax更改信用名称]
     */
    public function ajax_name(){
        $this->credit_table    = $this->load->table('payment/credit');
        
        $id = (int)$_POST['id'];
        if($id <1){
            showmessage("参数错误",'',0,'','json');
        }
        $result = $this->credit_table->where(['id'=>$id])->save(['credit_name'=>$_POST['name']]);
        if($result === false){
            showmessage("修改失败",'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }


}
