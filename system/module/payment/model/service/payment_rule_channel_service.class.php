<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/2/3 0003-下午 3:42
 * @copyright (c) 2017, Huishoubao
 */

class payment_rule_channel_service extends model
{
    private $channel_model = null;
    private $rule_model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->channel_model = $this->load->table('channel/channel');
        $this->rule_model = $this->load->service('payment/payment_rule');
    }

    /**
     * 根据rule_id 获取渠道列表
     */
    public function get_channel_list(int $rule_id){
        $where = ['rule_id' => $rule_id, 't1.status' => 1];
        $channel_table_name = $this->channel_model->trueTableName;
        $channel_list = $this->alias('t1')->field('t2.id, name')->join($channel_table_name . ' as t2  on t2.id = channel_id')->where($where)->select();
        return $channel_list;
    }

    /**
     * 根据渠道id 获取规则列表
     * @param int $channel_id
     */
    public function get_rule_list(int $channel_id){
        $where = ['channel_id' => $channel_id, 't1.status' => 1];
        $rule_table_name = $this->rule_model->trueTableName;
        $rule_list = $this->alias('t1')->field('t2.id, name')->join($rule_table_name . ' as t2  on t2.id = rule_id')->where($where)->select();
        return $rule_list;
    }

    public function edit_relation(int $rule_id, array $channel)
    {
        $rule_channel_list = $this->where(['rule_id' => $rule_id])->select();
        if($rule_channel_list){
            foreach ($rule_channel_list as $item){
                //
                if(in_array($item['channel_id'], $channel)){
                    if($item['status'] == 0){
                        $item['status'] = 1;
                        $result = $this->update($item);
                        if($result === false){
                            return false;
                        }
                    }
                    unset($channel[array_search($item['channel_id'] , $channel)]);
                }else{
                    if($item['status'] == 1){
                        $item['status'] = 0;
                        $result = $this->update($item);
                        if($result === false){
                            return false;
                        }
                    }
                }
            }
        }

        if(!empty($channel)){
            foreach ($channel as $channel_id){
                $data = ['rule_id' => $rule_id, 'channel_id' => $channel_id];
                $result = parent::edit_params(0, $data);
                if($result === false){
                    return false;
                }
            }
        }
        return true;
    }
}