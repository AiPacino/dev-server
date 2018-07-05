<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: hd_error.php 33361 2013-05-31 08:59:06Z nemohou $
 */

class hd_error
{

        /**
         * 展示 系统 错误信息
         * @param string $message   错误提示
         * @param type $show        是否展示
         * @param type $save        是否写入错误日志
         * @param bool $halt        是否中止程序
         * @return string           $halt=false 时返回 $message
         */
	public static function system_error($message, $show = true, $save = true, $halt = true) {
		if(!empty($message)) {
			$message = lang($message, 'error');
		} else {
			$message = lang('error_unknow', 'error');
		}

		list($showtrace, $logtrace) = hd_error::debug_backtrace();

		if($save) {
			$messagesave = '<b>'.$message.'</b><br><b>PHP:</b>'.$logtrace;
			hd_log::write($messagesave);
		}

		if($show) {
			hd_error::show_error('system', "<li>$message</li>", $showtrace, 0);
		}

		if($halt) {
			exit();
		} else {
			return $message;
		}
	}

        /**
         * 展示 模板 错误信息
         * @param string $message   错误提示
         * @param string $tplname   模板名称
         */
	public static function template_error($message, $tplname) {
		$message = lang($message, 'error');
		$tplname = str_replace(DOC_ROOT, '', $tplname);
		$message = $message.': '.$tplname;
		hd_error::system_error($message, true, false);
	}

	private static function debug_backtrace() {
		$skipfunc[] = 'hd_error->debug_backtrace';
		$skipfunc[] = 'hd_error->db_error';
		$skipfunc[] = 'hd_error->template_error';
		$skipfunc[] = 'hd_error->system_error';
		$skipfunc[] = 'db_mysql->halt';
		$skipfunc[] = 'db_mysql->query';
		$skipfunc[] = 'DB::_execute';

		$show = $log = '';
		$debug_backtrace = debug_backtrace();
		krsort($debug_backtrace);
		foreach ($debug_backtrace as $k => $error) {
			$file = str_replace(DOC_ROOT, '', $error['file']);
			$func = isset($error['class']) ? $error['class'] : '';
			$func .= isset($error['type']) ? $error['type'] : '';
			$func .= isset($error['function']) ? $error['function'] : '';
			if(in_array($func, $skipfunc)) {
				break;
			}
			$error[line] = sprintf('%04d', $error['line']);

			$show .= "<li>[Line: $error[line]]".$file."($func)</li>";
			$log .= !empty($log) ? ' -> ' : '';$file.':'.$error['line'];
			$log .= $file.':'.$error['line'];
		}
		return array($show, $log);
	}

        /**
         * 展示 数据库 错误
         * @global type $_G
         * @param string $message   错误信息
         * @param string $sql       错误sql
         */
	public static function db_error($message, $sql) {
		global $_G;

		list($showtrace, $logtrace) = hd_error::debug_backtrace();

		$title = lang('error', 'db_'.$message);
		$title_msg = lang('error', 'db_error_message');
		$title_sql = lang('error', 'db_query_sql');
		$title_backtrace = lang('error', 'backtrace');
		$title_help = lang('error', 'db_help_link');

		$db = &DB::object();
		$dberrno = $db->errno();
		$dberror = str_replace($db->tablepre,  '', $db->error());
		$sql = dhtmlspecialchars(str_replace($db->tablepre,  '', $sql));

		$msg = '<li>[Type] '.$title.'</li>';
		$msg .= $dberrno ? '<li>['.$dberrno.'] '.$dberror.'</li>' : '';
		$msg .= $sql ? '<li>[Query] '.$sql.'</li>' : '';

		hd_error::show_error('db', $msg, $showtrace, false);
		unset($msg, $phperror);

		$errormsg = '<b>'.$title.'</b>';
		$errormsg .= "[$dberrno]<br /><b>ERR:</b> $dberror<br />";
		if($sql) {
			$errormsg .= '<b>SQL:</b> '.$sql;
		}
		$errormsg .= "<br />";
		$errormsg .= '<b>PHP:</b> '.$logtrace;

		hd_error::write_error_log($errormsg);
		exit();

	}

