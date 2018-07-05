<?php
/**
 * 定时任务 基类
 *
 * @author wangjinlin
 *
 */
//hd_core::load_class('base', 'crontab');
class base_control extends control {
    /**
     *
     * @var \debug_service
     */
    private $whiteip = [

    ];


    public function _initialize() {
        parent::_initialize();
        /*
         * 白名单验证
         */
        //get_client_ip();
        echo self::get_client_ip();die;
        self::is_safe_ip();
    }

    /**
     * 安全IP检测，支持IP段检测
     * @param string $ip 要检测的IP
     * @param string|array $ips  白名单IP或者黑名单IP
     * @return boolean true 在白名单或者黑名单中，否则不在
     */
    private function is_safe_ip($ip="",$ips=""){
        if(!$ip) $ip = self::get_client_ip();  //获取客户端IP
        if($ips){
            if(is_string($ips)){ //ip用"," 例如白名单IP：192.168.1.13,123.23.23.44,193.134.*.*
                $ips = explode(",", $ips);
            }
        }else{ //读取后台配置 白名单IP
            $obj = new Setting();
            $ips = explode(",", $obj->getConfig("whiteip"));
        }
        if(in_array($ip, $ips)){
            return true;
        }
        $ipregexp = implode('|', str_replace( array('*','.'), array('\d+','\.') ,$ips));
        $rs = preg_match("/^(".$ipregexp.")$/", $ip);
        if($rs) return true;
        return ;
    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    private function get_client_ip($type = 0,$adv=false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

}