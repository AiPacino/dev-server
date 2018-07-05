<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/17 0017-上午 11:02
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('init', 'admin');
class payment_style_control extends init_control
{


    public function _initialize(){
        parent::_initialize();
        $this->service = $this->load->service('payment_style');
        $this->credit_service = $this->load->service('payment/credit');
    }

    public function index(){
        $where = [];
        $options['order'] = 'id desc';
        $result = $this->service->arrListByPage(0, 0, $where, $options);
        foreach ($result['rows'] as &$row){
            $credit= $this->credit_service->get_info($row['credit_id']);
            $row['credit_name'] =$credit['credit_name']?$credit['credit_name']:'--';
            $row['isdefault'] = $row['isdefault'] ? '是' : '否';
        }
        $lists = array(
            'th' => array(
                'pay_name' => array('style' => 'double_click','title' => '支付名称','length' => 20),
                'detail_pay_style' => array('length' => 30,'title' => '详细支付方式'),
                'credit_name' => array('title' => '信用名称','length' => 20),
                'status' => array('title' => '是否启用','style' => 'ico_up_rack','length' => 10),
                'isdefault' => array('title' => '是否设为默认','length' => 10),
            ),
            'lists' => $result['rows'],
            'pages' => '',
        );

        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->display('payment_style_list');
    }

    public function edit(){
        $id = (int) $_GET['id'];
        if(checksubmit('dosubmit')) {
            $params = $_POST;
            $result = $this->service->edit_params($id, $params);
            if($result === false){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $info = [];
            if($id > 0){
                $info = $this->service->modelId($id);
            }

            $credits = $this->credit_service->getField('id,credit_name');
            $this->load->librarys('View')->assign('info',$info)->assign('credits',$credits)->display('payment_style_edit');
        }
    }

    /**
     * [ajax_status 更改状态]
     */
    public function ajax_status(){
        $result = $this->service->enable($_GET['id']);
        if($result === false){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

    /**
     * [ajax_name ajax更改名称]
     * @return [type] [description]
     */
    public function ajax_name(){
        $id = $_POST['id'];
        $params['pay_name'] = $_POST['name'];
        $result = $this->service->change_info($id, $params);
        if($result === false){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

}