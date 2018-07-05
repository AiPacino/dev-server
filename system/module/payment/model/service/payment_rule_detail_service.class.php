<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/2/3 0003-下午 3:50
 * @copyright (c) 2017, Huishoubao
 */

class payment_rule_detail_service extends model
{

    const TYPE_QUOTA = 1;
    const TYPE_PER = 2;

    /**
     *
     */
    public function get_list_by_ruleid(int $rule_id){
        $list = $this->where(['rule_id' => $rule_id, 'status' => 1])->select();
        return $list;
    }

    /**
     * 编辑关联的规则详情
     * @param int $rule_id
     * @param array $params
     * @return bool
     */
    public function edit_relation(int $rule_id, array $params){
        $this->where(['rule_id' => $rule_id])->save(['status' => 0]);

        $rule_detail = [];
        foreach ($params['detail_id'] as $k => $item){
            $rule_detail['id'] = $item;
            $rule_detail['rule_id'] = $rule_id;
            $rule_detail['credit_down'] = $params['credit_down'][$k];
            $rule_detail['credit_up'] = $params['credit_up'][$k];
            $rule_detail['age_down'] = $params['age_down'][$k];
            $rule_detail['age_up'] = $params['age_up'][$k];
            $rule_detail['yajin_type'] = $params['yajin_type'][$k];
            $rule_detail['relief_amount'] = $params['relief_amount'][$k];
            $rule_detail['max_amount'] = $params['max_amount'][$k];
            $rule_detail['status'] = 1;

            $result = $this->update($rule_detail);
            if($result === false){
                return false;
            }
        }

        return true;
    }

}