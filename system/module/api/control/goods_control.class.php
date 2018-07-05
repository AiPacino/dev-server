<?php
hd_core::load_class('api', 'api');

class goods_control extends api_control
{
    private $spu_serve;
    private $sku_serve;
    private $brand_serve;
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * [lists 接口商品列表]
     * @return [json][response]
     */
    public function queryall()
    {
        //依赖服务
        $this->channel_appid = $this->load->service('channel/channel_appid');
        $this->spu_serve     = $this->load->service('goods2/goods_spu');
        $this->sku_serve     = $this->load->service('goods2/goods_sku');
        $this->brand_serve  = $this->load->service('goods2/brand');
        $this->category = $this->load->service("goods/goods_category");
        //查询条件
        $where['status'] = 1;
        $appid = api_request()->getAppid();
        $appid_info = $this->channel_appid->get_info($appid);
        $channel_info = [];
        if($appid_info){
            //有独立商品，获取对应渠道的商品，没有：获取官方渠道的商品
            $where['channel_id'] = !empty($appid_info['_channel']['alone_goods']) ? $appid_info['appid']['channel_id'] : 1;
            $channel_info = $appid_info['_channel'];
        }
        $goods = $this->spu_serve->api_get_list($where);
        foreach($goods as $key=>$val){
            $goods[$key]['sku_total'] = $val['sku_total']>0?$val['sku_total']:0;
            $goods[$key]['yiwaixian'] = $val['yiwaixian']>0?zuji\order\Order::priceFormat($val['yiwaixian']):0;
            $goods[$key]['min_price'] = $val['min_price']>0?zuji\order\Order::priceFormat($val['min_price']):0;
            $goods[$key]['max_month'] = $val['max_month']>0?$val['max_month']:0;
            $goods[$key]['min_month'] = $val['min_month']>0?$val['min_month']:0;// 最小租期
			
			// 最小租期类型
			if( $val['min_zuqi_type'] == 1 ){
				$goods[$key]['min_zuqi_type'] = 'day';
			}elseif( $val['min_zuqi_type'] == 2 ){
				$goods[$key]['min_zuqi_type'] = 'month';
			}else{
				$goods[$key]['min_zuqi_type'] = 'unknown';
			}
			// 最大租期类型
			if( $val['max_zuqi_type'] == 1 ){
				$goods[$key]['max_zuqi_type'] = 'day';
			}elseif( $val['max_zuqi_type'] == 2 ){
				$goods[$key]['max_zuqi_type'] = 'month';
			}else{
				$goods[$key]['max_zuqi_type'] = 'unknown';
			}
            $goods[$key]['flag']  = "spu";
            $goods[$key]['imgs'] = $val['imgs']?json_decode($val['imgs'],true):"";

            // 商品优惠价 ：市场价 - （租金 * 12）
            //获取子商品信息
            $sku_where = [
                'status'=>1,
                'spu_id'=>intval($val['id'])
            ];
            $sku_goods = $this->sku_serve->api_get_list($sku_where);

            $status_ext = 0;
            foreach ($sku_goods as $k=>$v) {
                $discount_price = $v['market_price'] - ($v['shop_price'] * 12);
                $sku_goods[$k]['discount_price'] = $discount_price > 0 ? zuji\order\Order::priceFormat($discount_price) : 0;

                // 商品标签 取决于子商品第一个商品的标签
                if($k == 0){
                    $status_ext = $v['status_ext'];     
                }
            }
			
            //商品最小市场价
            $discount_price = array_column($sku_goods,"discount_price");
            asort($discount_price);
            $discount_price = current($discount_price);

            $goods[$key]['discount_price']  = $discount_price;
            $goods[$key]['status_ext']      = $status_ext;

        }

        if($appid==28){
            //苹果应用商店（只读苹果品牌，ID为2）
            $brand = $this->brand_serve->api_get_list(['id'=>2]);
        }
        else{
            $brand = $this->brand_serve->api_get_list(['status'=>1]);
        }
        $new_brand =[];
        foreach ($brand as $v =>$k){
            $spu_info =$this->spu_serve->api_get_list(['brand_id'=>$k['id'],'status'=>1]);
            if(count($spu_info) ==0){
                continue;
            }
            $new_brand[] =$k;
        }
        $brand =$new_brand;
        $category = $this->category->category_lists();

        //记录访问量
        $date = date('Ymd',time());
        $key = 'store:'.$appid.':'.$date;
        $redis = \zuji\cache\Redis::getInstans();
        $pv = $redis->get($key);
        if($pv === false){
            $redis->set($key, 1);
        }else{
            $redis->incr($key);
        }
        
        api_resopnse( array('category_list'=>$category,'brand_list'=>$brand,'spu_list'=>$goods, 'channel_info' => $channel_info),ApiStatus::CODE_0 );
    }

