<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/30 0030-下午 5:08
 * @copyright (c) 2017, Huishoubao
 */

class count_member_service extends model
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

    public function get_member_num(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];

            if($search){
                $where['unix_timestamp(dateline)'] = $search;
                $days_data_result = $this->where($where)->select();
                foreach ($days_data_result as $k =>$item){
                    $today = date('m月d日',strtotime($item['dateline']));
                    $result['days']['count'][$k] = $item;
                    $result['days']['dates'][$k] = $today;
                }
            }

        }

        return $result;
    }

    /**
     * 会员年龄，性别百分比
     */
    public function get_member_rata(){
        $man_total = $this->sum('man_num');
        $woman_total = $this->sum('woman_num');
        $teenagers_total = $this->sum('teenagers');
        $adult_total = $this->sum('adult');
        $midlife_total = $this->sum('midlife');
        $old_people_total = $this->sum('old_people');

        $member_rate = [
            'sex' => [
                'datas' => ['男性', '女性'],
                'count' => [['value'=>$man_total,'name'=>'男性'],['value'=>$woman_total,'name'=>'女性']]
            ],
            'age' => [
                'datas' => ['青少年', '成年青年','中年人', '老年人'],
                'count' => [
                    ['value'=>$teenagers_total,'name'=>'青少年'],
                    ['value'=>$adult_total,'name'=>'成年青年'],
                    ['value'=>$midlife_total,'name'=>'中年人'],
                    ['value'=>$old_people_total,'name'=>'老年人']
                ]
            ],
        ];

        return $member_rate;

    }
}