<?php

/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class hd_core {

    private static $_app;

    /**
     * 获取 hd_application实例化对象
     * 必须 先执行 run() 方法
     * @return hd_application   单一hd_application实例化对象
     */
    public static function app() {
	return self::$_app;
    }

    /**
     * 实例化 hd_application
     * @return hd_application   单一hd_application实例化对象
     */
    public static function run() {
	if (!is_object(self::$_app)) {
	    self::$_app = hd_application::instance();
	}
	return self::$_app;
    }

    /**
     * 异常处理回调函数
     * @param Exception $exception
     */
    public static function handleException($exception) {
	hd_error::exception_error($exception);
    }

    /**
     * 错误处理回调函数
     * @param int       $errno      错误码
     * @param string    $errstr     错误提示
     * @param string    $errfile    错误文件路径
     * @param int       $errline    错误行号
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
	if ($errno & APP_DEBUG) {
	    hd_error::system_error($errstr, false, true, false);
	}
    }

    /**
     * 程序中止回调函数
     */
    public static function handleShutdown() {
	if (($error = error_get_last()) && $error['type'] & APP_DEBUG) {
	    hd_error::system_error($error['message'], false, true, false);
	}
    }

    /**
     * 类加载函数
     * @staticvar array $_class
     * @param string $class     类名称
     * @param string $module    模块名称
     * @param bool $initialize  是否初始化
     * @return \class_name|boolean
     */
    public static function load_class($class, $module = null, $initialize = false) {
	static $_class = array();
	$module = (empty($module)) ? MODULE_NAME : $module;
	$class_name = $class . '_control';
	$class_dir = APP_PATH . config('DEFAULT_H_LAYER') . '/' . $module . '/control/';

	if (config('SUBCLASS_PREFIX') && is_file($class_dir . config('SUBCLASS_PREFIX') . $class_name . EXT)) {
	    $class_name = config('SUBCLASS_PREFIX') . $class_name;
	}
	$class_file = $class_dir . $class_name . EXT;
	if (require_cache($class_file)) {
	    $class = TRUE;
	    if ($initialize == TRUE && class_exists($class_name)) {
		$class = new $class_name();
	    }
	    $_class[$class_name] = $class;
	    return $class;
	} else {
	    hd_error::system_error('_class_not_exist_:['.$class_name.']; '.$class_file);
	}
	return FALSE;
    }

    /**
     * 类自动加载 回调函数
     * （加载失败时程序中止）
     * @param string $class     类名称
     * @return boolean  是否加载成功
     */
    public static function autoload($class) {
	//$class = strtolower($class);
	if (strpos($class, 'hd_') === 0) {
	    $path = CORE_PATH;
	} else {
	    if (strpos($class, 'control') > 1) {
		$name = cut_str($class, strpos($class, 'control') - 1);
		return self::load_class($name);
	    } else {
		$path = LIB_PATH;
	    }
	}
	try {
	    // 先执行命名空间方式加载
	    if( self::_zuji_autoload($class) ){
		return true;
	    }
	    $files = array(
		$path . 'MY_' . $class . EXT,
		$path . $class . EXT
	    );
	    if (require_array($files) === false) {
		throw new Exception('Oops! System file lost: ' . $class);
	    }
	    return true;
	} catch (Exception $exc) {
	    $trace = $exc->getTrace();
	    foreach ($trace as $log) {
		if (empty($log['class']) && $log['function'] == 'class_exists') {
		    return false;
		}
	    }
	    hd_error::exception_error($exc);
	}
    }

    /**
     * 加载类函数
     * @param string $class 类全名称
     * @return boolean  true：加载成功；false：加载失败
     */
    public static function _zuji_autoload( $class ){
        $flag = false;
        $file = '';
        $path = str_replace('\\', '/', $class);
        // 文件
        if( $flag===false && file_exists( LIB_PATH.'/'.$path . '.php' ) ){
            $file = LIB_PATH.$path . '.php';
            $flag = true;
        }
        // 文件
        if( $flag===false && file_exists( LIB_PATH.'/'.$path . '.class.php' ) ){
            $file = LIB_PATH.$path . '.class.php';
            $flag = true;
        }
        // 在当前目录找到
        if( $flag === true ){
            // 加载
            $b = require_cache($file);
			return $b;
        }
        
        // 类是否存在
        return false;
    }

}

/**
 * hd_core 类别名
 */
class C extends hd_core {
    
}
