<?php
use zuji\order\OrderStatus;
use zuji\order\PaymentStatus;
use zuji\order\DeliveryStatus;
use zuji\order\ReturnStatus;
use zuji\order\ReceiveStatus;
use zuji\order\EvaluationStatus;
use zuji\order\RefundStatus;
/**
 * 		订单服务层
 */
class order_api_core_service extends service {

    public function _initialize() {

        $this->userId = $this->member['id']?$this->member['id']:1;

        /* 实例化数据层 */
        $this->sku_serve     = $this->load->service('goods2/goods_sku');
        $this->spu_serve     = $this->load->service('goods2/goods_spu');
        $this->member_serve  = $this->load->service('member/member');
        $this->order_serve  = $this->load->service('order2/order');
        $this->address_serve = $this->load->service('member/member_address');
    }

    //获取用户信息
    private function get_user(){
        return $this->member_serve->fetch_by_id($this->userId);
    }
    //获取地址信息
    private function get_address(){
        return $this->address_serve->user_address_default($this->userId);
    }
    //获取单条sku商品
    private function sku_goods($goodsId,$sku_field=""){
       return $this->sku_serve->api_get_info($goodsId,$sku_field);
    }
    //获取单条spu商品
    private function spu_goods($goodsId,$spu_field=""){
        return $this->spu_serve->api_get_info($goodsId,$spu_field);
    }
    //订单确认查询
    public function confirmation($goodsId){
        if($goodsId>0){
            //获取商品信息
            $sku_field = "sku_id,spu_id,sku_name,thumb,spec,shop_price,yajin,zuqi,chengse";
            $goods_sku = $this->sku_goods($goodsId,$sku_field);
            $spu_field = "id,yiwaixian";
            $goods_spu = $this->spu_goods($goods_sku['spu_id'],$spu_field);

            $user  = $this->get_user();

            $data['zuji_no']       = "ZUJI-2017110113-000000393900404029253";
            //认证
            $data['cert_status']   = $user['certified']?"Y":"N";
            //芝麻分免押金
            $data['credit_status'] = $user['credit']>650?"Y":"N";

            $spec_names = array();
            foreach(json_decode($goods_sku['spec'],true) as $k=>$v){
                array_push($spec_names,$v['value']);
            }

            $sku_info = array(
                //货品ID
                'sku_id'     => $goods_sku['sku_id'],
                //货品名称
                'sku_name'   => $goods_sku['sku_name'],
                //缩略图
                'thumb'      => $goods_sku['thumb'],
                //货品名称规格列表
                'spec_names' => implode(",",$spec_names),
            );

            $is_mianya = intval($user['credit'])>=zuji\Config::ZIMA_SCORE?true:false;

            //押金
            $data['yajin']           = $is_mianya?0:$goods_sku['yajin'];
            //免押金额
            $data['yajin_free']    = $is_mianya?$goods_sku['yajin']:0;
            //月租金
            $data['monthly_price'] = $goods_sku['shop_price'];
            //意外险
            $data['yiwaixian_price'] = $goods_spu['yiwaixian'];
            //租期
            $data['zuqi']          = $goods_sku['zuqi'];
            //金额
            $data['amount']        = $goods_sku['shop_price']*$goods_sku['zuqi']+$goods_spu['yiwaixian'];
            //货品信息
            $data['sku_info']      = $sku_info;
            return $data;
        }
        return  false;
    }
    //会员订单列表查询
    public function order_list(){
        $where['user_id'] = $this->userId ;
        $options['page'] = 1;
        $options['size']   = 1;
        $options['goods_info'] = true;
        $order = $this->order_serve->get_order_list($where,$options);
        if($order){

        }
        //查询商品缩略图
        $goods =array_column($order, 'goods_info');
        $sku_ids = array_column($goods,'sku_id');
        $sku_where['sku_id'] = ['in',implode(',',$sku_ids)];
        $sku_goods = $this->sku_serve->api_get_list($sku_where,'sku_id,thumb');
        $sku_goods = $this->arrayKey($sku_goods,"sku_id");
        //组装数据格式
        $order_new = array();
        foreach($order as $key=>$val){
            $order_new[$key]['order_no']     = $val['order_no'];
            $order_new[$key]['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            $order_new[$key]['amount']       = $val['amount'];
            $order_new[$key]['yajin']           = $val['yajin'];
            $order_new[$key]['mianyajin']    = $val['mianyajin'];
            $order_new[$key]['zujin']           = $val['zujin'];
            $order_new[$key]['yiwaixian']       = $val['yiwaixian'];
            $order_new[$key]['zuqi']             = $val['zuqi'];
            $order_new[$key]['goods_info']['sku_id'] = $val['goods_info']['sku_id'];
            $order_new[$key]['goods_info']['sku_name'] = $val['goods_info']['sku_name'];
            $sku_id = $val['goods_info']['sku_id'];
            $order_new[$key]['goods_info']['thumb'] = $sku_goods[$sku_id]['thumb'];
            $order_new[$key]['goods_info']['specs'] = json_decode($val['goods_info']['specs'],true);
        }
        return $order_new;
    }
    //会员订单详情查询
    public function order_detail($order_no){
        $where['order_no'] = $order_no;
        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        $order = $this->order_serve->get_order_info($where,$additional);
        //var_dump($order);die;
        //订单基本信息
        $data['order_no'] = $order['order_no'];
        $data['create_time'] = date("Y-m-d H:i:s");
        $data['amount'] = $order['amount'];
        $data['yajin'] = $order['yajin'];
        $data['mianyajin'] = $order['mianyajin'];
        $data['zujin'] = $order['zujin'];
        $data['yiwaixian'] = $order['yiwaixian'];
        $data['wuliu_no'] = $order['delivery_info']['wuliu_no'];
        $data['order_no'] = $order['order_no'];
        //商品信息
        $data['sku_info']   =   array(
            'sku_id'=>$order['goods_info']['sku_id'],
            'sku_name'=>$order['goods_info']['sku_name'],
            'thumb'=>$order['goods_info']['thumb'],
            'specs'=>json_decode($order['goods_info']['specs'],true),

        );
        //地址信息
        $data['address_info']   = array(
            'name'=>$order['address_info']['name'],
             'mobile'=>$order['address_info']['mobile'],
             'address'=>$order['address_info']['address'],
        );

        return $data;
    }
    //订单创建
    public function order_create($goodsid){

        //获取用户信息
        $user = $this->get_user();
        $user_info['user_id'] = $user['id'];
        $user_info['mobile'] = $user['mobile'];
        $user_info['certified_platform'] = $user['certified_platform'];
        $user_info['credit'] = $user['credit'];

        //sku商品信息
        $sku_goods = $this->sku_goods($goodsid);
        $spu_goods = $this->spu_goods($sku_goods['spu_id']);

        $sku_info['sku_id']        = $sku_goods['sku_id'];
        $sku_info['spu_id']        = $sku_goods['spu_id'];
        $sku_info['brand_id']     = $spu_goods['brand_id'];
        $sku_info['category_id'] = $spu_goods['catid'];
        $sku_info['sku_name']   = $sku_goods['sku_name'];
        $sku_info['specs']          = $sku_goods['spec'];
        $sku_info['zuqi']            = intval($sku_goods['zuqi']);
        $sku_info['zujin']           = intval($sku_goods['shop_price']);
        $sku_info['yiwaixian']       = intval($spu_goods['yiwaixian']);
        //订单金额
        $sku_info['amount']       =  intval($sku_goods['shop_price']*$sku_goods['zuqi']+$spu_goods['yiwaixian']);

        $is_mianya = intval($user['credit'])>=zuji\Config::ZIMA_SCORE?true:false;
        $sku_info['yajin']          =  $is_mianya?0:$sku_goods['yajin'];
        $sku_info['mianyajin']   = $is_mianya?intval($sku_goods['yajin']):0;

        //收货地址
        $address = $this->get_address();
        $address_info['name'] = $address['name'];
        $address_info['mobile'] = $address['mobile'];
        $address_info['address'] = $address['address'];
        $address_info['province_id'] = $address['provin_id'];
        $address_info['city_id'] = $address['city_id'];
        $address_info['country_id'] = $address['country_id'];
        $address_info['zipcode'] = $address['zipcode'];

        $type = \zuji\Business::BUSINESS_ZUJI;
        $ret = $this->order_serve->create_order( $type, $user_info, $sku_info, $address_info);
        if($ret){
            $order['order_no'] = $ret;
            $order['create_time'] = date('Y-m-d H:i:s');
            $order['amount']       = $sku_info['amount'] ;
            $order['yajin']           = $sku_info['yajin'] ;
            $order['mianyajin']    = $sku_info['mianyajin'] ;
            $order['zujin']           = $sku_info['zujin'] ;
            $order['yiwaixian']       = $sku_info['yiwaixian'] ;
            $order['zuqi']             = $sku_info['zuqi'] ;
            $order['sku_info']       = array(
                'sku_id'         => $sku_goods['sku_id'],
                'sku_name'   => $sku_goods['sku_name'],
                'thumb'         => $sku_goods['thumb'],
                'spec_names' => $sku_goods['spec']
            );
            return $order;
        }
        else
        {
            return false;
        }

    }

    //重构数组键名
    function arrayKey($infos,$key){
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
}
