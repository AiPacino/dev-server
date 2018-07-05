<?php
/**
 * sms 短信接口
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
interface sms {
    
    /**
     * 发送短信
     * @return string	短信业务流水号
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function send();
}
