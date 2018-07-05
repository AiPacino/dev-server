<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/24 0024-下午 3:11
 * @copyright (c) 2017, Huishoubao
 */

class count_total_service extends model
{

    protected $where = [];

    /**
     * 组装搜索条件
     * @param  array  $params
     * $params[user_id] : 会员主键id (int)
     * $params[days] : 最近{多少}天 (int)
     * $params['month']:最近{多少}月(int)
     * $params[start_time] : 开始时间 (int, 时间戳)
     * $params[end_time] : 结束时间 (int, 时间戳)
     * @return [obj]
     */
    public function build_sqlmap($params = []) {
        if (isset($params['days']) && $params['days'] > 0) {
            $days = $params['days'];
            $days -= 1;
            $this->where['search']['time'] = ['BETWEEN', [strtotime("-{$days}day",strtotime(date('Y-m-d 00:00:00'))) ,time()]];
            $this->where['search']['days'] = $params['days'];
        } else if (isset($params['start_time']) && isset($params['end_time'])) {
            $this->where['search']['time'] = ['BETWEEN', [$params['start_time'] ,$params['end_time']]];
            //两个时间戳之间的天数
            $this->where['search']['days'] = round(($params['end_time'] - $params['start_time'])/86400);
        }

        if(isset($params['month']) && $params['month'] > 0){
            $months = $params['month'];
            $months -= 1;
            $this->where['search']['month_time'] = ['BETWEEN', [strtotime("-{$months}month",strtotime(date('Y-m-1 00:00:00'))) ,time()]];
            $this->where['search']['months'] = $params['month'];
        }
        return $this;
    }

    /**
     * 查询订单,下单率，成交率统计数据
     */
    public function date_count_search(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            $months = $this->where['search']['months'];
            $months_search = $this->where['search']['month_time'];

            if($search){
                $where['unix_timestamp(dateline)'] = $search;
                $days_data_result = $this->where($where)->select();
                $days_data = [];
                foreach ($days_data_result as $item){
                    $member = $this->get_member_num($item['dateline'], $search);

                    $key = date('m月d日', strtotime($item['dateline']));
                    //下单率为 ：下单/登陆用户数 成交率：成交量/登陆用户数
                    if($item['order_num'] > 0 && isset($member['login_num']) && $member['login_num'] > 0){
                        $item['create_order_rate'] = round($item['order_num']/$member['login_num'], 2);
                    }else{
                        $item['create_order_rate'] = 0;
                    }
                    if($item['complete_order_num'] > 0 && isset($member['login_num']) && $member['login_num'] > 0){
                        $item['complete_order_rate'] = round($item['complete_order_num']/$member['login_num'], 2);
                    }else{
                        $item['complete_order_rate'] = 0;
                    }
                    $days_data[$key] = $item;
                }

                for ($i = 0; $i < $days; $i++) {
                    $today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
                    $default =  [
                        'dateline' => $today,
                        'order_num' => 0,
                        'complete_order_num' => 0,
                        'refund_num' => 0,
                        'delivery_num' => 0,
                        'evaluation_num' => 0,
                        'huanhuo_num' => 0,
                        'huiji_num' => 0,
                        'pay_amount' => 0,
                        'refund_amount' => 0,
                        'create_order_rate' => 0,
                        'complete_order_rate' => 0
                    ];
                    $result['days']['count'][$i] = isset($days_data[$today])?$days_data[$today]:$default;
                    $result['days']['dates'][$i] = $today;
                }
            }
            if($months_search){
                $where['unix_timestamp(dateline)'] = $months_search;
                $field = "FROM_UNIXTIME(unix_timestamp(dateline),'%m月') months,sum(order_num) order_num,sum(complete_order_num) complete_order_num,sum(refund_num) refund_num,sum(delivery_num) delivery_num,sum(evaluation_num) evaluation_num,sum(huanhuo_num) huanhuo_num,sum(huiji_num) huiji_num,sum(pay_amount) pay_amount,sum(refund_amount) refund_amount";
                $months_data_result = $this->where($where)->field($field)->group('months')->select();
                $months_data = [];
                foreach ($months_data_result as $item){
                    $member = $this->get_month_member_num($item['months'], $months_search);

                    //下单率为 ：下单/登陆用户数 成交率：成交量/登陆用户数
                    if($item['order_num'] > 0 && isset($member['login_num']) && $member['login_num'] > 0){
                        $item['create_order_rate'] = round($item['order_num']/$member['login_num'], 2);
                    }else{
                        $item['create_order_rate'] = 0;
                    }
                    if($item['complete_order_num'] > 0 && isset($member['login_num']) && $member['login_num'] > 0){
                        $item['complete_order_rate'] = round($item['complete_order_num']/$member['login_num'], 2);
                    }else{
                        $item['complete_order_rate'] = 0;
                    }
                    $months_data[$item['months']] = $item;
                }

                for ($i = 0; $i < $months; $i++) {
                    $today = date('m月',strtotime("+{$i}month",$months_search[1][0]));
                    $default =  [
                        'months' => $today,
                        'order_num' => 0,
                        'complete_order_num' => 0,
                        'refund_num' => 0,
                        'delivery_num' => 0,
                        'evaluation_num' => 0,
                        'huanhuo_num' => 0,
                        'huiji_num' => 0,
                        'pay_amount' => 0,
                        'refund_amount' => 0,
                        'create_order_rate' => 0,
                        'complete_order_rate' => 0
                    ];
                    $result['months']['count'][$i] = isset($months_data[$today]) ? $months_data[$today] : $default;
                    $result['months']['dates'][$i] = $today;
                }
            }

        }

        return $result;
    }

    /**
     * 机型列表
     * @return array
     */
    public function get_machine_num(){

        $result = [];
        if ($this->where['search']) {
            $search = $this->where['search']['time'];
            //查询机型
            $where['payment_time'] = $search;
            $sql = 'SELECT t1.id,t1.name,count(t2.id) as num FROM zuji_goods_machine_model as t1 LEFT JOIN zuji_count_condition as t2 ON t1.id=t2.machine_id LEFT JOIN zuji_order2_follow as t3 ON t2.order_id=t3.order_id WHERE t3.new_status in (22,7) and (t3.create_time BETWEEN '.$search[1][0].' and '.$search[1][1].') GROUP BY t1.id ORDER BY num DESC limit 10';
            $machine_list = $this->query($sql);
            if(!empty($machine_list)){
                $result['days']['machine_name'] = array_reverse(array_column($machine_list, 'name'));
                $result['days']['machine_value'] = array_reverse(array_column($machine_list, 'num'));
            }
        }
        return $result;
    }


    private function get_member_num($dateline, $search){
        $where['unix_timestamp(dateline)'] = $search;
        $member_data_result = $this->load->service('count_member')->where($where)->select();
        foreach ($member_data_result as $member){
            if($member['dateline'] == $dateline){
                return $member;
            }
        }

        return [];
    }

    private function get_month_member_num($dateline, $search){
        $where['unix_timestamp(dateline)'] = $search;
        $field = "FROM_UNIXTIME(unix_timestamp(dateline),'%m月') months,avg(total_num) total_num,sum(register_num) register_num,sum(login_num) login_num,sum(certified_num) certified_num,sum(man_num) man_num,sum(woman_num) woman_num,sum(teenagers) teenagers,sum(adult) adult,sum(midlife) midlife,sum(old_people) old_people";
        $member_data_result = $this->load->service('count_member')->where($where)->field($field)->group('months')->select();
        foreach ($member_data_result as $member){
            if($member['months'] == $dateline){
                return $member;
            }
        }

        return [];
    }

}