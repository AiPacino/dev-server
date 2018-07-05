<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/22 0022-下午 2:18
 * @copyright (c) 2017, Huishoubao
 */

class count_condition_service extends model
{

    protected $where = [];
    protected $result = [];

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
        if(isset($params['user_id']) && is_numeric($params['user_id'])) {
            $this->where['user_id'] = $params['user_id'];
        }
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
     * 订单量查询
     * @return array
     */
    public function get_order_create_num(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            $months = $this->where['search']['months'];
            $months_search = $this->where['search']['month_time'];

            //按时间查询
            $where['create_time'] = $search;
            $field = "FROM_UNIXTIME(create_time,'%m月%d日') days,count(id) orders";
            $order_searchs = $this->where($where)->field($field)->group('days')->select();
            $orders = [];
            foreach ($order_searchs as $k => $val) {
                $orders[$val['days']] = $val['orders'];
            }

            for ($i = 0; $i < $days; $i++) {
                $today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
                $result['days']['orders'][$i] = isset($orders[$today])?$orders[$today]:0;
                $result['days']['dates'][$i] = $today;
            }

            //按月查询
            $where['create_time'] = $months_search;
            $field = "FROM_UNIXTIME(create_time,'%m月') months,count(id) orders";
            $order_searchs = $this->where($where)->field($field)->group('months')->select();
            $orders = [];
            foreach ($order_searchs as $k => $val) {
                $orders[$val['months']] = $val['orders'];
            }

            for ($i = 0; $i < $months; $i++) {
                $today = date('m月',strtotime("+{$i}month",$months_search[1][0]));
                $result['months']['orders'][$i] = isset($orders[$today])?$orders[$today]:0;
                $result['months']['dates'][$i] = $today;
            }
        }
        return $result;
    }

    /**
     * 订单的成交量
     * @return mixed
     */
    public function get_order_complete_num(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            $months = $this->where['search']['months'];
            $months_search = $this->where['search']['month_time'];

            //条件
            $where['payment_status'] = \zuji\order\PaymentStatus::PaymentSuccessful;

            //按时间查询
            $where['payment_time'] = $search;
            $field = "FROM_UNIXTIME(payment_time,'%m月%d日') days,count(order_id) orders";
            $order_searchs = $this->load->table('order2/order2')->where($where)->field($field)->group('days')->select();
            $orders = [];
            foreach ($order_searchs as $k => $val) {
                $orders[$val['days']] = $val['orders'];
            }

            for ($i = 0; $i < $days; $i++) {
                $today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
                $result['days']['orders'][$i] = isset($orders[$today])?$orders[$today]:0;
                $result['days']['dates'][$i] = $today;
            }

            //按月查询
            $where['payment_time'] = $months_search;
            $field = "FROM_UNIXTIME(payment_time,'%m月') months,count(order_id) orders";
            $order_searchs = $this->load->table('order2/order2')->where($where)->field($field)->group('months')->select();
            $orders = [];
            foreach ($order_searchs as $k => $val) {
                $orders[$val['months']] = $val['orders'];
            }

            for ($i = 0; $i < $months; $i++) {
                $today = date('m月',strtotime("+{$i}month",$months_search[1][0]));
                $result['months']['orders'][$i] = isset($orders[$today])?$orders[$today]:0;
                $result['months']['dates'][$i] = $today;
            }
        }
        return $result;
    }

    /**
     * 会员查询
     * @return mixed
     */
    public function get_member_total(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            $months = $this->where['search']['months'];
            $months_search = $this->where['search']['month_time'];

            $member_model = $this->load->table('member2/member');

            //按时间查询, 统计$days前的会员量
            $days_before_num = $member_model->where('FROM_UNIXTIME(register_time,\'%Y-%m-%d\') < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)')->count('id');

            $field = "FROM_UNIXTIME(register_time,'%m月%d日') days,count(id) members";
            $member_list = $member_model->where('DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY) <= FROM_UNIXTIME(register_time,\'%Y-%m-%d\')')->field($field)->group('days')->select();

            $members = [];
            foreach ($member_list as $k => $val) {
                $members[$val['days']] = $val['members'];
            }

            for ($i = 0; $i < $days; $i++) {
                global $num;
                $num= $days_before_num;
                $today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
                if(isset($members[$today])){
                   $members[$today] += $num;
                }else{
                    $members[$today] = $num;
                }
                $num = $members[$today];
                $result['days']['members'][$i] = $members[$today];
                $result['days']['dates'][$i] = $today;
            }

            //按月查询
            $months_before_num = $member_model->where('FROM_UNIXTIME(register_time,\'%Y-%m\') < DATE_SUB(CURDATE(), INTERVAL '.$months.' MONTH)')->count('id');

            $field = "FROM_UNIXTIME(register_time,'%m月') months,count(id) members";
            $member_list = $member_model->where('DATE_SUB(CURDATE(), INTERVAL '.$months.' MONTH) <= FROM_UNIXTIME(register_time,\'%Y-%m-%d\')')->field($field)->group('months')->select();
            foreach ($member_list as $k=>&$item){
                $item['member_num'] += $months_before_num;
                $months_before_num = $item['member_num'];
            }


            $members = [];
            foreach ($member_list as $k => $val) {
                $members[$val['months']] = $val['members'];
            }

            for ($i = 0; $i < $months; $i++) {
                $today = date('m月',strtotime("+{$i}month",$months_search[1][0]));
                $result['months']['members'][$i] = isset($members[$today])?$members[$today]:0;
                $result['months']['dates'][$i] = $today;
            }

        }

        return $result;
    }

    /**
     * 登录会员查询
     * @return mixed
     */
    public function get_login_member(){
        $result = [];
        if ($this->where['search']) {
            $days = $this->where['search']['days'];
            $search = $this->where['search']['time'];
            $months = $this->where['search']['months'];
            $months_search = $this->where['search']['month_time'];

            $member_model = $this->load->table('member2/member');

            //按时间查询
            $where['login_time'] = $search;
            $field = "FROM_UNIXTIME(login_time,'%m月%d日') days,count(id) members";
            $member_searchs = $member_model->where($where)->field($field)->group('days')->select();
            $orders = [];
            foreach ($member_searchs as $k => $val) {
                $orders[$val['days']] = $val['members'];
            }

            for ($i = 0; $i < $days; $i++) {
                $today = date('m月d日',strtotime("+{$i}day",$search[1][0]));
                $result['days']['members'][$i] = isset($orders[$today])?$orders[$today]:0;
                $result['days']['dates'][$i] = $today;
            }

            //按月查询
            $where['login_time'] = $months_search;
            $field = "FROM_UNIXTIME(login_time,'%m月') months,count(id) members";
            $member_searchs = $member_model->where($where)->field($field)->group('months')->select();

            $members = [];
            foreach ($member_searchs as $k => $val) {
                $members[$val['months']] = $val['members'];
            }

            for ($i = 0; $i < $months; $i++) {
                $today = date('m月',strtotime("+{$i}month",$months_search[1][0]));
                $result['months']['members'][$i] = isset($members[$today])?$members[$today]:0;
                $result['months']['dates'][$i] = $today;
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

    //下单率为 ：下单/登陆用户数 成交率：成交量/登陆用户数
    public function get_order_rate(){
        $result = [];
        $orders = $this->get_order_create_num();
        $members = $this->get_login_member();
        if(isset($orders['days'])){
            foreach ($orders['days']['orders'] as $k => $item){
                if($item && $members['days']['members'][$k]){
                    $result['days']['create_order_rate'][$k] = round($item/$members['days']['members'][$k]);
                }else{
                    $result['days']['create_order_rate'][$k] = 0;
                }
            }
            $result['days']['dates'] = $orders['days']['dates'];
        }
        if(isset($orders['months'])){
            foreach ($orders['months']['orders'] as $k => $item){
                if($item && $members['months']['members'][$k]){
                    $result['months']['create_order_rate'][$k] = round($item/$members['months']['members'][$k]);
                }else{
                    $result['months']['create_order_rate'][$k] = 0;
                }
            }
            $result['months']['dates'] = $orders['months']['dates'];
        }
        return $result;
    }

    //成交率：成交量/登陆用户数
    public function get_complete_order_rate(){
        $result = [];
        $orders = $this->get_order_complete_num();
        $members = $this->get_login_member();
        if(isset($orders['days'])){
            foreach ($orders['days']['orders'] as $k => $item){
                if($item && $members['days']['members'][$k]){
                    $result['days']['complete_order_rate'][$k] = round($item/$members['days']['members'][$k]);
                }else{
                    $result['days']['complete_order_rate'][$k] = 0;
                }
            }
            $result['days']['dates'] = $orders['days']['dates'];
        }
        if(isset($orders['months'])){
            foreach ($orders['months']['orders'] as $k => $item){
                if($item && $members['months']['members'][$k]){
                    $result['months']['complete_order_rate'][$k] = round($item/$members['months']['members'][$k]);
                }else{
                    $result['months']['complete_order_rate'][$k] = 0;
                }
            }
            $result['months']['dates'] = $orders['months']['dates'];
        }
        return $result;
    }

    /**
     * 今日的数据统计
     */
    public function get_today_total(){
        $result = [];
        // 本日查询条件
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end   = strtotime(date('Y-m-d 23:59:59'));
        $today = array('BETWEEN',array($start, $end));

        //查询新增用户数
        $users = $this->load->service('member2/member')->get_list(['register_time'=>$today]);
        $result['today']['user'] = count($users);
        //查询下单量
        $orders =$this->load->service('order2/order')->get_order_list(['create_time'=>$today]);
        $result['today']['order']=count($orders);
        //登陆用户数
        $login_users = $this->load->service('member2/member')->get_list(['login_time'=>$today]);
        $result['today']['login_user'] =count($login_users);
        //查询成交量
        $orders =$this->load->service('order2/order')->get_order_list(['payment_time'=>$today,'payment_status' =>\zuji\order\PaymentStatus::PaymentSuccessful]);
        $result['today']['payment']=count($orders);
        //下单率为 ：下单/登陆用户数 成交率：成交量/登陆用户数
        $login =count($login_users)==0?1:count($login_users);
        $result['today']['xiadanlv'] =sprintf("%.2f",$this->result['today']['order']/$login*100);
        $result['today']['chengjiaolv'] =sprintf("%.2f",$this->result['today']['payment']/$login*100);
        if($result['today']['xiadanlv'] >100){
            $result['today']['xiadanlv'] ="100.00";
        }
        if($result['today']['chengjiaolv'] >100){
            $result['today']['chengjiaolv'] ="100.00";
        }
        //退货量
        $refund = $this->load->service('order2/refund')->get_list(['refund_time' =>$today]);
        $result['today']['refund'] =count($refund);

        return $result;
    }

}