<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/28 0028-下午 4:34
 * @copyright (c) 2017, Huishoubao
 */
use oms\state\State;
class count_channel_service extends model
{

    protected $where = [];

    /**
     * 组装搜索条件
     * @param  array  $params
     * $params[user_id] : 会员主键id (int)
     * $params[days] : 最近{多少}天 (int)
     * $params[start_time] : 开始时间 (int, 时间戳)
     * $params[end_time] : 结束时间 (int, 时间戳)
     * @return [obj]
     */
    public function build_sqlmap($params = []) {
        if(isset($params['channel_id']) && is_numeric($params['channel_id'])) {
            $this->where['channel_id'] = $params['channel_id'];
        }
        if(isset($params['appid']) && is_array($params['appid'])){
            $this->where['appid'] = ['IN', $params['appid']];
        }
        if (isset($params['days'])) {
            if($params['days'] == 'all'){
                $this->where['search']['time'] = '';
                $this->where['search']['days'] = round((time() - strtotime(date('2017-12-01')))/86400);
            }else{
                $days = $params['days'];
                $days -= 1;
                $this->where['search']['time'] = ['BETWEEN', [strtotime("-{$days}day",strtotime(date('Y-m-d 00:00:00'))) ,time()]];
                $this->where['search']['days'] = $days;
            }
        } else if (isset($params['begin_time']) && isset($params['end_time'])) {
            $this->where['search']['time'] = ['BETWEEN', [$params['begin_time'] ,$params['end_time']]];
            //两个时间戳之间的天数
            $this->where['search']['days'] = round(($params['end_time'] - $params['begin_time'])/86400);
        }

        return $this;
    }

