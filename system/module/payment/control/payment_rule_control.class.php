<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/17 0017-上午 11:05
 * @copyright (c) 2017, Huishoubao
 */

hd_core::load_class('init', 'admin');
class payment_rule_control extends init_control
{

    public function _initialize(){
        parent::_initialize();
        // $this->service = $this->load->service('payment/payment_rule');
        // $this->channel_service = $this->load->service('channel/channel');
        // $this->credit_service = $this->load->service('credit');
        // $this->deposit_service = $this->load->service('deposit');
        // $this->payment_style_model = $this->load->service('payment_style');
        // $this->rule_channel_service = $this->load->service('payment_rule_channel');
        // $this->rule_detail_service = $this->load->service('payment_rule_detail');
    }

    public function index(){
        $this->service = $this->load->service('payment/payment_rule');
        $this->credit_service = $this->load->service('credit');
        $this->deposit_service = $this->load->service('deposit');
        $this->payment_style_model = $this->load->service('payment_style');
        $this->rule_channel_service = $this->load->service('payment_rule_channel');
        $this->rule_detail_service = $this->load->service('payment_rule_detail');

        $where = [];
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $options['order'] = 'id desc';
        $result = $this->service->arrListByPage($page, $limit, $where, $options);
        foreach ($result['rows'] as $k => &$row){
            //支付方式
            $payment_style = $this->payment_style_model->modelId($row['payment_style_id']);
            $row['payment_style_name'] = $payment_style ? $payment_style['pay_name'] : '';

            //信用
            $credit = $this->credit_service->get_info($row['credit_id']);
            $row['credit_name'] = $credit ? $credit['credit_name'] : '';

            //押金
            $yajin = $this->deposit_service->get_info($row['yajin_id']);
            $row['yajin_name'] = $yajin ? $yajin['deposit_name'] : '';

            //渠道
            $channel_list = $this->rule_channel_service->get_channel_list($row['id']);
            $row['channel_name'] = implode('|', array_column($channel_list, 'name'));

            //具体规则
            $rule_detail_list = $this->rule_detail_service->get_list_by_ruleid($row['id']);
            $rule_text = '';
            foreach ($rule_detail_list as $item){
                $rule_text .= '信用分：' . $item['credit_down'] . '~' . $item['credit_up'].'，';
                $rule_text .= '年龄：' . $item['age_down'] . '~' . $item['age_up'].'，';
                if($item['yajin_type'] == 1){
                    $rule_text .= '押金：' . $item['relief_amount'] . '元，';
                }else{
                    $rule_text .= '押金：' . $item['relief_amount'] . '%，';
                }
                $rule_text .= '减压上限：' . $item['max_amount'] . '元<br/>';

            }

            $row['rule_detail'] = $rule_text;
        }
        $lists = array(
            'th' => array(
                'name' => array('style' => 'double_click','title' => '规则名称','length' => 10),
                'payment_style_name' => array('title' => '支付方式','length' => 10),
                'credit_name' => array('title' => '信用管理','length' => 10),
                'yajin_name' => array('title' => '押金管理','length' => 10),
                'channel_name' => array('title' => '所属渠道','length' => 20),
                'rule_detail' => array('title' => '具体规则','length' => 25),
                'status' => array('title' => '是否启用','style' => 'ico_up_rack','length' => 5),
            ),
            'lists' => $result['rows'],
            'pages' => $this->admin_pages($result['total'], $limit),
        );

        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->display('payment_rule_list');
    }

