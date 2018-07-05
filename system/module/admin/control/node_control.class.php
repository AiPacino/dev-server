<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class node_control extends init_control {

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('admin/node');
    }

    /* 节点管理 */
    public function index() {
        $nodes = $this->service->get_lists();
        $lists = array(
            'th' => array(
                'sort' => array('title' => '排序','length' => 10,'style'=>'double_click'),
                'name' => array('title' => '名称','length' => 70,'style'=>'data'),
            ),
            'lists' => $nodes,
        );


        $this->load->librarys('View')->assign('lists',$lists)->display('node_index');
    }

    public function ajax_status() {
        $id = $_GET['id'];
        if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
        if ($this->service->change_status($id)) {
            showmessage('状态修改成功', '', 1);
        } else {
            showmessage($this->service->error, '', 0);
        }
    }

    /* 更新排序 */
    public function ajax_sort() {
        $id = (int)$_GET['id'];
        $sort = (int)$_GET['sort'];
        $result = $this->service->setField(array('sort' => $sort),array('id' => $id));
        if ($result === FALSE) {
            showmessage($this->service->error);
        } else {
            showmessage(lang('edit_sort_success','admin/language'), url('index'), 1);
        }
    }

    /* 更新节点名称 */
    public function ajax_name() {
        $params = array();
        $params['id'] = (int)$_GET['id'];
        $params['name'] = trim($_GET['name']);
        if (empty($params['name'])) {
            showmessage('节点名称不能为空');
        }
        $result = $this->service->setField(['name' => $params['name']], ['id' => $params['id']]);
        if ($result === FALSE) {
            showmessage($this->service->error);
        } else {
            showmessage('节点名称修改成功', url('index'), 1);
        }
    }

    public function ajax_node() {
        $id = (int)$_GET['id'];
        $result = (array)$this->service->get_children($id);
        if ($result) {
            foreach ($result as $key => $value) {
                $value['_child'] = $this->service->count(array('parent_id' => $value['id']));
                $result[$key] = $value;
            }
        }
        $this->load->librarys('View')->assign('result',$result);
        $result = $this->load->librarys('View')->get('result');
        echo json_encode($result);
    }

    /* 添加节点 */
    public function add() {
        $parent_id = (int)$_GET['parent_id'];
        $parent_pos = array('顶级节点');
        if ($parent_id > 0) {
            $parent_pos = $this->service->fetch_position($parent_id);
        }
        if (checksubmit('dosubmit')) {
            $params = $_GET;
            $params['m'] = $params['mm'];
            $params['c'] = $params['cc'];
            $params['a'] = $params['aa'];
            if (FALSE === $this->service->update($params)) {
                showmessage($this->service->error);
            }
            showmessage('添加节点成功', url('index'), 1);
        } else {
            $this->load->librarys('View')->assign('parent_id',$parent_id)->assign('parent_pos',$parent_pos)->display('node_add');
        }
    }

    public function edit() {
        $id = $_GET['id'];
        if ($id < 1) {
            showmessage(lang('_param_error_'));
        }
        $r = $this->service->fetch_by_id($id);
        $parent_pos = array('顶级节点');
        if ($r['parent_id'] > 0) {
            $parent_pos = $this->service->fetch_position($r['id']);
        }
        if (checksubmit('dosubmit')) {
            $params = array_merge($r, $_GET);
            $params['m'] = $params['mm'];
            $params['c'] = $params['cc'];
            $params['a'] = $params['aa'];
            if (FALSE === $this->service->update($params)) {
                showmessage($this->service->error);
            }
            showmessage('节点更新成功', url('index'), 1);
        } else {
            $this->load->librarys('View')->assign('parent_pos',$parent_pos)->assign('r',$r)->display('node_edit');
        }
    }

    public function delete()
    {
        $ids = (array)$_GET['ids'];
        $result = $this->service->delete($ids);
        if ($result === false) {
            showmessage($this->service->error);
        }
        showmessage('节点删除成功');
    }
}