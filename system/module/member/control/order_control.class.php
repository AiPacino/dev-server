<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
hd_core::load_class('init', 'goods');
class order_control extends init_control {

    const _KEY = '8oxq1kma0eli9vlxnyj8v7qk335uvrf0';  // 密钥
    public function _initialize() {
        parent::_initialize();
        if($this->member['id'] < 1) {
			redirect(url('cp/index',array('url_forward'=>urlencode($_SERVER['REQUEST_URI']))));
		}
        $this->table = $this->load->table('order/order');
        $this->table_sub = $this->load->table('order/order_sub');
        $this->service = $this->load->service('order/order');
        $this->service_sub = $this->load->service('order/order_sub');
        $this->service_track = $this->load->service('order/order_track');
        $this->member_table = $this->load->table('member/member');
        $this->member_service = $this->load->service('member/member');
        helper('order/function');
    }

    /* 我的订单 */
    public function index() {
    	// 查询条件
		$sqlmap = array();
		$sqlmap = $this->service->build_sqlmap($_GET);
		$sqlmap['buyer_id'] = $this->member['id'];
        if (isset($_GET['sn'])) $sqlmap['sn'] = array('LIKE','%'.$_GET['sn'].'%');
		if (!isset($_GET['type'])) $sqlmap['status'] = array('IN','1,2');
		$limit  = (isset($_GET['limit'])) ? $_GET['limit'] : 5;
        $orders = $this->service->fetch($sqlmap, $limit, $_GET['page'], 'id DESC');
        $count  = $this->service->count($sqlmap);
        $setting = $this->load->service('admin/setting')->get();
        $pages  = pages($count,$limit);
        $SEO = seo('我的订单 - 会员中心');
        $this->load->librarys('View')->assign('setting',$setting)->assign('orders',$orders)->assign('SEO',$SEO)->assign('pages',$pages)->display('my_order');
    }

    /* 订单详情 */
    public function detail() {
        $o_d_id = remove_xss($_GET['o_d_id']);
        $detail = $this->service_sub->sub_detail($_GET['sub_sn'] ,$o_d_id);
        if (!$detail) showmessage(lang('order_not_exist','order/language'));
        //更新跟踪物流
        if($detail['delivery_status'] > 0 && $o_d_id > 0){
            $this->service_track->update_api100($detail['sub_sn'],$o_d_id);
            $detail = $this->service_sub->sub_detail($_GET['sub_sn'] ,$o_d_id);
        }
        $detail['_member'] = $this->load->table('member/member')->find($detail['buyer_id']);
        $detail['_main'] = $this->service->member_table_detail($detail['order_sn']);
        // 是否显示子订单号信息
        $detail['_showsubs'] = (count($detail['_main']['_subs']) > 1) ? TRUE : FALSE;
        $setting = $this->load->service('admin/setting')->get();
        $SEO = seo('订单详情 - 会员中心');
        $this->load->librarys('View')->assign('detail',$detail)->assign('SEO',$SEO)->assign('setting',$setting)->display('order_detail');
    }

    /* 取消订单 */
    public function cancel() {
        if (checksubmit('dosubmit')) {
            $sub_sn = remove_xss($_GET['sub_sn']);
            $order = $this->service_sub->find(array('sub_sn' => $sub_sn), 'buyer_id,order_sn');
            if ($order['buyer_id'] != $this->member['id']) {
                showmessage(lang('no_promission_operate_order','member/language'));
            }
            $result = $this->service_sub->set_order($sub_sn ,$action = 'order',$status = 2 ,array('msg'=>'用户取消订单','isrefund' => 1));
            if (!$result) showmessage($this->service_sub->error);
            model('order/order_trade')->where(array('order_sn'=>$order['order_sn']))->setField('status',-1);
            showmessage(lang('cancel_order_success','order/language'),'',1,'json');
        } else {
            showmessage(lang('_error_action_'));
        }
    }

    /* 放入回收站 */
    public function recycle() {
        if (checksubmit('dosubmit')) {
            $sn = remove_xss($_GET['sn']);
            $order = $this->service->find(array('sn' => $sn), 'member_id');
            if ($order['member_id'] != $this->member['id']) showmessage(lang('no_promission_operate_order','member/language'));
            $result = $this->service->set_order($sn ,$action = 'order',$status = 3 ,array('msg'=>'订单放入回收站'));
            if (!$result) showmessage($this->service->error);
            showmessage(lang('已放入回收站'),'',1,'json');
        } else {
            showmessage(lang('_error_action_'));
        }
    }