    public function edit(){
        $this->service = $this->load->service('payment/payment_rule');
        $this->channel_service = $this->load->service('channel/channel');
        $this->credit_service = $this->load->service('credit');
        $this->deposit_service = $this->load->service('deposit');
        $this->payment_style_model = $this->load->service('payment_style');
        $this->rule_channel_service = $this->load->service('payment_rule_channel');

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
            $credit_text = $yajin_text = '';
            if($id > 0){
                $info = $this->service->get_info($id, 'channel,rule_detail');
                $payment_id = $info['rule']['payment_style_id'];
                if($payment_id){
                    $credit_list = $this->credit_service->get_info_by_payment($payment_id);
                    $yajin_list = $this->deposit_service->get_list_by_payment($payment_id);
                    foreach ($credit_list as $credit){
                        if($credit['id'] == $info['rule']['credit_id']){
                            $credit_text .= '<option value="'.$credit['id'].'" selected>'.$credit['credit_name'].'</option>';
                        }else{
                            $credit_text .= '<option value="'.$credit['id'].'">'.$credit['credit_name'].'</option>';
                        }

                    }

                    foreach ($yajin_list as $yajin){
                        if($yajin['id'] == $info['rule']['yajin_id']) {
                            $yajin_text .= '<option value="' . $yajin['id'] . '" selected>' . $yajin['deposit_name'] . '</option>';
                        }else{
                            $yajin_text .= '<option value="' . $yajin['id'] . '">' . $yajin['deposit_name'] . '</option>';
                        }
                    }
                }

            }

            //支付列表
            $payment_list = $this->payment_style_model->arrListByPage(0, 0, ['status' => 1]);
            $payments = [0=>'请选择支付方式'];
            if($payment_list['rows']){
                foreach ($payment_list['rows'] as $item){
                    $payments[$item['id']] = $item['pay_name'];
                }
            }

            //渠道列表
            $channel_list = $this->channel_service->get_list(['status' => 1]);
            $channels = [];
            foreach ($channel_list as $item){
                $channels[$item['id']] = $item['name'];
            }

            $this->load->librarys('View')
                ->assign('info',$info)
                ->assign('channel_list', $channels)
                ->assign('payment_list', $payments)
                ->assign('credit_text', $credit_text)
                ->assign('yajin_text', $yajin_text)
                ->display('payment_rule_edit');
        }
    }

    /**
     * [ajax_status 更改状态]
     */
    public function ajax_status(){
        $this->service = $this->load->service('payment/payment_rule');

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
        $this->service = $this->load->service('payment/payment_rule');

        $id = $_POST['id'];
        $params['name'] = $_POST['name'];
        $result = $this->service->change_info($id, $params);
        if($result === false){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

    /**
     * 根据支付类型获取信用、押金列表
     * @return string
     */
    public function ajax_get_credit_yajin_list(){
        $this->credit_service = $this->load->service('credit');
        $this->deposit_service = $this->load->service('deposit');
        
        $payment_style_id = $_POST['payment_style_id'];
        $credit_list = $this->credit_service->get_info_by_payment($payment_style_id);
        $credits = '<div class="form-group ">';
        $credits .= '<span class="label">使用信用<b style="color:red">*</b>：</span>';
        $credits .= '<div class="box">';
        $credits .= '<select class="input" id="credit" name="credit_id"><option value="0">请选择信用</option>';
        foreach ($credit_list as $item){
            $credits .= '<option value="'.$item['id'].'">'.$item['credit_name'].'</option>';
        }
        $credits .= '</select><span class="ico_buttonedit"></span>';
        $credits .= '</div><p class="desc">【必填】请选择信用。</p></div>';

        $yajin_list = $this->deposit_service->get_list_by_payment($payment_style_id);
        $yajins = '<div class="form-group ">';
        $yajins .= '<span class="label">使用押金<b style="color:red">*</b>：</span>';
        $yajins .= '<div class="box">';
        $yajins .= '<select id="yajin" class="input" name="yajin_id"><option value="0">请选择押金</option>';
        foreach ($yajin_list as $item){
            $yajins .= '<option value="'.$item['id'].'">'.$item['deposit_name'].'</option>';
        }
        $yajins .= '</select><span class="ico_buttonedit"></span>';
        $yajins .= '</div><p class="desc">【必填】请选择押金。</p></div>';

        echo $credits.'<br/>'.$yajins;
        exit();
    }
}