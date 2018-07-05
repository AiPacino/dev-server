<?php
/**
 * 支付宝代扣协议 数据层
 */
class withholding_notify_alipay_table extends table {

    protected $fields = [
        'id',
        'external_user_id',         // 租机用户名
        'notify_id',                // 通知校验ID
        'notify_time',              // 通知时间
        'notify_type',              // 通知类型
        'sign_type',                // 签名类型
        'sign',                     // 签名
        'partner_id',               // 合作者身份ID
        'alipay_user_id',           // 支付宝用号
        'agreement_no',             // 协议号
        'product_code',             // 签约产品码
        'scene',                    // 签约场景
        'status',                   // 协议状态 空：无效,TEMP：暂存，协议未生效；NORMAL：正常；STOP：暂停
        'sign_time',                // 
        'sign_modify_time',
        'valid_time',
        'invalid_time',
        'unsign_time',              // 解约时间（解约异步通知时使用）
    ];


    /**
     * 根据协议码，查询详情
     * @param string $agreement_no	交易码
     */
    public function get_info( $agreement_no ){

        return $this->where(['agreement_no'=>$agreement_no])->limit(1)->find();
    }

    /**
     * 查询列表
     * @return int  符合查询条件
     */
    public function get_list($where) {
        $withhold_notify_list = $this->field($this->fields)->where($where)->order('id desc')->select();
        if(!is_array($withhold_notify_list)){
            return [];
        }
        return $withhold_notify_list;
    }
}