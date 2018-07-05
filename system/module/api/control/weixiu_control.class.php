<?php

hd_core::load_class('api', 'api');
/**
 * 维修控制器
 * @access public
 * @author Chenchun <Chenchun@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class weixiu_control extends user_control{
    public function _initialize() {
        parent::_initialize();
        $this->spu_service = $this->load->service('goods/goods_spu');
        $this->order   = $this->load->service('order2/order');
        $this->weixiu = $this->load->service('weixiu/weixiu');
        $this->weixiu_service = $this->load->service('weixiu_service/weixiu_service');
    }

    /**
     * 维修服务列表查询接口
     * @access public
     * @return $data
     * @author Chenchun
     */
    public function service_query(){
        $parent = $this->weixiu_service->get_parent_list();
        if($parent === false) {
            api_resopnse( [], ApiStatus::CODE_20000);
            return;
        }
        $data = array();
        foreach ($parent as $k=>$v) {
            $data[$k]['service_id'] = $v['id'];
            $data[$k]['service_name'] = $v['service_name'];
            $reason = $this->weixiu_service->get_reason_list($v['id']);
            if($parent === false) { api_resopnse( [], ApiStatus::CODE_20000); return;}
            foreach ($reason as $key=>$val){
                $data[$k]['reason_list'][$key]['reason_id'] = $val['id'];
                $data[$k]['reason_list'][$key]['reason_name'] = $val['reason_name'];
            }
        }
        api_resopnse( $data, ApiStatus::CODE_0 ,'获取成功');
        return;
    }
    /**
     * 维修申请接口
     * @access public
     * @return $data
     * @author Chenchun
     */
    public function apply(){
        $params = $this->params;
        $data = filter_array($params,[
            'order_no' => 'required',
            'user_id' => 'required|is_id',
            'service_name' => 'required',
            'reason_name' => 'required',
            'pictures' => 'required',
            'user_remark' => 'required',
        ]);
        if(empty($data['order_no'])) {
            api_resopnse( [], ApiStatus::CODE_20001, '必须参数订单号不存在');
            return;
        }
        if(empty($data['user_id'])) {
            api_resopnse( [], ApiStatus::CODE_20001, '必须参数用户ID不存在');
            return;
        }
        if(empty($data['service_name'])) {
            api_resopnse( [], ApiStatus::CODE_20001, '必须参数维修内容不存在');
            return;
        }
        if(empty($data['reason_name'])) {
            api_resopnse( [], ApiStatus::CODE_20001, '必须参数维修原因不存在');
            return;
        }
        if(empty($data['pictures'])) {
            api_resopnse( [], ApiStatus::CODE_20001, '必须参数图片不存在');
            return;
        }
        $where['order_no']=$data['order_no'];
        $order_info = $this->order->get_order_info($where);
        if(empty($order_info)) {
            api_resopnse( [], ApiStatus::CODE_20001,'获取订单信息失败');
        }
        $data['order_id'] = $order_info['order_id'];
        $result = $this->weixiu->create($data);
        if($result === false) {
            api_resopnse( [], ApiStatus::CODE_20001,'申请维修单失败');
            return;
        }
        api_resopnse( [], ApiStatus::CODE_0 ,'申请维修单成功');
        return;
    }
    /**
     * 维修记录查看接口
     * @access public
     * @return $data
     * @author Chenchun
     */
    public function query(){
        $params = $this->params;
        $where = filter_array($params,[
            'order_no' => 'required',
            'user_id' => 'required|is_id',
        ]);
        if(count($where) != 2 ){
            api_resopnse( [], ApiStatus::CODE_20001,'参数错误');return;
        }
        //获取当条维修数据
        $fields =['order_no','record_id','service_name','reason_name','status','create_time'];
        $result = $this->weixiu->get_weixiu_info($where,$fields);
        if(empty($result)){
            api_resopnse( [], ApiStatus::CODE_20001,'获取维修单失败');
            return;
        }
        $result['create_time'] = date('Y-m-d H:i:s',$result['create_time']);
        //获取订单商品信息
        $order_info = $this->order->get_order_info(['order_no'=>$where['order_no']],['goods_info'=>true]);
        if(!$order_info){
            api_resopnse( [], ApiStatus::CODE_20001,'获取订单信息失败');
            return;
        }
        $result['goods_name'] = $order_info['goods_info']['sku_name'];
        // 商品规格
        $specs  = json_decode($order_info['goods_info']['specs'],true);
        $spec_value_list = [];
        foreach( $specs as $it ){
            $spec_value_list[] = $it['value'];
        }
        $result['goods_spec'] = implode(' ',$spec_value_list);
        // 商品图片
        $spu_info = $this->spu_service->get_query_one( $order_info['goods_info']['spu_id'] );
        $result['goods_images']= $spu_info['spu_info']['thumb'];
        api_resopnse( $result, ApiStatus::CODE_0 ,'获取维修单成功');
        return;
    }
    /**
     * 维修详情查看接口
     * @access public
     * @return $data
     * @author Chenchun
     */
    public function get(){
        $params = $this->params;
        $where = filter_array($params,[
            'order_no' => 'required',
            'record_id' => 'required|is_id',
        ]);
        if(count($where) != 2 ){
            api_resopnse( [], ApiStatus::CODE_20001,'参数错误');return;
        }
        //维修记录列表
        $result = $this->weixiu->get_weixiu_info($where);
        if(empty($result)){
            api_resopnse( [], ApiStatus::CODE_20001,'获取维修单失败');
            return;
        }
        api_resopnse( $result, ApiStatus::CODE_0 ,'获取维修单成功');
        return;
    }

}