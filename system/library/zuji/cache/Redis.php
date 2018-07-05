<?php
namespace zuji\cache;

// 判断扩张
if( !in_array('redis', \get_loaded_extensions()) ){
    //throw new \Exception('redis extension not found');
    class Redis{
	
	public static function setConfig( $config ){
	    
	}
	public static function getInstans( ) {
	    return new self();
	}
	
	public function __call($name, $arguments) {
	    return false;
	}
    }
    
}
else{
/**
 * Redis
 * 实现Redis客户端代理操作
 *
 * @author liuhongxing
*/
class Redis extends \Redis {
    
    private static $config = array();
    
    private static $Redis = null;
    
    public function __construct($config) {
	parent::__construct();
	$b = $this->connect($config['host'], $config['port'], $config['timeout'] );
	if( !$b ){
	    throw new \Exception('Redis connect error');
	}
        if( strlen($config['auth']) ){// 密码
            $this->auth( $config['auth'] ); 
        }
    }
    public static function setConfig( $config ){
	self::$config = $config;
    }
    public static function getInstans( ) {
	if( self::$Redis==null ){
	    self::$Redis = new self([
		'host' => config('REDIS_HOST'),
		'port' => config('REDIS_PORT'),
		'auth' => config('REDIS_AUTH'),
		'timeout' => '0',
	    ]);
	}
	return self::$Redis;
    }

}


}