    /**
     * 渠道统计
     */
    public function base_data_count(){
        $result = [];
        if($this->where['search']){
            $days = $this->where['search']['days'];
            $days += 1;
            $search = $this->where['search']['time'];
            $channel_id = $this->where['channel_id'];
            unset($this->where['search']);
            unset($this->where['channel_id']);
            if($days == 1){
                $z_w = ['BETWEEN', [strtotime("-{$days}day",strtotime(date('Y-m-d 00:00:00'))) ,strtotime("-{$days}day",strtotime(date('Y-m-d 23:59:59')))]];
            }


            $sqlmap = $this->where;

            //注册用户
            $member_model = $this->load->table('member2/member');
            $register_where = $sqlmap;
            if($search)
            $register_where['register_time'] = $search;
            $member_register_num = $member_model->where($register_where)->count('id');
            $result['member_register_num'] = !empty($member_register_num) ? $member_register_num : 0;
            if($days == 1){
                $register_where['register_time'] = $z_w;
                $z_member_register_num = $member_model->where($register_where)->count('id');
                $result['z_member_register_num'] = !empty($z_member_register_num) ? $z_member_register_num : 0;
            }

            //认证用户
            $credit_where = $sqlmap;
            if($search)
            $credit_where['credit_time'] = $search;
            $member_credit_num = $member_model->where($credit_where)->count('id');
            $result['member_credit_num'] = !empty($member_credit_num) ? $member_credit_num : 0;
            if($days == 1){
                $credit_where['credit_time'] = $z_w;
                $z_member_credit_num = $member_model->where($credit_where)->count('id');
                $result['z_member_credit_num'] = !empty($z_member_credit_num) ? $z_member_credit_num : 0;
            }

            ////订单处理相关统计
            $order_model = $this->load->table('order2/order2');
            $state_where = $sqlmap;
            if($search)
            $state_where['create_time'] = $search;
            //待确认订单数
            $state_where['status'] = State::FundsAuthorized;
            $confirm_order_num = $order_model->where($state_where)->count('order_id');
            $result['confirm_order_num'] = $confirm_order_num;
            $result['confirm_status'] = State::FundsAuthorized;
            //待发货
            $state_where['status'] = ['IN', [State::StoreConfirmed, State::OrderConfirmed]];
            $delivery_order_num = $order_model->where($state_where)->count('order_id');
            $result['delivery_order_num'] = $delivery_order_num;
            $result['delivery_status'] = [State::StoreConfirmed, State::OrderConfirmed];
            //待收货
            $state_where['status'] = State::OrderDeliveryed;
            $receiv_order_num = $order_model->where($state_where)->count('order_id');
            $result['receiv_order_num'] = $receiv_order_num;
            $result['receiv_status'] = State::OrderDeliveryed;
            //租用中
            $state_where['status'] = State::OrderInService;
            $inservice_order_num = $order_model->where($state_where)->count('order_id');
            $result['inservice_order_num'] = $inservice_order_num;
            $result['inservice_status'] = State::OrderInService;
            //待处理订单（退换货中）
            $state_where['status'] = ['IN', [State::OrderHuanhuoing, State::OrderReturning]];
            $treat_order_num = $order_model->where($state_where)->count('order_id');
            $result['treat_order_num'] = $treat_order_num;
            $result['treat_status'] = [State::OrderHuanhuoing, State::OrderReturning];
            //取消订单
            $state_where['status'] = State::OrderCanceled;
            $canceled_order_num = $order_model->where($state_where)->count('order_id');
            $result['canceled_order_num'] = $canceled_order_num;
            $result['canceled_status'] = State::OrderCanceled;

            ////下单量占比（成色）
            $count_condition_model = $this->load->service('statistics/count_condition');
            //新机数
            $rate_where = $sqlmap;
            if($search)
            $rate_where['create_time'] = $search;
            $rate_where['chengse'] = 100;
            $quanxin_num = $count_condition_model->where($rate_where)->count('order_id');
            //优品数
            $rate_where['chengse'] = ['neq', 100];
            $youpin_num = $count_condition_model->where($rate_where)->count('order_id');

            $result['create_order_chengse'] = [
                'datas' => ['新机', '优品'],
                'count' => [['value'=>$quanxin_num,'name'=>'新机'],['value'=>$youpin_num,'name'=>'优品']]
            ];

            ////成交量占比（成色）
            $order_follow_model = $this->load->table('order2/order2_follow');
            $order2_follow_table = $order_follow_model->getTableName();
            //成交新机数
            if($search)
                $where = [
                    't1.chengse' => 100,
                    't1.appid' => $this->where['appid'],
                    't2.new_status' => ['IN', [7,22]],
                    't2.create_time' => $search
                ];
            else
                $where = [
                    't1.chengse' => 100,
                    't1.appid' => $this->where['appid'],
                    't2.new_status' => ['IN', [7,22]]
                ];
            $complete_quanxin_num = $count_condition_model->alias('t1')
                ->join($order2_follow_table.' as t2 ON t1.order_id=t2.order_id')
                ->where($where)
                ->count();

            //成交优品数
            if($search)
                $where = [
                    't1.chengse' => ['neq', 100],
                    't1.appid' => $this->where['appid'],
                    't2.new_status' => ['IN', [7,22]],
                    't2.create_time' => $search
                ];
            else
                $where = [
                    't1.chengse' => ['neq', 100],
                    't1.appid' => $this->where['appid'],
                    't2.new_status' => ['IN', [7,22]]
                ];
            $complete_youpin_num = $count_condition_model->alias('t1')
                ->join($order2_follow_table.' as t2 ON t1.order_id=t2.order_id')
                ->where($where)
                ->count();

            $result['complete_order_chengse'] = [
                'datas' => ['新机', '优品'],
                'count' => [['value'=>$complete_quanxin_num,'name'=>'新机'],['value'=>$complete_youpin_num,'name'=>'优品']]
            ];

            //信用认证分布
            $order_credit_where = $sqlmap;
            if($search)
            $order_credit_where['create_time'] = $search;
            $order_credit_where['chengse'] = 100;
            $order_credit_where['zm_score'] = ['BETWEEN', [599 ,649]];
            $zm_score_quanxin_range1 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['BETWEEN', [650 ,699]];
            $zm_score_quanxin_range2 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['BETWEEN', [700 ,749]];
            $zm_score_quanxin_range3 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['BETWEEN', [750 ,799]];
            $zm_score_quanxin_range4 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['EGT', 800];
            $zm_score_quanxin_range5 = $count_condition_model->where($order_credit_where)->count();

            $result['credit_order_quanxin'] = [
                'datas' => ['599-649', '650-699', '700-749', '750-799', '800分以上'],
                'count' => [
                    ['value'=>$zm_score_quanxin_range1,'name'=>'599-649'],
                    ['value'=>$zm_score_quanxin_range2,'name'=>'650-699'],
                    ['value'=>$zm_score_quanxin_range3,'name'=>'700-749'],
                    ['value'=>$zm_score_quanxin_range4,'name'=>'750-799'],
                    ['value'=>$zm_score_quanxin_range5,'name'=>'800分以上']
                ]
            ];

            //优品分布
            $order_credit_where['chengse'] = ['neq', 100];
            $order_credit_where['zm_score'] = ['BETWEEN', [599 ,649]];
            $zm_score_youpin_range1 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['BETWEEN', [650 ,699]];
            $zm_score_youpin_range2 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['BETWEEN', [700 ,749]];
            $zm_score_youpin_range3 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['BETWEEN', [750 ,799]];
            $zm_score_youpin_range4 = $count_condition_model->where($order_credit_where)->count();
            $order_credit_where['zm_score'] = ['EGT', 800];
            $zm_score_youpin_range5 = $count_condition_model->where($order_credit_where)->count();
            $result['credit_order_youpin'] = [
                'datas' => ['599-649', '650-699', '700-749', '750-799', '800分以上'],
                'count' => [
                    ['value'=>$zm_score_youpin_range1,'name'=>'599-649'],
                    ['value'=>$zm_score_youpin_range2,'name'=>'650-699'],
                    ['value'=>$zm_score_youpin_range3,'name'=>'700-749'],
                    ['value'=>$zm_score_youpin_range4,'name'=>'750-799'],
                    ['value'=>$zm_score_youpin_range5,'name'=>'800分以上']
                ]
            ];

            //下单数，成交数
            $order_where = $sqlmap;
            $result['create_order_num'] = array_sum(array_column($result['create_order_chengse']['count'], 'value'));
            $result['complete_order_num'] = array_sum(array_column($result['complete_order_chengse']['count'], 'value'));
            if($days == 1){
                $order_where['unix_timestamp(dateline)'] = $z_w;
                $z_create_order_num = $this->where($order_where)->sum('order_num');
                $z_complete_order_num = $this->where($order_where)->sum('complete_order_num');
                $result['z_create_order_num'] = !empty($z_create_order_num) ? $z_create_order_num : 0;
                $result['z_complete_order_num'] = !empty($z_complete_order_num) ? $z_complete_order_num : 0;
            }

            //转化率
            $page_view_model = $this->load->service('statistics/channel_page_view');
            $view_info = $page_view_model->where(['channel_id' => $channel_id])->find();
            if($view_info){
                $view_info['uv'] = json_decode($view_info['uv'], true);
                switch ($days){
                    case 1:
                        if(!empty($view_info['uv']['today']) && !empty($result['complete_order_num']))
                            $result['switch_rate'] = round(($result['complete_order_num']/$view_info['uv']['today']), 2);
                        else
                            $result['switch_rate'] = 0;
                        if(!empty($view_info['uv']['yesterday']) && !empty($result['z_complete_order_num']))
                            $result['z_switch_rate'] = round(($result['z_complete_order_num']/$view_info['uv']['yesterday']), 2);
                        else
                            $result['z_switch_rate'] = 0;
                        break;
                    case 7:
                        if(!empty($view_info['uv']['seven']) && !empty($result['complete_order_num']))
                            $result['switch_rate'] = round(($result['complete_order_num']/$view_info['uv']['seven']), 2);
                        else
                            $result['switch_rate'] = 0;
                        break;
                    case 30:
                        if(!empty($view_info['uv']['month']) && !empty($result['complete_order_num']))
                            $result['switch_rate'] = round(($result['complete_order_num']/$view_info['uv']['month']), 2);
                        else
                            $result['switch_rate'] = 0;
                        break;
                    default:
                        if(!empty($view_info['uv']['all']) && !empty($result['complete_order_num']))
                            $result['switch_rate'] = round(($result['complete_order_num']/$view_info['uv']['all']), 2);
                        else
                            $result['switch_rate'] = 0;
                        break;
                }
            }else{
                $result['switch_rate'] = 0;
                if($days == 1){
                    $result['z_switch_rate'] = 0;
                }
            }
        }

        return $result;
    }