    /* 删除订单 */
    public function delete_sn() {
        if (checksubmit('dosubmit')) {
            $sn = remove_xss($_GET['sn']);
            $order = $this->service->find(array('sn' => $sn), 'member_id');
            if ($order['member_id'] != $this->member['id']) showmessage(lang('no_promission_operate_order','member/language'));
            $result = $this->service->set_order($sn ,'order', 4 ,array('msg'=>'用户删除订单'));
            if (!$result) showmessage($this->service->error);
            showmessage(lang('删除订单成功'),'',1,'json');
        } else {
            showmessage(lang('_error_action_'));
        }
    }

    /* 确认收货 */
    public function finish() {
        if (checksubmit('dosubmit')) {
            $sub_sn = remove_xss($_GET['sub_sn']);
            $order = $this->service_sub->find(array('sub_sn' => $sub_sn), 'buyer_id');
            if ($order['buyer_id'] != $this->member['id']) showmessage(lang('no_promission_operate_order','member/language'));
            $data = array();
            $data['msg'] = '确认订单商品收货';
            $data['o_delivery_id'] = remove_xss($_GET['o_d_id']);
            $result = $this->service_sub->set_order($sub_sn ,'finish',1 ,$data);
            if (!$result) showmessage($this->service_sub->error);
            showmessage(lang('确认订单商品收货成功'),'',1,'json');
        } else {
            showmessage(lang('_error_action_'));
        }
    }

    /* wap查看物流 */
    public function delivery() {
        $o_d_id = (int) $_GET['o_d_id'];
        $order_delivery = $this->load->service('order/delivery')->order_delivery_find(array('id' => $o_d_id));
        if (!$order_delivery) return FALSE;
        //更新物流跟踪
        if($o_d_id > 0){
            $this->service_track->update_api100($order_delivery['sub_sn'],$o_d_id);
        }
        $info = array();
        $info['delivery'] = $this->load->service('order/delivery')->find(array('id' => $order_delivery['delivery_id']));
        $info['tracks'] = $this->service_track->get_tracks_by_sn($order_delivery['sub_sn']);
        $SEO = seo('查看物流 - 会员中心');
        $this->load->librarys('View')->assign('SEO',$SEO)->assign('order_delivery',$order_delivery)->assign('info',$info)->display('track');
    }

    /* 获取订单列表 */
    public function get_orders() {
        // 查询条件
        $sqlmap = array();
        $sqlmap = $this->service->build_sqlmap($_GET);
        if(empty($_GET['type'])){
            $_GET['type'] = $_GET['map']['type'];
        }
        $sqlmap['buyer_id'] = $this->member['id'];
        if (isset($_GET['sn'])) $sqlmap['sn'] = array('LIKE','%'.$_GET['sn'].'%');
        if (!isset($_GET['type'])) $sqlmap['status'] = array('IN','1,2');
        $limit  = (isset($_GET['limit'])) ? $_GET['limit'] : 10;
        $data['orders'] = $this->service->fetch($sqlmap, $limit, $_GET['page'], 'id DESC');
        $data['count']  = $this->service->count($sqlmap);
        $data['pages']  = pages($data['count'],$limit);
        $this->load->librarys('View')->assign('data',$data);
        $data = $this->load->librarys('View')->get('data');
        echo json_encode($data);
    }

    /*
        *支付宝授权
        *
        *
        */

    public  function  authorize()
    {
        //echo '1111';exit;
        $data = $_GET;
        //调用支付宝的接口
        ////////////////////////
        $re = array(
            //"code"=> "10000",
            // "msg"=>"Success",
            "user_id"=> "2088102104794936",
            "avatar"=>"http://tfsimg.alipay.com/images/partner/T1uIxXXbpXXXXXXXX",
            "province"=> "安徽省",
            "city"=> "安庆",
            "nick_name"=> "支付宝小二",
            "is_student_certified"=> "T",
            "user_type"=>"1",
            "user_status"=>"T",
            "is_certified"=> "T",
            "gender"=>"F",
        );
        $sign = "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE";
        $feeddata = array();
        $feeddata['alipay_user_info_share_response'] = $re ;
        $feeddata['sign'] = $sign ;
        session('user_id',$re['user_id']);
         $result = $this->member_table->where("alipay_user_id={$re['user_id']}")->find();
        //if($result[["alipay_user_id"]]==0)
        if(1==1)
        {
            $sign = $this->member_service->savedata($feeddata['alipay_user_info_share_response']);

        }
        return $result;

    }
    /*
     * 支付宝身份认证
     */
    public function identity()
    {
        $data = json_decode($_GET);
        //if($data['code']==1000)
        if(1==1)
        {
            //$re=json_decode($data);
            $re = array(
                "zuji_no"=> "10000",
                "channel_name"=>"芝麻信用",
                "cert_type"=> "IDENTITY_CARD",
                "cert_name"=>"**星",
                "cert_no"=> "130423********1234",
            );

            $sign = $this->member_service->save_identity_data($re);

        }

    }