        /**
         * 展示 异常 信息
         * @param Exception $exception
         */
	public static function exception_error($exception) {

		if($exception instanceof DbException) {
			$type = 'db';
		} else {
			$type = 'system';
		}

		if($type == 'db') {
			$errormsg = '('.$exception->getCode().') ';
			$errormsg .= self::sql_clear($exception->getMessage());
			if($exception->getSql()) {
				$errormsg .= '<div class="sql">';
				$errormsg .= self::sql_clear($exception->getSql());
				$errormsg .= '</div>';
			}
		} else {
			$errormsg = $exception->getMessage();
		}

		$trace = $exception->getTrace();
		krsort($trace);

		$trace[] = array('file'=>$exception->getFile(), 'line'=>$exception->getLine(), 'function'=> 'break');
		$phpmsg = array();
		foreach ($trace as $error) {
			if(!empty($error['function'])) {
				$fun = '';
				if(!empty($error['class'])) {
					$fun .= $error['class'].$error['type'];
				}
				$fun .= $error['function'].'(';
				if(!empty($error['args'])) {
					$mark = '';
					foreach($error['args'] as $arg) {
						$fun .= $mark;
						if(is_array($arg)) {
							$fun .= 'Array';
						} elseif(is_bool($arg)) {
							$fun .= $arg ? 'true' : 'false';
						} elseif(is_int($arg)) {
							$fun .= (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) ? $arg : '%d';
						} elseif(is_float($arg)) {
							$fun .= (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) ? $arg : '%f';
						} else {
							$fun .= (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) ? '\''.dhtmlspecialchars(substr(self::clear($arg), 0, 10)).(strlen($arg) > 10 ? ' ...' : '').'\'' : '%s';
						}
						$mark = ', ';
					}
				}

				$fun .= ')';
				$error['function'] = $fun;
			}
			$phpmsg[] = array(
			    'file' => str_replace(array(DOC_ROOT, '\\'), array('', '/'), $error['file']),
			    'line' => $error['line'],
			    'function' => $error['function'],
			);
		}

		self::show_error($type, $errormsg, $phpmsg);
		exit();

	}

