<?php

use zuji\payment\Withhold;
use zuji\payment\Withhold_notify;

hd_core::load_class('init', 'admin');
class withhold_control extends init_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'user_id' => '用户',
        'agreement_no' => '协议号'
    ];

    public function _initialize() {
        parent::_initialize();
        // $this->withhold_service = $this->load->service('payment/withhold');
        // $this->withhold_notify_service = $this->load->table('payment/withholding_notify_alipay');
    }

    public function index(){
        $this->withhold_service = $this->load->service('payment/withhold');

        $where = [];
        $additional = ['page'=>1,'size'=>20];
        // 查询条件
        if(isset($_GET['status'])){
            $where['status'] = intval($_GET['status']);
            if( $where['status'] == 0){
                unset($where['status']);
            }
        }


        if($_GET['keywords']!=''){
            if($_GET['kw_type']=='user_id'){
                $where['user_id'] = $_GET['keywords'];
            }
            elseif($_GET['kw_type']=='agreement_no'){
                $where['agreement_no'] = $_GET['keywords'];
            }
        }

        if(isset($_GET['begin_time'])){
            $where['sign_time'] = $_GET['begin_time'];
            if( !$where['sign_time'] ){
                unset($where['sign_time']);
            }
        }
        if(isset($_GET['end_time'])){
            $where['unsign_time'] = $_GET['end_time'];
            if( !$where['unsign_time'] ){
                unset($where['unsign_time']);
            }
        }

        // 查询

        $count = $this->withhold_service->get_count($where);

        $withhold_list = [];
        if( $count>0 ){
            // 支付宝代扣协议查询
            $withhold_list = $this->withhold_service->get_list($where,$additional);

            foreach ($withhold_list as &$item){
                // 格式化状态
                $item['auth_status_show'] = Withhold::getStatusName($item['status']);
            }
        }

        $data_table = array(
            'th' => array(
                'user_id' => array('length' => 8,'title' => '租机用户ID'),
                'partner_id' => array('title' => '合作者身份', 'length' => 10),
                'alipay_user_id' => array('title' => '支付宝用号', 'length' => 15),
                'agreement_no' => array('title' => '支付宝代扣协议号', 'length' => 15),
                'auth_status_show' => array('title' => '协议状态', 'length' => 10),
                'sign_time' => array('title' => '签约时间', 'length' => 10),
                'valid_time' => array('title' => '协议生效时间', 'length' => 10),
                'unsign_time' => array('title' => '解约时间', 'length' => 10),
            ),
            'record_list' => $withhold_list,
            'pages' => $this->admin_pages($count, $additional['size']),
        );

        // 头部 tab 切换设置
        $tab_list = [];
        $status_list = array_merge(['0'=>'全部'],Withhold::getStatusList());
        foreach( $status_list as $k=>$name ){
            $css = '';
            if ($_GET['status'] == $k){
                $css = 'current';
            }
            $url = self::current_url(array('status'=>$k));
            $tab_list[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
        }

        $this->load->librarys('View')
            ->assign('tab_list',$tab_list)
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('data_table', $data_table)
            ->display('withhold');
    }

    /**
     * 详情
     */
    public function detail() {
        $this->withhold_service = $this->load->service('payment/withhold');
        $this->withhold_notify_service = $this->load->table('payment/withholding_notify_alipay');

        $agreement_no   =  $_GET['agreement_no'] ;
        if(!$agreement_no || $agreement_no < 0){
            echo_div('参数错误！');
        }

        $withhold_info =  $this->withhold_service->get_info(['agreement_no'=>$agreement_no]);
        if(!$withhold_info){
            echo_div('协议不存在！');
        }
        $withhold_info['status'] =  Withhold::getStatusName($withhold_info['status']);

        $where['agreement_no'] = $agreement_no;
        // 通知列表
        $withhold_notify = $this->withhold_notify_service->get_list($where);

        $this->load->librarys('View')
            ->assign('withhold_info',$withhold_info)
            ->assign('withhold_notify',$withhold_notify)
            ->display('withhold_detail');
    }
  
}