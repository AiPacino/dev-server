<?php
namespace zuji\auth;
/**
 * 授权类
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class Authorization {
    
    
    /**
     * 判断授权渠道是否正确
     * @param string $channel
     * @return boole
     */
    public static function is_auth_channel($channel){
	return in_array($channel, [
	    'ALIPAY',
        'ALIPAY-MINI'
	]);
    }

    
}
