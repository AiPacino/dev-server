<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/2/3 0003-下午 5:31
 * @copyright (c) 2017, Huishoubao
 */

class goods_spu_rule_service extends model
{


    /**
     * 关联商品支付规则
     * @param int $spu_id
     * @param array $rule
     * @return bool
     */
    public function edit_relation(int $spu_id, array $rule)
    {
        $spu_rule_list = $this->where(['spu_id' => $spu_id])->select();
        if($spu_rule_list){
            foreach ($spu_rule_list as $item){
                //
                if(in_array($item['rule_id'], $rule)){
                    if($item['status'] == 0){
                        $item['status'] = 1;
                        $result = $this->update($item);
                        if($result === false){
                            return false;
                        }
                    }
                    unset($rule[array_search($item['rule_id'] , $rule)]);
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

        if(!empty($rule)){
            foreach ($rule as $rule_id){
                $data = ['spu_id' => $spu_id, 'rule_id' => $rule_id];
                $result = parent::edit_params(0, $data);
                if($result === false){
                    return false;
                }
            }
        }
        return true;
    }
}