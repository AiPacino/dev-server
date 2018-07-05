<?php

/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
define('IN_ADMIN', TRUE);

class init_control extends control {

    public function _initialize() {
        parent::_initialize();
        $this->log = model('admin/log','service');
        $this->admin = model('admin/admin', 'service')->init();
        if ($this->admin['id'] < 1 && CONTROL_NAME != 'public') {
            redirect(url('admin/public/login'));
        }
        define('ADMIN_ID', intval($this->admin['id']));
        define('FORMHASH', $this->admin['formhash']);
	// 是否为超级管理员
        define('IS_SUPER', $this->admin['group_id']==1);
        $load = hd_load::getInstance();
        $load->librarys('View')->assign('admin', $this->admin);
        if (isset($_GET['formhash']) && $_GET['formhash'] !== FORMHASH) {
            hd_error::system_error('_request_tainting_');
        }
        if ($this->admin['group_id'] > 1 && model('admin/admin', 'service')->auth($this->admin['rules']) === false) {
            showmessage(lang('no_promission_operate', 'admin/language'));
        }
    }
//    /* 写入操作日志记录数据 */
    public function create_log($userId,$actionIp,$optionId,$remark,$dateline,$url){
        $params['user_id'] = $userId;
        $params['action_ip'] = $actionIp;
        $params['option_id'] = $optionId;
        $params['remark'] = $remark;
        $params['dateline'] = $dateline;
        $params['url'] = $url;
        $this->log->create_log($params);
    }
    /* 禁止重写的方法 */

    final public function admin_tpl($file, $module = '', $suffix = '.tpl.php', $halt = true) {
        $file = (!empty($file)) ? $file : CONTROL_NAME . '_' . METHOD_NAME;
        $module = (!empty($module)) ? $module : MODULE_NAME;
        $tpl_dir = APP_PATH . config('DEFAULT_H_LAYER') . '/' . $module . '/template/';
        $tpl_file = $tpl_dir . $file . $suffix;
        if (!is_file($tpl_file) && $halt === TRUE)
            die(lang('_template_not_exist_') . '：' . $tpl_file);
        return $tpl_file;
    }

    /**
     * 后台页面调用
     * @param int $totalrow 总记录数
     * @param int $pagesize 每页记录数
     * @param int $pagenum 	页码数量
     */
    final public function admin_pages($totalrow, $pagesize = 10, $pagenum = 5) {
        $totalPage = ceil($totalrow / $pagesize);
        $rollPage = floor($pagenum / 2);

        $StartPage = $_GET['page'] - $rollPage;
        $EndPage = $_GET['page'] + $rollPage;
        if ($StartPage < 1)
            $StartPage = 1;
        if ($EndPage < $pagenum)
            $EndPage = $pagenum;

        if ($EndPage >= $totalPage) {
            $EndPage = $totalPage;
            $StartPage = max(1, $totalPage - $pagenum + 1);
        }
        $string = '<ul class="fr">';
        $string .= '<li>共' . $totalrow . '条数据</li>';
        $string .= '<li class="spacer-gray margin-lr"></li>';
        $string .= '<li>每页显示<input class="input radius-none" type="text" name="limit" value="' . $pagesize . '"/>条</li>';
        $string .= '<li class="spacer-gray margin-left"></li>';

        /* 第一页 */
        if ($_GET['page'] > 1) {
            $string .= '<li class="start"><a href="' . page_url(array('page' => 1)) . '"></a></li>';
            $string .= '<li class="prev"><a href="' . page_url(array('page' => $_GET['page'] - 1)) . '"></a></li>';
        } else {
            $string .= '<li class="default-start"></li>';
            $string .= '<li class="default-prev"></li>';
        }
        for ($page = $StartPage; $page <= $EndPage; $page++) {
            $string .= '<li ' . (($page == $_GET['page']) ? 'class="current"' : '') . '><a href="' . page_url(array('page' => $page)) . '">' . $page . '</a></li>';
        }
        if ($_GET['page'] < $totalPage) {
            $string .= '<li class="next"><a href="' . page_url(array('page' => $_GET['page'] + 1)) . '"></a></li>';
            $string .= '<li class="end"><a href="' . page_url(array('page' => $totalPage)) . '"></a></li>';
        } else {
            $string .= '<li class="default-next"></li>';
            $string .= '<li class="default-end"></li>';
        }
        $string .= '</ul>';
        return $string;
    }

    final public function _empty() {
        $tpl_file = $this->admin_tpl(METHOD_NAME, null, '.tpl.php', false);
        if (!is_file($tpl_file)) {
            exit(lang('_template_not_exist_') . '：' . $tpl_file);
            //error::system_error(lang('_template_not_exist_').'：'.$tpl_file);
        }
        include $tpl_file;
    }

    /**
     * 判断是否有操作权限
     * @param string $m 模块
     * @param string $c 控制器
     * @param string $a 方法
     * @return bool
     */
    final public function check_promission_operate($m, $c, $a, $param=''){
        if ($this->admin['group_id'] > 1) {
            $rules = explode(",", $this->admin['rules']);
            $_map = array();
            $_map['status'] = ['EGT', 0];
            $_map['m'] = $m;
            $_map['c'] = $c;
            $_map['a'] = $a;
            if(!empty($param)){
                $_map['param'] = $param;
            }
            $rule_id = model('node')->where($_map)->getField('id');
            if($rule_id && !in_array($rule_id, $rules) && !defined('AUTH_IGNORE')) {
                return false;
            }
            return true;
        }
        return true;
    }

    public function dialog_close( $msg, $second=3 ){
        $this->load->librarys('View')
            ->assign('msg',$msg)
            ->display('dialog_close','admin');
        exit;

    }

}
