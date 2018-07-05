<?php

use zuji\order\DeliveryStatus;
use zuji\Business;
use zuji\debug\Location;
use zuji\order\OrderStatus;
use zuji\Config;
use zuji\order\delivery\Delivery;
use zuji\order\Order;
use zuji\debug\Debug;
use zuji\email\EmailConfig;

hd_core::load_class('base', 'order2');

class sms_control extends base_control
{

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'user_mobile' =>'手机号',
        'sms_no' =>'短信编号',
    ];
    public function _initialize()
    {
        parent::_initialize();
        $this->sms_service = $this->load->service('order2/sms');
    }

    /**
     * 短信发送列表
     */
    public function index()
    {
        $where = array();

        if ($_GET['keywords'] != '') {
            if ($_GET['kw_type'] == 'user_mobile') {
                $where['user_mobile'] = $_GET['keywords'];
            }elseif($_GET['kw_type'] == 'sms_no'){
                $where['sms_no'] = $_GET['keywords'];
            }else{
                $where['order_no'] = $_GET['keywords'];
            }
        }

        if($_GET['begin_time']!='' ){
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if( $_GET['end_time']!='' ){
            $where['end_time'] = strtotime($_GET['end_time']);
        }

        //权限判断
        $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $count = $this->sms_service->get_count($where);
        $pages = $this->admin_pages($count, $size);
        $additional = [
            'page' => $_GET['page'],
            'size' => $size,
            'orderby' => 'create_time DESC',
        ];
        $sms_list = $this->sms_service->get_list($where, $additional);
        foreach ($sms_list as $k => &$item) {
            $item['create_time_show'] = date('Y-m-d H:i:s', $item['create_time']);
        }

            $lists = array(
                'th' => array(
                    'user_mobile' => array('length' => 20, 'title' => '用户手机号'),
                    'sms_no' => array('title' => '短信编号', 'length' => 20),
                    'order_no' => array('length' => 20, 'title' => '订单编号'),
                    'create_time_show' => array('length' => 20, 'title' => '创建时间'),
                ),
                'lists' => $sms_list,
                'pages' => $pages,
            );


            $this->load->librarys('View')
                ->assign('keywords_type_list', $this->keywords_type_list)
                ->assign('lists', $lists)->assign('pages', $pages)->display('sms_index');

    }

    public function detail(){
        $id = $_GET['id'];
        if($id <1){
            showmessage('参数错误');
        }
        //
        $info = $this->sms_service->get_info($id);
        if( !$info ){
            showmessage('查询失败');
        }
        $data = json_decode($info['json_data'],true);

        $info['sms_name']= \zuji\sms\Sms::getSmsAllName($info['sms_no']);
        $info['sms_name']= str_replace('${goodsName}',$data['goodsName'],$info['sms_name']);
        $info['sms_name']= str_replace('${orderNo}',$data['orderNo'],$info['sms_name']);
        $info['sms_name']= str_replace('${logisticsNo}',$data['logisticsNo'],$info['sms_name']);
        $info['sms_name']= str_replace('${serviceTel}',$data['serviceTel'],$info['sms_name']);
        $info['sms_name']= str_replace('${returnAddress}',$data['returnAddress'],$info['sms_name']);
        $info['sms_name']= str_replace('${code}',$data['code'],$info['sms_name']);
        $info['sms_name']= str_replace('${storeName}',$data['storeName'],$info['sms_name']);
        $info['sms_name']= str_replace('${userName}',$data['userName'],$info['sms_name']);
        $info['sms_name']= str_replace('${daikouSum}',$data['daikouSum'],$info['sms_name']);
        $info['sms_name']= str_replace('${koukuanTime}',date("Y-m-d H:i:s",$data['koukuanTime']),$info['sms_name']);
        $info['sms_name']= str_replace('${realName}',$data['realName'],$info['sms_name']);
        $info['sms_name']= str_replace('${zuQi}',$data['zuQi'],$info['sms_name']);
        $info['sms_name']= str_replace('${zuJin}',$data['zuJin'],$info['sms_name']);
        $info['sms_name']= str_replace('${beginTime}',$data['beginTime'],$info['sms_name']);
        $info['sms_name']= str_replace('${endTime}',$data['endTime'],$info['sms_name']);
        $info['sms_name']= str_replace('${realName}',$data['realName'],$info['sms_name']);
        $info['sms_name']= str_replace('${shoujianrenName}',$data['shoujianrenName'],$info['sms_name']);
        $info['sms_name']= str_replace('${createTime}',$data['createTime'],$info['sms_name']);
        $info['sms_name']= str_replace('${buchangGift}',$data['buchangGift'],$info['sms_name']);
        $info['sms_name']= str_replace('${jieRi}',$data['jieRi'],$info['sms_name']);
        $info['sms_name']= str_replace('${yanchiZhouqi}',$data['yanchiZhouqi'],$info['sms_name']);
        $info['sms_name']= str_replace('${zidongQuxiao}',$data['zidongQuxiao'],$info['sms_name']);

        $this->load->librarys('View')
            ->assign('info',$info)->display('sms_detail');
    }
    public function response_detail(){
        $id = $_GET['id'];
        if($id <1){
            showmessage('参数错误');
        }
        $info = $this->sms_service->get_info($id);
        if( !$info ){
            showmessage('查询失败');
        }
        $info['sms_name'] = $info['response'];

        $this->load->librarys('View')
            ->assign('info',$info)->display('sms_detail');
    }


    public function active(){

        $this->code_service = $this->load->service('order2/sms_code');
        $this->sms_code_table = $this->load->table('order2/sms_code');
//
////        $path = 'http://localhost/Zuji/statics/active.csv';
//        $path = 'https://admin-zuji.huishoubao.com/statics/active.csv';
//        $list = $this->read_csv($path);
        $snineLianjie = "http://t.tl/1iy8d";

        $page = !empty($_GET['page']) ? $_GET['page'] : 1;
        $page = $page - 1;

        $size = 100;
        $limit = $page * $size;

        $list = $this->sms_code_table->field('id,mobile,code')->limit($limit,$size)->select();
        $sms = new \zuji\sms\HsbSms();

        foreach ($list as $k=>$v){

            if($v['code'] == 0){
                $mobile = $v['mobile'];
                $b = $sms->send_sms($mobile,'hsb_sms_eed44',[
                    'snineLianjie' => $snineLianjie,
                ]);

                if($b === true){
                    $this->sms_code_table->where(['mobile'=>$mobile])->save(['code'=>1]);
                }
            }
        }
    }


    function read_csv($cvs) {
        $shuang = false;
        $str = file_get_contents($cvs);
        for ($i=0;$i<strlen($str);$i++) {
            if($str{$i}=='"') {
                if($shuang) {
                    if($str{$i+1}=='"') {
                        $str{$i} = '*';
                        $str{$i+1} = '*';
                    } else {
                        $shuang = false;
                    }
                } else {
                    $shuang = true;
                }
            }
            if($str{$i}==',') {
                if($shuang) {
                } else {
                    $str{$i} = '|';
                }
            }
            if($str{$i}=="\n") {
                if($shuang) {
                    $str{$i} = '^';
                } else {
                }
            }
        }
        $str = str_replace(array('"','*'),array('','"'),$str);
        $a1 = explode("\n",$str);
        $array = array();
        foreach($a1 as $k=>$value) {
            if($value) {
                $value = str_replace("^","\n",$value);
                $array[$k] = explode("|",$value);
            }
        }
        return $array;
    }
}