    /*
    * 支付宝信用分保存
    */
   public   function credit()
   {
       $data = json_decode($_GET);
       //if($data['code']==1000){
       if(1==1){
       $re = array(
           "zuji_no"=> "10000",
           "channel_name"=>"芝麻信用",
           "cert_type"=> "IDENTITY_CARD",
           "credit_score"=>763,
       );
       $sign = $this->member_service->save_credit_data($re);

       }

   }
    /*
         *短信接口
         *
         *
         */
    public  function  mobile_messge()
    {
        $head = array(
            'version' => '0.01',
            'msgtype' => 'request',
            'interface' => 'send',
            'remark' => ' ',
        );
        $para = array(
            'system' => 'HSB',
            'time' => time(),
            'scene' =>"login",
            'phones' =>'18510789716',
        );
        $data = array();
        $data['head'] = $head;
        $data['params'] = $para;
        $merge_data = array_merge($head,$para);
        $re = self::sign2($merge_data);
        $data['params']['sign'] = $re;
        //include_once( APP_PATH.'library'.DIRECTORY_SEPARATOR.'Curl.class.php');
        include_once( APP_PATH.'library'.'/'.'Curl.class.php');
        $model= new Curl();
       // var_dump($model);exit;
        $url = "http://dev-psl-server.huanjixia.com/service/captcha";
        //var_dump(json_encode($data));exit;
        $result =$model->post($url,json_encode($data));
        //$result = $this->post($url,json_encode($data));
        $result = json_decode($result,true);
        $userInfo = $result['body'];
        return $userInfo;
    }

    /**
     * sign加密规则
     * @author wq
     */
    static private  function sign2( $param )
    {
        $sign = "";
        ksort($param);
        foreach( $param as $k=>$v )
        {
            if(!is_array($v) and $param[$k]!='')
            {
                $sign .= $k.'='.$v.'&';
            }
        }
        $sign = strtolower( md5($sign.'key='.self::_KEY) );
        return $sign;
    }

    public  function execute($method, $url, $fields = '', $userAgent = '', $httpHeaders = '', $username = '', $password = '') {
        $ch = $this->create();
        if (false === $ch) {
            return false;
        }
        if (is_string($url) && strlen($url)) {
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            return false;
        }
        //是否显示头部信息
        curl_setopt($ch, CURLOPT_HEADER, false);
        //
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($username != '') {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        }

        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $method = strtolower($method);
        if ('post' == $method) {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($fields)) {
                $sets = array();
                foreach ($fields AS $key => $val) {
                    $sets[] = $key . '=' . urlencode($val);
                }
                $fields = implode('&', $sets);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else if ('put' == $method) {
            curl_setopt($ch, CURLOPT_PUT, true);
        }
        //curl_setopt($ch, CURLOPT_PROGRESS, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //curl_setopt($ch, CURLOPT_MUTE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); //设置curl超时秒数
        if (strlen($userAgent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }
        if (is_array($httpHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }
        $ret = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return array(curl_error($ch), curl_errno($ch));
        } else {
            curl_close($ch);
            if (!is_string($ret) || !strlen($ret)) {
                return false;
            }
            return $ret;
        }
    }
    public function post($url, $fields, $userAgent = '', $httpHeaders = '', $username = '', $password = '') {
        $ret = $this->execute('POST', $url, $fields, $userAgent, $httpHeaders, $username, $password);
        if (false === $ret) {
            return false;
        }
        if (is_array($ret)) {
            return false;
        }
        return $ret;
    }
    public function create() {
        $ch = null;
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init();
        if (!is_resource($ch)) {
            return false;
        }
        return $ch;
    }

}