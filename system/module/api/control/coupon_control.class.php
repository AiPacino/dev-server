<?php
hd_core::load_class('api', 'api');
/**
 * 用户收货地址 API
 * @access public （访问修饰符）
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class coupon_control extends user_control
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 优惠券列表
     * @return Array
     * @author limin
     */
    public function query()
    {
        $param = $this->params;
        $param['type'] = $param['type'] ? $param['type'] : 1;

        if ($param['type'] != 1 && $param['type'] != 2 && $param['type'] != 3) {
            api_resopnse([], ApiStatus::CODE_20001, "type参数必须");
            return;
        }
        $data = zuji\coupon\Coupon::coupon_list($this->member['id'], $param['type']);
        api_resopnse($data, ApiStatus::CODE_0);
        return;
    }

    /**
     * 优惠券商品可用列表
     * @return Array
     * @author limin
     */
    public function checked()
    {
        //接收请求参数
        $param = $this->params;
        //过滤参数
        $data = filter_array($param, [
            "sku_id" => "required",
        ]);
        //验证参数
        if (!$data['sku_id']) {
            api_resopnse([], ApiStatus::CODE_20001, 'sku_id必须');
            return;
        }
        //依赖服务
        $this->sku_serve = $this->load->service('goods2/goods_sku');
        $this->spu_service = $this->load->service('goods2/goods_spu');
        //获取商品信息
        $sku_info = $this->sku_serve->api_get_info($data['sku_id']);
        $spu_info = $this->spu_service->api_get_info($sku_info['spu_id']);
        $num = [
            'sku_id'=>$sku_info['sku_id'],
            'spu_id'=>$sku_info['spu_id'],
            'user_id'=>$this->member['id'],
            'payment'=>($sku_info['shop_price']*$sku_info['zuqi']+$spu_info['yiwaixian'])*100 //月租金*租期+意外险
        ];
        $ret = zuji\coupon\Coupon::get_coupon($num);
        if ($ret['code'] == 0) {
            api_resopnse([], ApiStatus::CODE_50010, $ret['data']);
            return;
        } elseif ($ret['code'] == 1) {
            $data = $ret['data'];
            foreach ($data as $key => $val) {
                $data[$key]['coupon_code'] = $val['coupon_no'];
                unset($data[$key]['coupon_no']);
            }
            api_resopnse($data, ApiStatus::CODE_0);
            return;
        }
    }

    /**
     * 优惠券激活
     * @return Array
     * @author limin
     */
    public function activate()
    {
        //接收请求参数
        $data = $this->params;
        //过滤参数
        $param = filter_array($data, [
            "card_number" => "required",
        ]);
        //验证参数
        if (!$param['card_number']) {
            api_resopnse([], ApiStatus::CODE_20001, 'card_number必须');
            return;
        }
        $ret = zuji\coupon\Coupon::bingding($this->member['id'], $param['card_number']);
        if ($ret['code'] == 0) {
            api_resopnse([], ApiStatus::CODE_50010, $ret['data']);
            return;
        } elseif ($ret['code'] == 1) {
            api_resopnse([], ApiStatus::CODE_0);
            return;
        }
    }

    /**
     * 优惠券领取
     * @return Array
     * @author limin
     */
    public function receive(){
        //接收请求参数
        $data = $this->params;
        //过滤参数
        $param = filter_array($data,[
            "mobile"=> "required",
            "card_number"  => "required",
        ]);
        //验证参数
        if(!$param['mobile']){
            api_resopnse( [], ApiStatus::CODE_20001,'mobile必须');
            return;
        }
        if(!$param['card_number']){
            api_resopnse( [], ApiStatus::CODE_20001,'card_number必须');
            return;
        }
        $ret = zuji\coupon\Coupon::set_coupon_user(array("user_id"=>$this->member['id'],"only_id"=>$param['card_number']));
        if ($ret['code'] == 0) {
            api_resopnse([], ApiStatus::CODE_50010, $ret['data']);
            return;
        } elseif ($ret['code'] == 1) {
            api_resopnse([], ApiStatus::CODE_0);
            return;
        }
        elseif($ret['code']==1){
            api_resopnse([],ApiStatus::CODE_0 );
            return ;
        }
    }

    /**
     * 优惠券领取第二次活动
     * @return Array
     * @author limin
     */
    public function receives(){
        //接收请求参数
        $data = $this->params;
        //过滤参数
        $param = filter_array($data,[
            "mobile"=> "required",
            "card_number"  => "required",
        ]);
        //验证参数
        if(!$param['mobile']){
            api_resopnse( [], ApiStatus::CODE_20001,'mobile必须');
            return;
        }
        if(!$param['card_number']){
            api_resopnse( [], ApiStatus::CODE_20001,'card_number必须');
            return;
        }
        $ret = zuji\coupon\Coupon::set_coupon_user2(array("user_id"=>$this->member['id'],"only_id"=>$param['card_number']));
        if ($ret['code'] == 0) {
            api_resopnse([], ApiStatus::CODE_50010, $ret['data']);
            return;
        } elseif ($ret['code'] == 1) {
            api_resopnse([], ApiStatus::CODE_0);
            return;
        }
        elseif($ret['code']==1){
            api_resopnse([],ApiStatus::CODE_0 );
            return ;
        }
    }
}