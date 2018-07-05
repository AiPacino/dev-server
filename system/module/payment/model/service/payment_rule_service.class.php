<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/17 0017-下午 4:20
 * @copyright (c) 2017, Huishoubao
 */

class payment_rule_service extends model
{


    protected $_validate = array(
        array('name','require','{payment/name_require}',table::MUST_VALIDATE),
        array('payment_style_id',0,'{payment/payment_style_require}',table::EXISTS_VALIDATE, 'notequal', table:: MODEL_BOTH),
        array('credit_id',0,'{payment/credit_require}',table::EXISTS_VALIDATE, 'notequal', table:: MODEL_BOTH),
        array('yajin_id',0,'{payment/yajin_require}',table::EXISTS_VALIDATE, 'notequal', table:: MODEL_BOTH),
        array('status','number','{goods/state_require}',table::EXISTS_VALIDATE,'regex', table:: MODEL_BOTH),
    );

    public function edit_params($id, $params)
    {
        $rule_channel_service = $this->load->service('payment/payment_rule_channel');
        $rule_detail_service = $this->load->service('payment/payment_rule_detail');
        if($this->startTrans()){
            $result =  parent::edit_params($id, $params); // TODO: Change the autogenerated stub
            if($result !== false){
                $rule_id = empty($id) ? $result : $id;
                $channel_id_arr = $params['channel_id'];
                if(empty($channel_id_arr)){
                    $this->rollback();
                    $this->error = '请选择渠道';
                    return false;
                }

                //编辑渠道
                $a = $rule_channel_service->edit_relation($rule_id, $channel_id_arr);
                if($a === false){
                    $this->rollback();
                    return $a;
                }

                //编辑规则详情
                $b = $rule_detail_service->edit_relation($rule_id, $params['detail']);
                if($b === false){
                    $this->rollback();
                    return $b;
                }

            }else{
                $this->rollback();
                return $result;
            }

            $this->commit();
            return true;
        }else{

            $this->error = '事务开启失败';
            return false;
        }

    }

    /**
     * 获取详情，附加额外信息
     * @param int $id
     * @param string $extra
     * @return array|bool
     */
    public function get_info(int $id, $extra=''){
        $result = array();
        if($id < 1) {
            $this->error = '参数错误';
            return false;
        }
        $info = $this->modelId($id);
        if(empty($info)) {
            $this->error = '不存在';
            return false;
        }
        $result['rule'] = $info;

        /* 返回值 */
        if($extra) {
            $extra = explode(",", $extra);
            foreach ($extra AS $val) {
                $method = "get_extra_".$val;
                if(method_exists($this,$method)) {
                    $result['_'.$val] = $this->$method($info);
                }
            }
        }
        return $result;
    }

    /**
     * 获取渠道扩展信息
     * @param array $info
     * @return mixed
     */
    public function get_extra_channel(array $info){
        $rule_channel_service = $this->load->service('paynemt/payment_rule_channel');
        $channel_service = $this->load->table('channel/channel');
        $channel_table = $channel_service->trueTableName;
        $channel_list = $rule_channel_service->alias('t1')
            ->field('t2.id,name,contacts,phone,alone_goods,desc')
            ->join($channel_table.' as t2 ON channel_id = t2.id')
            ->where(['rule_id' => $info['id'],'t1.status' => 1])
            ->select();

        return $channel_list;
    }

    /**
     * 获取具体规则列表
     * @param array $info
     */
    public function get_extra_rule_detail(array $info){
        $rule_detail_service = $this->load->service('payment/payment_rule_detail');
        $rule_list = $rule_detail_service->where(['rule_id' => $info['id'], 'status' => 1])->select();
        return $rule_list;
    }


    /**
     * 根据spu_id获取支付方式列表
     */
    public function get_payment_list_by_spu(int $spu_id){
        $spu_rule = $this->load->service('payment/goods_spu_rule');
        $style_model = $this->load->service('payment/payment_style');
        $rule_table = $this->trueTableName;
        $style_table = $style_model->trueTableName;
        $payment_list = $spu_rule->alias('t1')
            ->field('t3.id,t3.pay_code,t3.pay_name')
            ->join($rule_table.' as t2 ON rule_id = t2.id ')
            ->join($style_table.' as t3 ON t2.payment_style_id = t3.id')
            ->where(['spu_id' => $spu_id, 't1.status' => 1])
            ->select();

        return $payment_list;
    }

    /**
     * 计算减免的金额和减免后的押金
     * @param $spu_id
     * @param $payment_id
     * @param $zm_score
     * @param $age
     * @param $yajin [单位分]
     */
    public function get_rule_info($spu_id, $payment_id, $zm_score, $age, $yajin){
        if($payment_id == \zuji\Config::UnionPay){
            return ['jianmian' => $yajin, 'yajin' => 0];
        }

        //获取rule_id数组
        $spu_rule = $this->load->service('payment/goods_spu_rule');
        $rule_detail_service = $this->load->service('payment/payment_rule_detail');
        $rule_id_arr = $spu_rule->where(['spu_id' => $spu_id, 'status' => 1])->getField('rule_id', true);
        if(empty($rule_id_arr)){
            return ['jianmian' => 0, 'yajin' => $yajin];
        }

        $rule_detail_table = $rule_detail_service->trueTableName;
        $where = [
            't1.id' => ['IN', $rule_id_arr],
            'payment_style_id' => $payment_id,
            'credit_down' => ['ELT', $zm_score],
            'credit_up' => ['EGT', $zm_score],
            'age_up' => ['EGT', $age],
            'age_down' => ['ELT', $age],
            't2.status' => 1
        ];
        $rule_list = $this->alias('t1')
            ->field('t1.id,t1.name,credit_down,credit_up,age_up,age_down,yajin_type,relief_amount,max_amount')
            ->join($rule_detail_table.' as t2 ON t1.id=rule_id')
            ->where($where)
            ->find();


        $jianmian = 0;
        $yajined = $yajin;
        if(!empty($rule_list)){
            if($rule_list['yajin_type'] == $rule_detail_service::TYPE_QUOTA){
                if(($rule_list['relief_amount']*100) > $yajin){
                    $jianmian = $yajin;
                    $yajined = 0;
                }else{
                    $jianmian = $rule_list['relief_amount']*100;
                    $yajined = $yajin-$jianmian;
                }
            }elseif($rule_list['yajin_type'] == $rule_detail_service::TYPE_PER){
                $jianmian = $yajin*($rule_list['relief_amount']/100);
                if($rule_list['max_amount']*100<$jianmian){
                    $jianmian = $rule_list['max_amount']*100;
                }
                $yajined = $yajin-$jianmian;
            }
        }

        if($jianmian == $yajin){
            $jianmian -= 1;
            $yajined = 1;
        }

        return ['jianmian' => $jianmian, 'yajin' => $yajined];
    }


}