    /**
     * [detail 接口商品详情页]
     * @param  [type] $id [商品id]
     * @return [type] [description]
     */
    public function queryone()
    {
        $params = $this->params;
        $params = filter_array($params,[
            'id' => 'required',
        ]);

        if(empty($params['id'])){
            api_resopnse( [], ApiStatus::CODE_20001,'id必须', ApiSubCode::Sku_Error_Sku_id);
            return;
        }
        //依赖服务
        $this->channel_appid = $this->load->service('channel/channel_appid');
        $this->spu_serve     = $this->load->service('goods2/goods_spu');
        $this->sku_serve     = $this->load->service('goods2/goods_sku');
        //获取商品信息
        $spu_goods = $this->spu_serve->api_get_info($params['id']);

        $spu_goods['accessories'] = $spu_goods['peijian']?$spu_goods['peijian']:"";
        unset($spu_goods['peijian']);
        if(!$spu_goods || $spu_goods['status']!=1){
            api_resopnse( [], ApiStatus::CODE_50000,'没找到该商品' );
            return;
        }
		
		// 最小租期类型
		if( $spu_goods['min_zuqi_type'] == 1 ){
			$spu_goods['min_zuqi_type'] = 'day';
		}elseif( $spu_goods['min_zuqi_type'] == 2 ){
			$spu_goods['min_zuqi_type'] = 'month';
		}else{
			$spu_goods['min_zuqi_type'] = 'unknown';
		}
		// 最大租期类型
		if( $spu_goods['max_zuqi_type'] == 1 ){
			$spu_goods['max_zuqi_type'] = 'day';
		}elseif( $spu_goods['max_zuqi_type'] == 2 ){
			$spu_goods['max_zuqi_type'] = 'month';
		}else{
			$spu_goods['max_zuqi_type'] = 'unknown';
		}
		
        //获取子商品信息
        $where = [
            'status'=>1,
            'spu_id'=>intval($params['id'])
        ];
        $sku_goods = $this->sku_serve->api_get_list($where);

        foreach ($sku_goods as $k=>$v) {
            $sku_goods[$k]['number']      = $v['number']>0?$v['number']:0;
            $sku_goods[$k]['market_price'] = $v['market_price']>0?zuji\order\Order::priceFormat($v['market_price']):0;
            $sku_goods[$k]['yajin']        = $v['yajin']>0?zuji\order\Order::priceFormat($v['yajin']):0;
            $sku_goods[$k]['shop_price']  = $v['shop_price']>0?zuji\order\Order::priceFormat($v['shop_price']):0;
            $sku_goods[$k]['zuqi']        = $v['zuqi']>0?intval($v['zuqi']):0;
            $sku_goods[$k]['spec']        = json_decode($v['spec'],true);
			
			// 最小租期类型
			if( $v['zuqi_type'] == 1 ){
				$sku_goods[$k]['zuqi_type'] = 'day';
			}elseif( $v['zuqi_type'] == 2 ){
				$sku_goods[$k]['zuqi_type'] = 'month';
			}else{
				$sku_goods[$k]['zuqi_type'] = 'unknown';
			}
			
        }
        //商品最小市场价
        $market_price = array_column($sku_goods,"market_price");
        asort($market_price);
        $spu_goods['market_price'] = current($market_price);

        //图片规格
        $spec_list =  json_decode($spu_goods['specs'],true);
        $spec = [];
        foreach($spec_list as $key=>$val){
            $val['value'] = explode(",",$val['value']);
            if($val['id'] == 1){
                array_multisort($val['value'],SORT_DESC,SORT_NATURAL);
            }else{
                array_multisort($val['value'],SORT_NATURAL);
            }
            $spec[] = $val;
        }


        $appid_info = $this->channel_appid->get_info($this->appid);

        $spu_goods['imgs']     = $spu_goods['imgs'] ?json_decode($spu_goods['imgs'] ,true):"";
        $spu_goods['specs']    = $spec;
        $spu_goods['sku_list']  = $sku_goods;
        $spu_goods['appid_type'] = $appid_info['appid']['type'];
        $spu_goods['sku_total'] = $spu_goods['sku_total']>0?$spu_goods['sku_total']:0;
        $spu_goods['min_price'] = $spu_goods['min_price']>0?zuji\order\Order::priceFormat($spu_goods['min_price']):0;
        $spu_goods['max_month'] = $spu_goods['max_month']>0?$spu_goods['max_month']:0;
        $spu_goods['min_month'] = $spu_goods['min_month']>0?$spu_goods['min_month']:0;
        
        $spu_goods['spec_list'] = $spec_list;

        $response['spu_info']   = $spu_goods;

        api_resopnse( $response,ApiStatus::CODE_0  );
        return;
    }
    /*@根据sku_id获取相应支付列表
     * 产品支付方式列表
     * */
    public function payment(){

        $params = $this->params;
        $params = filter_array($params,[
            'sku_id' => 'required',
        ]);
        if(empty($params['sku_id'])){
            api_resopnse( [], ApiStatus::CODE_20001,'sku_id必须0', ApiSubCode::Sku_Error_Sku_id );
            return;
        }
        $this->sku_service = $this->load->service("goods2/goods_sku");
        $this->payment_service = $this->load->service("payment/payment_rule");

        $sku_info = $this->sku_service->api_get_info($params['sku_id']);
        if(empty($sku_info)){
            api_resopnse( [], ApiStatus::CODE_40003,'商品不存在');
            return;
        }
        $payment_list = $this->payment_service->get_payment_list_by_spu($sku_info['spu_id']);
        if(empty($payment_list)){
            api_resopnse( [], ApiStatus::CODE_40003,'支付方式错误');
            return;
        }
        api_resopnse($payment_list, ApiStatus::CODE_0);
        return;
    }

}