        /**
         * 端展示错误信息，并退出系统
         * @param string $type  类型：db为Database类型；否则为 System类型
         * @param string $errormsg  错误信息
         * @param string $phpmsg    错误信息
         * @param string $typemsg   （暂时无用）
         */
	public static function show_error($type, $errormsg, $phpmsg = '', $typemsg = '') {
            if( IS_API ){
                zuji\debug\Debug::error(0, '系统错误', ['msg'=>$errormsg]);
                api_resopnse( [], ApiStatus::CODE_50000,'稍后重试', '', '')->flush();
                exit;
            }
		ob_end_clean();
		$gzip = 1;
		ob_start($gzip ? 'ob_gzhandler' : null);

		$host = $_SERVER['HTTP_HOST'];
		$title = $type == 'db' ? 'Database' : 'System';
		echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>$host - $title Error</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
	<style type="text/css">
	<!--
	body { background-color: white; color: black; font: 9pt/11pt verdana, arial, sans-serif;}
	#container { width: 1024px; }
	#message   { width: 1024px; color: black; }

	.red  {color: red;}
	a:link     { font: 9pt/11pt verdana, arial, sans-serif; color: red; }
	a:visited  { font: 9pt/11pt verdana, arial, sans-serif; color: #4e4e4e; }
	h1 { color: #FF0000; font: 18pt "Verdana"; margin-bottom: 0.5em;}
	.bg1{ background-color: #FFFFCC;}
	.bg2{ background-color: #EEEEEE;}
	.table {background: #AAAAAA; font: 11pt Menlo,Consolas,"Lucida Console"}
	.info {
	    background: none repeat scroll 0 0 #F3F3F3;
	    border: 0px solid #aaaaaa;
	    border-radius: 10px 10px 10px 10px;
	    color: #000000;
	    font-size: 11pt;
	    line-height: 160%;
	    margin-bottom: 1em;
	    padding: 1em;
	}

	.help {
	    background: #F3F3F3;
	    border-radius: 10px 10px 10px 10px;
	    font: 12px verdana, arial, sans-serif;
	    text-align: center;
	    line-height: 160%;
	    padding: 1em;
	}

	.sql {
	    background: none repeat scroll 0 0 #FFFFCC;
	    border: 1px solid #aaaaaa;
	    color: #000000;
	    font: arial, sans-serif;
	    font-size: 9pt;
	    line-height: 160%;
	    margin-top: 1em;
	    padding: 4px;
	}
	-->
	</style>
</head>
<body>
<div id="container">
<h1>$title Error</h1>
<div class='info'>$errormsg</div>


EOT;
		if(!empty($phpmsg)) {
			echo '<div class="info">';
			echo '<p><strong>PHP Debug</strong></p>';
			echo '<table cellpadding="5" cellspacing="1" width="100%" class="table">';
			if(is_array($phpmsg)) {
				echo '<tr class="bg2"><td>No.</td><td>File</td><td>Line</td><td>Code</td></tr>';
				foreach($phpmsg as $k => $msg) {
					$k++;
					echo '<tr class="bg1">';
					echo '<td>'.$k.'</td>';
					echo '<td>'.$msg['file'].'</td>';
					echo '<td>'.$msg['line'].'</td>';
					echo '<td>'.$msg['function'].'</td>';
					echo '</tr>';
				}
			} else {
				echo '<tr><td><ul>'.$phpmsg.'</ul></td></tr>';
			}
			echo '</table></div>';
		}


		$helplink = '';
		if($type == 'db') {
			$helplink = "http://faq.comsenz.com/?type=mysql&dberrno=".rawurlencode(DB::errno())."&dberror=".rawurlencode(str_replace(DB::object()->tablepre, '', DB::error()));
			$helplink = "<a href=\"$helplink\" target=\"_blank\"><span class=\"red\">Need Help?</span></a>";
		}

		$endmsg = lang('error_end_message', 'error', array('host'=>$host));
		echo <<<EOT
<div class="help">$endmsg. $helplink</div>
</div>
</body>
</html>
EOT;
		$exit && exit();

	}

        /**
         * 移动端展示错误信息，并退出系统
         * @global type $_G
         * @param string $type  类型：db为Database类型；否则为 System类型
         * @param string $errormsg  错误信息
         * @param string $phpmsg    错误信息
         */
	public static function mobile_show_error($type, $errormsg, $phpmsg) {
		global $_G;

		ob_end_clean();
		ob_start();

		$host = $_SERVER['HTTP_HOST'];
		$phpmsg = trim($phpmsg);
		$title = 'Mobile '.($type == 'db' ? 'Database' : 'System');
		echo <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html>
<head>
	<title>$host - $title Error</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
	<style type="text/css">
	<!--
	body { background-color: white; color: black; }
	UL, LI { margin: 0; padding: 2px; list-style: none; }
	#message   { color: black; background-color: #FFFFCC; }
	#bodytitle { font: 11pt/13pt verdana, arial, sans-serif; height: 20px; vertical-align: top; }
	.bodytext  { font: 8pt/11pt verdana, arial, sans-serif; }
	.help  { font: 12px verdana, arial, sans-serif; color: red;}
	.red  {color: red;}
	a:link     { font: 8pt/11pt verdana, arial, sans-serif; color: red; }
	a:visited  { font: 8pt/11pt verdana, arial, sans-serif; color: #4e4e4e; }
	-->
	</style>
</head>
<body>
<table cellpadding="1" cellspacing="1" id="container">
<tr>
	<td id="bodytitle" width="100%">Discuz! $title Error </td>
</tr>
EOT;

		echo <<<EOT
<tr><td><hr size="1"/></td></tr>
<tr><td class="bodytext">Error messages: </td></tr>
<tr>
	<td class="bodytext" id="message">
		<ul> $errormsg</ul>
	</td>
</tr>
EOT;
		if(!empty($phpmsg)  && $type == 'db') {
			echo <<<EOT
<tr><td class="bodytext">&nbsp;</td></tr>
<tr><td class="bodytext">Program messages: </td></tr>
<tr>
	<td class="bodytext">
		<ul> $phpmsg </ul>
	</td>
</tr>
EOT;
		}
		$endmsg = lang('error', 'mobile_error_end_message', array('host'=>$host));
		echo <<<EOT
<tr>
	<td class="help"><br />$endmsg</td>
</tr>
</table>
</body>
</html>
EOT;
		$exit && exit();
	}

        /**
         * 空白替换（将"\t", "\r", "\n"替换成" "）
         * @param string $message   
         * @return string 替换后的错误信息
         */
	public static function clear($message) {
		return str_replace(array("\t", "\r", "\n"), " ", $message);
	}

        /**
         * sql 输出时的过滤函数
         * @param string $message   错误信息
         * @return string   过滤后的错误信息
         */
	public static function sql_clear($message) {
		$message = self::clear($message);
		$message = str_replace(DB::object()->tablepre, '', $message);
		$message = dhtmlspecialchars($message);
		return $message;
	}

        /**
         * 写错误日志
         * @param string $message   错误信息
         * @return null
         */
	public static function write_error_log($message) {

		$message = hd_error::clear($message);
		$time = time();
		$file =  DOC_ROOT.'./data/log/'.date("Ym").'_errorlog.php';
		$hash = md5($message);

		$uid = getglobal('uid');
		$ip = getglobal('clientip');

		$user = '<b>User:</b> uid='.intval($uid).'; IP='.$ip.'; RIP:'.$_SERVER['REMOTE_ADDR'];
		$uri = 'Request: '.dhtmlspecialchars(hd_error::clear($_SERVER['REQUEST_URI']));
		$message = "<?PHP exit;?>\t{$time}\t$message\t$hash\t$user $uri\n";
		if($fp = @fopen($file, 'rb')) {
			$lastlen = 50000;
			$maxtime = 60 * 10;
			$offset = filesize($file) - $lastlen;
			if($offset > 0) {
				fseek($fp, $offset);
			}
			if($data = fread($fp, $lastlen)) {
				$array = explode("\n", $data);
				if(is_array($array)) foreach($array as $key => $val) {
					$row = explode("\t", $val);
					if($row[0] != '<?PHP exit;?>') continue;
					if($row[3] == $hash && ($row[1] > $time - $maxtime)) {
						return;
					}
				}
			}
		}
		error_log($message, 3, $file);
	}
}