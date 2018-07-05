<?php

use zuji\payment\FundAuth;

hd_core::load_class('init', 'admin');

class deposit_control extends init_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [

    ];

    public function _initialize() {
        parent::_initialize();
        // $this->deposit_service  = $this->load->service('payment/deposit');
        // $this->deposit_table    = $this->load->table('payment/deposit');
        // $this->paystyle_service = $this->load->service('payment/payment_style');
    }

    /**
     * 押金管理表
     */
    public function index() {
        $this->deposit_service  = $this->load->service('payment/deposit');
        $this->paystyle_service = $this->load->service('payment/payment_style');

        $where = [];
        $additional = ['page' => 1, 'size' => 20];

        $limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $additional['page'] = intval($_GET['page']);
        $additional['size'] = intval($limit);
        // 查询

        $count = $this->deposit_service->get_count($where, $additional);

        $deposit_list = [];
        if ($count > 0) {
            // 授权列表查询
            $deposit_list = $this->deposit_service->get_list($where, $additional);
            foreach ($deposit_list as $key=>&$item) {
                // 格式化状态
                $payment = $this->paystyle_service->modelId($item['payment_style_id']);
                $item['payment_style_show'] = !empty($payment['pay_name']) ? $payment['pay_name'] : "无";
            }
        }
        $lists = array(
            'th' => array(
                'deposit_name' => array('length' => 30, 'title' => '押金名称'),
                'payment_style_show' => array('title' => '支付方式', 'length' => 30),
                'is_open' => array('title' => '是否启用', 'style' => 'ico_open', 'length' => 30),
            ),
            'lists' => $deposit_list,
            'pages' => $this->admin_pages($count, $additional['size']),
        );

        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->display('deposit');
    }

    /**
     * [deposit_add 押金编辑]
     * @return [type] [description]
     */
    public function deposit_add() {
        $this->deposit_service  = $this->load->service('payment/deposit');
        $this->paystyle_service = $this->load->service('payment/payment_style');

        $id = (int) $_GET['id'];
        if (checksubmit('dosubmit')) {
            $_POST['admin_id'] = $this->admin['id'];
            $result = $this->deposit_service->save_params($_POST);
            if($result === false){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        } else {
            $deposit = array();
            if ($id > 0) {
                $deposit = (array) $this->deposit_service->get_info($id);
                $payment = $this->paystyle_service->modelId($deposit['payment_style_id']);

                $deposit['pay_name'] = $payment['pay_name'];
            }

            // 支付方式
            $options['order'] = 'id desc';
            $payment = $this->paystyle_service->arrListByPage(0, 0, ['status'=>1],$options);

            $this->load->librarys('View')
                ->assign('deposit', (array) $deposit)
                ->assign('payment', (array) $payment['rows'])
                ->display('deposit_add');
        }
    }




    // 开启/停用
    public function set_disable(){
        $this->deposit_table    = $this->load->table('payment/deposit');

        $id         = trim($_POST['id']);

        if($id < 0){
            showmessage('参数错误', 'null');
        }
        $data = [];
        $data['is_open']=array('exp',' 1-is_open ');
        $result = $this->deposit_table->where(['id' => $id])->save($data);
        // 查询分期信息
        if( !$result ){
            showmessage('修改停用错误', 'null');
        }
        showmessage('修改成功', 'null', 1);
    }


    public function ajax_machine(){
        $this->deposit_service  = $this->load->service('payment/deposit');
        $result = $this->deposit_service->ajax_machine($_GET['name']);
        if (!$result) {
            showmessage("查询信息错误", '', 0, '', 'json');
        } else {
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
    }



}
