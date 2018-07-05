<?php
class init_control extends control
{
	public function _initialize() {
	    exit('禁止访问');
		defined('IN_PLUGIN') OR define('IN_PLUGIN', TRUE);
		parent::_initialize();
                // 用户信息初始化（已经登录，返回用户信息，未登录，返回默认游客信息）
		$this->member = $this->load->service('member/member')->init();
                
                // 模板复制用户信息
		$this->load->librarys('View')->assign('member',$this->member);
                
                // 站点主题路径
		define('SKIN_PATH', __ROOT__.(str_replace(DOC_ROOT, '', TPL_PATH)).config('TPL_THEME').'/');
                
                // 云配置参数
		$cloud =  unserialize(authcode(config('__cloud__','cloud'),'DECODE'));
		define('SITE_AUTHORIZE', (int)$cloud['authorize']);
		define('COPYRIGHT', '');
		/* 检测商城运营状态 */
		runhook('site_isclosed');
	}
}