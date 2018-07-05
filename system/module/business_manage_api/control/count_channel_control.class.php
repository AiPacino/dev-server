<?php
/**
 * 渠道统计相关接口
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/28 0028-下午 5:28
 * @copyright (c) 2017, Huishoubao
 */
hd_core::load_class('user', 'offline_store_api');
class count_channel_control extends user_control
{

    public function _initialize(){
        parent::_initialize();
        $this->service = $this->load->service('statistics/count_channel');
        $this->channel_machine_service = $this->load->service('statistics/count_channel_machine');
    }

    /**
     * 获取渠道的
     */
    public function get_channel_count_list(){
        // 查询条件
        $where = [];
        $params = $this->params;
        if($params['begin_time']!='' ){
            $where['begin_time'] = strtotime($params['begin_time']);
        }
        if( $params['end_time']!='' ){
            $where['end_time'] = strtotime($params['end_time']);
        }

        $channel_id = api_request()->getAppid();
        $appid_list = $this->load->service('channel/channel_appid')->get_list(['channel_id' => $channel_id, 'status' => 1]);
        $appid_id_arr = array_column($appid_list, 'id');
        $where['appid'] = ['IN', $appid_id_arr];

        // 结束时间（可选），默认为为当前时间
        if( !isset($where['end_time']) ){
            $where['end_time'] = time();
        }

        if( isset($where['begin_time'])){
            if( $where['begin_time']>$where['end_time'] ){
                api_resopnse( [], ApiStatus::CODE_20001 ,'开始时间不能大于结束时间或当前时间')->flush();
                exit();
            }
            $where['unix_timestamp(dateline)'] = ['between',[$where['begin_time'], $where['end_time']]];
        }else{
            $where['unix_timestamp(dateline)'] = ['LT',$where['end_time']];
        }

        unset($where['begin_time']);
        unset($where['end_time']);

        $page_size = isset($params['show_count']) ? intval($params['show_count']) : 20;
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $options['field'] = 'appid,sum(order_num) as order_num, sum(complete_order_num) as complete_order_num, sum(pv) as pv, sum(all_amount) as all_amount, sum(service_expire_num) as service_expire_num';
        $options['orderby'] = 'id desc';
        $options['groupby'] = 'appid';
        $options['size'] = $page_size;
        $options['page'] = $page;
        $channel_list = $this->service->get_list_by_channelid($where, $options);
        if($channel_list){
            $appid_service = $this->load->service('channel/channel_appid');
            foreach ($channel_list as &$item){
                $appid_info = $appid_service->get_info($item['appid']);
                $item['appid_name'] = $appid_info['appid']['name'];
            }
        }

        $data['count'] = $this->service->get_channel_count($where, $options);
        $data['page'] = $page;
        $data['total_page'] = ceil($data['count']/$page_size);
        $data['channel_count_list'] = $channel_list;
        return api_resopnse( $data, ApiStatus::CODE_0 );
    }

    /**
     * 获取门店明细
     * @return bool
     */
    public function get_appid_count_list(){
        // 查询条件
        $where = [];
        $params = $this->params;
        if($params['begin_time']!='' ){
            $where['begin_time'] = strtotime($params['begin_time']);
        }
        if( $params['end_time']!='' ){
            $where['end_time'] = strtotime($params['end_time']);
        }
        if($params['appid']>'0' ){
            $where['appid'] = intval($params['appid']);
        }

        // 结束时间（可选），默认为为当前时间
        if( !isset($where['end_time']) ){
            $where['end_time'] = time();
        }

        if( isset($where['begin_time'])){
            if( $where['begin_time']>$where['end_time'] ){
                return false;
            }
            $where['unix_timestamp(dateline)'] = ['between',[$where['begin_time'], $where['end_time']]];
        }else{
            $where['unix_timestamp(dateline)'] = ['LT',$where['end_time']];
        }

        unset($where['begin_time']);
        unset($where['end_time']);

        $limit = (int)min(isset($params['show_count']) && is_numeric($params['show_count']) ? $params['show_count'] : 20, 100);
        $page = intval($params['page']);
        $options['field'] = "dateline,order_num,complete_order_num,pv,all_amount";
        $options['order'] = 'id desc';
        $result = $this->service->arrListByPage($page, $limit, $where, $options);

        $appid_list = $result['rows'];

        $data['count'] = $result['total'];
        $data['page'] = $page;
        $data['total_page'] = ceil($data['count']/$limit);
        $data['appid_count_list'] = $appid_list;

        return api_resopnse( $data, ApiStatus::CODE_0 );
    }


