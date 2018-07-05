<?php
/**
 * 
 * @author liuhongxing <liuhongxing@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */
/**
 * 发送登录短信验证码
 * 
 * @author liuhongxing <liuhongxing@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */
class sms_login_code extends hsb_sms {
    
    
    /**
     * 构造函数
     * @params	    string  $mobile	【必须】手机号，多个以','分割
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function __construct($mobile='') {
	parent::__construct($mobile);
    }
    
    /**
     * 获取短信模板ID
     * @return	    string  短信模板ID
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    protected function _get_template_code(){
	return '';
    }
    /**
     * 获取短信模板参数
     * @return array	短信模板参数（非空数组时，必须为关联数组）
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    protected function _get_template_params(){
	
    }
    
}
