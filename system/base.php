<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
define('IN_APP', true);
defined('APP_ROOT') 	OR 		define('APP_ROOT', str_replace("\\","/",substr(dirname(__FILE__), 0, -6)));
defined('LIB_PATH') 	OR 		define('LIB_PATH',  APP_PATH.'library/');
defined('CORE_PATH') 	OR 		define('CORE_PATH',  APP_PATH.'core/');
defined('CONF_PATH')    OR 		define('CONF_PATH',  DOC_ROOT.'config/');
defined('CACHE_PATH') 	OR 		define('CACHE_PATH',  DOC_ROOT.'caches/');
defined('TPL_PATH') 	OR 		define('TPL_PATH',  DOC_ROOT.'template/');
defined('LANG_PATH') 	OR 		define('LANG_PATH',  APP_PATH.'language/');

defined('APP_DEBUG') 	OR 		define('APP_DEBUG', false);
defined('IS_API') OR define('IS_API',false);

/* ����ļ� */
defined('__APP__') 	    OR 		define('__APP__', $_SERVER['SCRIPT_NAME']);
/* ��װĿ¼ */
define('__ROOT__', str_replace(basename(__APP__), "", __APP__));
/* ���Ŀ¼ */
defined('PLUGIN_PATH') 	OR 		define('PLUGIN_PATH',  APP_PATH.'plugin/');

define('IS_CGI',(0 === strpos(PHP_SAPI,'cgi') || false !== strpos(PHP_SAPI,'fcgi')) ? 1 : 0 );
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
define('IS_CLI',PHP_SAPI=='cli'? 1   :   0);
define('EXT', '.class.php');

require CORE_PATH.'hd_core'.EXT;
// require CORE_PATH.'hd_load'.EXT;
require APP_PATH.'function/function.php';

// 注册 异常处理 回调函数
set_exception_handler(array('C', 'handleException'));

// 注册 错误处理 回调函数
set_error_handler(array('C', 'handleError'));

// 注册 程序终止 处理回调函数
register_shutdown_function(array('C', 'handleShutdown'));

// 注册 类自动加载 处理回调函数
if(function_exists('spl_autoload_register')) {
	spl_autoload_register(array('C', 'autoload'));
} else {
	function __autoload($class) {
		return C::autoload($class);
	}
}

if( check_extensions('redis') ){
	// session 存储
	if( isset($_SERVER['SESSION_SAVE_HANDLER']) || config('SESSION_SAVE_HANDLER','config') ){
		if( $_SERVER['SESSION_SAVE_HANDLER'] ){
		ini_set('session.save_handler', $_SERVER['SESSION_SAVE_HANDLER']);
		}else{
		ini_set('session.save_handler', config('SESSION_SAVE_HANDLER','config'));
		}
		if( $_SERVER['SESSION_SAVE_PATH'] ){
		ini_set('session.save_path', $_SERVER['SESSION_SAVE_PATH']);
		}else{
		ini_set('session.save_path', config('SESSION_SAVE_PATH','config'));
		}
		if( $_SERVER['SESSION_GC_MAXLIFETIME'] ){
		ini_set('session.gc_maxlifetime', $_SERVER['SESSION_GC_MAXLIFETIME']);
		}else{
		ini_set('session.gc_maxlifetime', config('SESSION_GC_MAXLIFETIME','config'));
		}
	}
}else{
//	throw new Exception('Redis 扩展不存在');
}

C::run();
