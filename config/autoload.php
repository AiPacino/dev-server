<?php
/**
 * 初始化时，系统主动加载的而配置（不是类自动加载的处理）
 */
$_autoload = array();
$_autoload['config'] = 'database,template';
$_autoload['library'] = '';
$_autoload['function'] = '';
$_autoload['language'] = 'language,error';
$_autoload['hooks'] = array(
	'pre_system' => LIB_PATH.'hook.class.php',
	'post_control' => array(
		LIB_PATH.'hook.class.php',
		LIB_PATH.'post.class.php',
	)
);