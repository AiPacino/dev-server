<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/3/8 0008-下午 5:11
 * @copyright (c) 2017, Huishoubao
 */

class count_channel_machine_service extends model
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
        if(isset($params['machine_id']) && is_numeric($params['machine_id'])) {
            $this->where['machine_id'] = $params['machine_id'];
        }
        if (isset($params['days']) && $params['days'] > 0) {
            $days = $params['days'];
            $days -= 1;
            $this->where['search']['time'] = ['BETWEEN', [strtotime("-{$days}day",strtotime(date('Y-m-d 00:00:00'))) ,time()]];
            $this->where['search']['days'] = $days;
        } else if (isset($params['begin_time']) && isset($params['end_time'])) {
            $this->where['search']['time'] = ['BETWEEN', [$params['begin_time'] ,$params['end_time']]];
            //两个时间戳之间的天数
            $this->where['search']['days'] = round(($params['end_time'] - $params['begin_time'])/86400);
        }

        return $this;
    }

    /**
     *机型排行
     */
    public function get_machine_ranking(){
        $result = [];

        $sqlmap = $this->where;

        //今日机型排行
        $today = date('Y-m-d', time());
        $sqlmap['dateline'] = $today;

        $today_machine_list = $this->field('machine_id, machine_name, order_num, complete_order_num')
            ->where($sqlmap)
            ->order('order_num desc')
            ->select();

        //昨日机型排行
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $sqlmap['dateline'] = $yesterday;
        $yesterday_machine_list = $this->field('machine_id, machine_name, order_num, complete_order_num')
            ->where($sqlmap)
            ->order('order_num desc')
            ->select();

        //7日机型排行
        unset($sqlmap['dateline']);
        $sqlmap['unix_timestamp(dateline)'] = ['BETWEEN', [strtotime("-7 day",strtotime(date('Y-m-d 00:00:00'))) ,time()]];
        $machine_list = $this->field('machine_id, machine_name, sum(order_num) seven_order_num, sum(complete_order_num) seven_complete_order_num')
            ->where($sqlmap)
            ->group('machine_id')
            ->order('order_num desc')
            ->select();

        foreach ($machine_list as $item){
            $today_machine = $this->get_num_by_machine_id($today_machine_list, $item['machine_id']);
            if(!empty($today_machine)){
                $item['today_order_num'] = $today_machine['order_num'];
                $item['today_complete_order_num'] = $today_machine['complete_order_num'];
            }else{
                $item['today_order_num'] = 0;
                $item['today_complete_order_num'] = 0;
            }

            $yesterday_machine = $this->get_num_by_machine_id($yesterday_machine_list, $item['machine_id']);
            if(!empty($yesterday_machine)){
                $item['yesterday_order_num'] = $yesterday_machine['order_num'];
                $item['yesterday_complete_order_num'] = $yesterday_machine['complete_order_num'];
            }else{
                $item['yesterday_order_num'] = 0;
                $item['yesterday_complete_order_num'] = 0;
            }

            $result[] = $item;
        }

        return $result;
    }

    private function get_num_by_machine_id($list, $machine_id){
        if(!empty($list) && is_array($list)){
            foreach ($list as $item){
                if($machine_id == $item['machine_id']){
                    return $item;
                }
            }
        }

        return [];
    }

    public function get_machine_diagram(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            unset($this->where['search']);

            $sqlmap = $this->where;
            if($search){
                $sqlmap['unix_timestamp(dateline)'] = $search;
                $days_data_result = $this->field('dateline, order_num, complete_order_num')
                    ->where($sqlmap)
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
            }
        }

        return $result;

    }
}