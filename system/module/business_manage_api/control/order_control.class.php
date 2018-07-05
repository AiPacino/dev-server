<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/16 0016-下午 3:21
 * @copyright (c) 2017, Huishoubao
 */

use oms\Order;
use oms\state\State;
hd_core::load_class('user', 'offline_store_api');
class order_control extends user_control
{

    public function _initialize() {
        parent::_initialize();
        $this->userId = $this->member['id'];
    }
    //重构数组键名
    private function arrayKey($infos,$key){
        $retArr = array();
        if( $infos && count($infos) > 0 )
        {
            foreach( $infos as $info )
            {
                $retArr[ $info[ $key ] ] = $info;
            }
        }
        return $retArr;
    }
    //订单状态
    public function status(){
        $result = oms\state\State::getStatusAllList();
        $data = [];
        if($result){
            foreach($result as $key=>$val){
                $data[] = ['id'=>$key,'name'=>$val];
            }
        }
        api_resopnse( $data, ApiStatus::CODE_0 );
        return;
    }
    public function query_test(){
        $params   = $this->params;

        //过滤参数
        $params = filter_array($params,[
            "brand_id"  => "required",
            "order_status"  => "required",
            "begin_time"  => "required",
            "end_time"  => "required",
            "search_type"  => "required",
            "content"  => "required",
            "page"  => "required",
            "show_count"  => "required",
        ]);


        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->spu_serve    = $this->load->service('goods2/goods_spu');
        $this->sku_serve    = $this->load->service('goods2/goods_sku');

        if($params['brand_id']){

        }
        if($params['order_status']){
            $where['status'] = $params['order_status'];
        }
        if($params['begin_time']||$params['end_time']){
            $where['begin_time'] = $params['begin_time']?strtotime($params['begin_time']):strtotime(date("Y-m-d")." 00:00:00");
            $where['end_time'] = $params['end_time']?strtotime($params['end_time']." 23:59:59"):time();
        }
        if($params['search_type'] && $params['content']){
            if($params['search_type']==1){
                $where['order_no'] = $params['content'];
            }
            elseif($params['search_type']==2){
                $where['mobile'] = $params['content'];
            }
        }
        $data['where'] = $where;
        //门店id
        $request = api_request();
        $appid = (int)$request->getAppid();
        $shop = model('channel/channel_appid')->where(['channel_id' => $appid])->select();
        $shop = $this->arrayKey($shop,"id");

        $where['appid'] = ['IN', array_column($shop,"id")] ;
        //获取订单数
        $count = $this->order->get_order_count($where);
        $data['count'] = $count;
        if(!$count){
            api_resopnse( [], ApiStatus::CODE_0 );
            return;
        }
        //显示条数
        $options['size']   = $params['show_count']?$params['show_count']:20;
        //分页
        $total_page = ceil($data['count']/$options['size']);
        $options['page'] = $params['page']?intval($params['page']):1;
        $options['page'] = $options['page']>=$total_page?$total_page:$options['page'];
        $data['total_page'] = $total_page;
        $data['page'] =  $options['page'];

        //获取订单数据列表
        $options['goods_info'] = true;
        $order_list = $this->order ->get_order_list($where,$options);
        $order_list = $this->arrayKey($order_list,"order_id");
        if(!$order_list){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
        }
        $order_ids = array_column($order_list,'order_id');
        asort($order_ids);
        $additional['size'] = count($order_ids);
        $additional['page']  = 1;

        //组装数据格式
        $order_new = array();
        foreach($order_list as $key=>$val){
            $Order = new Order($val);
            //获取前端订单状态
            //$status_name =  $Order->get_client_name();
            $status_name = State::getStatusAllName($val['status']);
            $status_key =  $Order->get_status();

            $order_new[$key] = array(
                'shop_name' => $shop[$val['appid']]['name'],
                'create_time'  =>  date("Y-m-d H:i:s",$val['create_time']),
                'status_name' => $status_name,
                'status_key' => $status_key,
                'order_id'      => $val['order_id'],
                'order_no'      => $val['order_no'],
                'amount'        => $val['amount'],
                'mobile'        => $val['mobile'],
                'yajin'          => $val['yajin'],
                'mianyajin'     => $val['mianyajin'],
                'zujin'          => $val['zujin'],
                'yiwaixian'      => $val['yiwaixian'],
                'zuqi'           => $val['zuqi'],
                'user_info'       => array(
                    'username'     =>  $val['realname'],
                    'mobile'        =>  $val['mobile'],
                    'card'         =>  $val['cert_no'],
                ),
                'sku_info'       => array(
                    'sku_id'         =>  $val['goods_info']['sku_id'],
                    'sku_name'   =>  $val['goods_info']['sku_name'],
                    'imei'   =>  $val['goods_info']['imei1'],
                )
            );
        }
        array_multisort($order_new,SORT_DESC);
        $data['order_list'] = $order_new;
        api_resopnse( $data, ApiStatus::CODE_0 );
        return;
    }
    //订单列表
    public function query(){
        $params   = $this->params;

        //过滤参数
        $params = filter_array($params,[
            "brand_id"  => "required",
            "order_status"  => "required",
            "begin_time"  => "required",
            "end_time"  => "required",
            "search_type"  => "required",
            "content"  => "required",
            "page"  => "required",
            "show_count"  => "required",
        ]);


        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->spu_serve    = $this->load->service('goods2/goods_spu');
        $this->sku_serve    = $this->load->service('goods2/goods_sku');

        if($params['brand_id']){

        }
        if($params['order_status']){
            $where['status'] = $params['order_status'];
        }
        if($params['begin_time']||$params['end_time']){
            $where['begin_time'] = $params['begin_time']?strtotime($params['begin_time']):strtotime(date("Y-m-d")." 00:00:00");
            $where['end_time'] = $params['end_time']?strtotime($params['end_time']." 23:59:59"):time();
        }
        if($params['search_type'] && $params['content']){
            if($params['search_type']==1){
                $where['order_no'] = $params['content'];
            }
            elseif($params['search_type']==2){
                $where['mobile'] = $params['content'];
            }
        }
        $data['where'] = $where;
        //门店id
        $request = api_request();
        $appid = (int)$request->getAppid();
        $shop = model('channel/channel_appid')->where(['channel_id' => $appid])->select();
        $shop = $this->arrayKey($shop,"id");

        $where['appid'] = ['IN', array_column($shop,"id")] ;
        //获取订单数
        $count = $this->order->get_order_count($where);
        $data['count'] = $count;
        if(!$count){
            api_resopnse( [], ApiStatus::CODE_0 );
            return;
        }
        //显示条数
        $options['size']   = $params['show_count']?$params['show_count']:20;
        //分页
        $total_page = ceil($data['count']/$options['size']);
        $options['page'] = $params['page']?$params['page']:1;
        $options['page'] = $options['page']>=$total_page?$total_page:$options['page'];
        $options['page'] = intval($options['page']);
        $data['total_page'] = $total_page;
        $data['page'] =  $options['page'];

        //获取订单数据列表
        $options['goods_info'] = true;
        $order_list = $this->order ->get_order_list($where,$options);
        $order_list = $this->arrayKey($order_list,"order_id");
        if(!$order_list){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
        }
        $order_ids = array_column($order_list,'order_id');
        asort($order_ids);
        $additional['size'] = count($order_ids);
        $additional['page']  = 1;

        //组装数据格式
        $order_new = array();
        foreach($order_list as $key=>$val){
            $Order = new Order($val);
            //获取前端订单状态
            //$status_name =  $Order->get_client_name();
            $status_name = State::getStatusAllName($val['status']);
            $status_key =  $Order->get_status();

            $order_new[$key] = array(
                'shop_name' => $shop[$val['appid']]['name'],
                'create_time'  =>  date("Y-m-d H:i:s",$val['create_time']),
                'status_name' => $status_name,
                'status_key' => $status_key,
                'order_id'      => $val['order_id'],
                'order_no'      => $val['order_no'],
                'amount'        => $val['amount'],
                'mobile'        => $val['mobile'],
                'yajin'          => $val['yajin'],
                'mianyajin'     => $val['mianyajin'],
                'zujin'          => $val['zujin'],
                'yiwaixian'      => $val['yiwaixian'],
                'zuqi'           => $val['zuqi'],
                'user_info'       => array(
                    'username'     =>  $val['realname'],
                    'mobile'        =>  $val['mobile'],
                    'card'         =>  $val['cert_no'],
                ),
                'sku_info'       => array(
                    'sku_id'         =>  $val['goods_info']['sku_id'],
                    'sku_name'   =>  $val['goods_info']['sku_name'],
                    'imei'   =>  $val['goods_info']['imei1'],
                )
            );
        }
        array_multisort($order_new,SORT_DESC);
        $data['order_list'] = $order_new;
        api_resopnse( $data, ApiStatus::CODE_0 );
        return;
    }
    //订单详情
    public function get(){
        //接收请求参数
        $param = $this->params;
        //过滤参数
        $param = filter_array($param,[
            "order_no"  => "required",
        ]);
        
        //验证参数
        if(!$param['order_no']){
            api_resopnse( [], ApiStatus::CODE_20001,'order_no必须');
            return;
        }
        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        $this->shop = $this->load->service('channel/channel_appid');
        $this->img = $this->load->service('order2/order_image');
        $this->log = $this->load->service('order2/order_log');

        $where['order_no'] = $param['order_no'];
        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        $order = $this->order->get_order_info($where,$additional);

        //验证参数
        if(!$order){
            api_resopnse( [], ApiStatus::CODE_50003,'订单不存在');
            return;
        }
        //订单
        $data = [
            'order_no' => $order['order_no'],
            'amount' => $order['all_amount'],
            'discount_amount' => $order['discount_amount'],
            'yajin' => $order['yajin'],
            'mianyajin' => $order['mianyajin'],
            'mianya' => $order['mianyajin']>0?true:false,
            'zujin' => $order['zujin'],
            'baoxian' => $order['yiwaixian'],
            'zuqi' => $order['zuqi'],
            'day' => $order['amount'],
            'begin_time' => "",
            'end_time' => "",
            'create_time' => $order['create_time'],
            'peyment_time' => $order['payment_time'],
            'status' => $order['status'],
        ];

        $orderObj = new oms\Order($order);
        $status_name = $orderObj->get_client_name();
        $data['status_name'] = $status_name;
        //商品
        $data['sku_info'] = [
            'sku_id' =>  $order['goods_info']['sku_id'],
            'sku_name' => $order['goods_info']['sku_name'],
            'imei' => $order['goods_info']['imei1'],
            'serial_number' => $order['goods_info']['serial_number'],
        ];
        //用户
        $data['user_info'] = [
            'username' =>  $order['realname'],
            'mobile' => $order['mobile'],
            'card' => $order['cert_no'],
        ];
        //门店
        $shop_info = $this->shop->get_info($order['appid']);
        $data['shop_info'] = [
            'shop_name'=> $shop_info['appid']['name'],
            'shop_mobile'=> $shop_info['appid']['mobile'],
        ];
        //操作日志
        $type = oms\operator\OperatorList::getOperatorList();
        $plat_log = $this->log->get_by_order_no($order['order_no']);
        foreach($plat_log as $key=>$val){
            if($val['operator_type'] != oms\operator\Operator::Type_System){
                $plat_log[$key]['type_name'] =$type[$val['operator_type']];
                $plat_log[$key]['system_time'] = $val['system_time']?date("Y-m-d H:i:s",$val['system_time']):"";
            }
            else{
                unset($plat_log[$key]);
            }
        }
        array_multisort($plat_log);
        $data['log_info'] = $plat_log;
        //合同图片
        $images = $this->img->get_one($order['order_id']);
        $data['order_agreement'] = [
            'goods_delivery'=>$images['goods_delivery']
        ];

        api_resopnse( $data, ApiStatus::CODE_0 );
        return;
    }

    public function export(){
        
    }
}