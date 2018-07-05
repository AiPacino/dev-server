<?php

namespace zuji;

/**
 * 系统限制
 *
 * @author liuhongxing
 */
class Limited {
	
	/**
	 * 判断用户是否在 白名单 中
	 * @param int $user_id
	 * @return bool
	 */
	public static function inUserWhiteList( int $user_id ):bool{
		$userlist = self::_getList('user','whitelist');
		return in_array($user_id, $userlist);
	}
	
	/**
	 * 判断用户是否在 黑名单 中
	 * @param int $user_id
	 * @return bool
	 */
	public static function inUserBlackList( int $user_id ):bool{
		$userlist = self::_getList('user','blacklist');
		return in_array($user_id, $userlist);
	}
	
	/**
	 * 判断IP是否在 白名单 中
	 * @param string $ip
	 * @return bool
	 */
	public static function inIpWhiteList( string $ip ):bool{
		$list = self::_getList('ip','whitelist');
		return in_array($ip, $list);
	}
	
	/**
	 * 判断IP是否在 黑名单 中
	 * @param string $ip
	 * @return bool
	 */
	public static function inIpBlackList( string $ip ):bool{
		$list = self::_getList('ip','blacklist');
		return in_array($ip, $list);
	}
	
	/**
	 * 获取配置
	 * @return array
	 */
	private static function _getLimitedIni():array{
		static $ini;
		if( $ini==null ){
			$ini = parse_ini_file(DOC_ROOT.'config/limited.ini',true);
		}
		return is_array($ini) ? $ini : [];
	}
	
	/**
	 * 获取 名单
	 * @return array
	 */
	private static function _getList($name,$key):array{
		return isset(self::_getLimitedIni()[$name][$key]) && is_array(self::_getLimitedIni()[$name][$key]) ? self::_getLimitedIni()[$name][$key] : [];
	}
	
}