    /**
     * 根据时间查询基本的统计数据
     * @return mixed
     */
    public function base_data_count(){
        // 查询条件
        $where = $this->__parse_where();

        $data = $this->service->build_sqlmap($where)->base_data_count();
        return api_resopnse( $data, ApiStatus::CODE_0 );
    }

    /**
     * 订单趋势图接口
     * @return mixed
     */
    public function order_diagram_count(){
        // 查询条件
        $where = $this->__parse_where();

        $data = $this->service->build_sqlmap($where)->order_diagram();
        return api_resopnse( $data, ApiStatus::CODE_0 );
    }

    /**
     * 机型排行榜
     */
    public function machine_rank_count(){
        $where = [];

        $channel_id = api_request()->getAppid();
        $where['channel_id'] = $channel_id;

        $data = $this->channel_machine_service->build_sqlmap($where)->get_machine_ranking();
        return api_resopnse( $data, ApiStatus::CODE_0 );
    }

    /**
     * 某个机型7天的下单、成交量走势
     * @return mixed
     */
    public function machine_diagram_count(){
        $where = [];
        $params = $this->params;

        $channel_id = api_request()->getAppid();
        $where['channel_id'] = $channel_id;
        $where['days'] = 7;

        if(!isset($params['machine_id']) || empty($params['machine_id'])){
            api_resopnse( [], ApiStatus::CODE_20000 ,'参数machine_id不能为空')->flush();
            exit();
        }
        $where['machine_id'] = $params['machine_id'];

        $data = $this->channel_machine_service->build_sqlmap($where)->get_machine_diagram();
        return api_resopnse( $data, ApiStatus::CODE_0 );
    }

    /**
     * 设置pv,uv的量
     */
    public function set_page_view(){

        $params = $this->params;

        $channel_id = api_request()->getAppid();
        $where['channel_id'] = $channel_id;

        $page_view_model = $this->load->service('statistics/channel_page_view');
        if(!isset($params['pv']) || !is_array($params['pv'])){
            api_resopnse( [], ApiStatus::CODE_20001 ,'参数错误')->flush();
            exit();
        }
        if(!isset($params['uv']) || !is_array($params['uv'])){
            api_resopnse( [], ApiStatus::CODE_20001 ,'参数错误')->flush();
            exit();
        }

        $params['pv'] = json_encode($params['pv']);
        $params['uv'] = json_encode($params['uv']);

        $info = $page_view_model->where($where)->find();
        if($info){
            $params['id'] = $info['id'];
        }else{
            $params['channel_id'] = $channel_id;
        }
        $result = $page_view_model->update($params);
        if($result === false){
            api_resopnse( [], ApiStatus::CODE_50000 ,'设置错误')->flush();
            exit();
        }

        return api_resopnse( [], ApiStatus::CODE_0 );
    }

    private function __parse_where(){
        $where = [];
        $params = $this->params;

        $channel_id = api_request()->getAppid();
        $where['channel_id'] = $channel_id;
        $appid_list = $this->load->service('channel/channel_appid')->get_list(['channel_id' => $channel_id, 'status' => 1]);
        $appid_id_arr = array_column($appid_list, 'id');
        $where['appid'] = $appid_id_arr;

        // 结束时间（可选），默认为为当前时间
        $where['end_time'] = isset($params['end_time']) ? strtotime($params['end_time']) : time();

        if( isset($params['begin_time'])){
            $where['begin_time'] = strtotime($params['begin_time']);
            if( $where['begin_time']>$where['end_time'] ){
                api_resopnse( [], ApiStatus::CODE_20001 ,'开始时间不能大于结束时间或当前时间')->flush();
                exit();
            }
        }else{
            $where['begin_time'] = 0;
        }

        if(!isset($params['begin_time']) && !isset($params['end_time'])){
            if(isset($params['days']) && !empty($params['days'])){
                $where['days'] = $params['days'];
            }else{
                api_resopnse( [], ApiStatus::CODE_20001 ,'参数错误')->flush();
                exit();
            }
        }

        return $where;
    }



}