    /**
     * 订单趋势图
     * @return array
     */
    public function order_diagram(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            unset($this->where['search']);

            $sqlmap = $this->where;
            if($search) {
                $sqlmap['unix_timestamp(dateline)'] = $search;

                $days_data_result = $this->field('dateline,sum(order_num) as order_num, sum(complete_order_num) as complete_order_num')
                    ->where($sqlmap)
                    ->group('dateline')
                    ->select();

                $days_data = [];
                foreach ($days_data_result as $item){
                    $key = date('m月d日', strtotime($item['dateline']));
                    $item['dateline'] = $key;
                    $days_data[$key] = $item;
                }

                for ($i = 0; $i <= $days; $i++) {
                    $today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
                    $default =  [
                        'dateline' => $today,
                        'order_num' => 0,
                        'complete_order_num' => 0
                    ];
                    $result['count'][$i] = isset($days_data[$today])?$days_data[$today]:$default;
                    $result['dates'][$i] = $today;
                }
            }else{

                $field = "FROM_UNIXTIME(unix_timestamp(dateline),'%Y年%m月') months,sum(order_num) order_num,sum(complete_order_num) complete_order_num";
                $months_data_result = $this->where($sqlmap)->field($field)->group('months')->select();
                foreach ($months_data_result as $i => $item){
                    $item['dateline'] = $item['months'];
                    unset($item['months']);

                    $result['count'][$i] = $item;
                    $result['dates'][$i] = $item['dateline'];
                }

            }

        }

        return $result;
    }

    /**
     * 渠道统计列表（分页）
     */
    public function get_list_by_channelid($where=[],$additional=[]){

        // 参数过滤
        $additional = filter_array($additional, [
            'field' => 'required',
            'page' => 'required|is_int',
            'size' => 'required',
            'orderby' => 'required',
            'groupby' => 'required'
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }elseif($additional['size']=='all'){
            $additional['size']="all";
        }else{
            $additional['size'] = min( $additional['size'], 100 );
        }

        if( !isset($additional['orderby']) ){	// 排序默认值
            $additional['orderby']='time_DESC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
        // 渠道信息
        $channel_list = $this->field($additional['field'])->page($additional['page'])->limit($additional['size'])->where($where)->order($additional['orderby'])->group($additional['groupby'])->select();

        return $channel_list;
    }

    public function get_channel_count($where, $additional){
        // 渠道信息
        $result = $this->field($additional['field'])->where($where)->group($additional['groupby'])->select();
        return count($result);
    }
}