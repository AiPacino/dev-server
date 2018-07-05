<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19 0019
 * Time: 上午 11:37
 */

hd_core::load_class('init', 'admin');
class channel_appid_control extends init_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('channel/channel_appid');
        $this->channel_service = $this->load->service('channel/channel');
    }

    /**
     * 列表
     */
    public function index(){
        $where = $where1 = [];
        $where['_logic'] = 'and';
        if($_GET['keywords']){
            $keywords = trim($_GET['keywords']);
            $where1['name'] = array('like','%'.$keywords.'%');
            $where1['id'] = intval($keywords);
            $where1['_logic'] = 'or';
        }
        if($_GET['channel_id'] > 0){
            $where['channel_id'] = intval($_GET['channel_id']);
        }
        if($_GET['type'] > 0){
            $where['type'] = intval($_GET['type']);
        }
        if(isset($_GET['status']) && $_GET['status'] != -1){
            $where['status'] = $_GET['status'];
        }else{
            $_GET['status'] = -1;
        }
        if(!empty($where1))
            $where['_complex'] = $where1;
        $options['size'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $options['page'] = $_GET['page'];
        $options['orderby'] = 'id asc';
        $type = $this->service->get_list($where, $options);
        $count = $this->service->count($where);
        $pages = $this->admin_pages($count, $options['size']);
        if($type){
            foreach ($type as &$item){
                $channel_info = $this->channel_service->get_info($item['channel_id']);
                $item['channel_id'] = $channel_info['name'];
                $item['type'] = $this->service->enum_type[$item['type']];
            }
        }

        $lists = array(
            'th' => array(
                'id' => ['title' => 'appid','length' => 10],
                'name' => array('style' => 'double_click','title' => '入口名称','length' => 30),
                'type' => array('title' => '类型','length' => 20),
                'channel_id' => array('title' => '渠道','length' => 20),
                'status' => array('title' => '启用','style' => 'ico_up_rack','length' => 10)
            ),
            'lists' => $type,
            'pages' => $pages,
        );

        //状态
        $status_list = [
            '-1' => '全部',
            '1' => '启用',
            '0' => '禁用'
        ];
        $status_tabs = [];
        foreach( $status_list as $k=>$name ){
            $css = '';
            if ($_GET['status'] == $k){
                $css = 'current';
            }
            $url = url('channel/channel_appid/index',array('status'=>$k));
            $status_tabs[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
        }

        $channel_list = $this->channel_service->get_list(['status' => 1]);
        $id_arr = array_column($channel_list, 'id');
        $name_arr = array_column($channel_list, 'name');
        $channel_list = array_combine($id_arr, $name_arr);
        $channel_list[0] = '全部';
        ksort($channel_list);
        $type_list = $this->service->enum_type;
        array_unshift($type_list, '全部');
        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->assign('channel_list', $channel_list)
            ->assign('type_list', $type_list)
            ->assign('status_tabs', $status_tabs)
            ->display('appid_list');
    }

    /**
     * [add 添加]
     * @return [type] [description]
     */
    public function add(){
        if(checksubmit('dosubmit')) {
            $result = $this->service->add_appid($_GET);
            if(!$result){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $channel_list = $this->channel_service->get_list(['status' => 1]);
            $type_list = $this->service->enum_type;
            $this->load->librarys('View')
                ->assign('channels', $channel_list)
                ->assign('type_list', $type_list)
                ->display('appid_edit');
        }
    }

    /**
     * [edit 编辑]
     * @return [type] [description]
     */
    public function edit(){
        $info = $this->service->get_info($_GET['id']);
        if(empty($info)){
            showmessage('渠道不存在');
        }
        if(checksubmit('dosubmit')) {
            $result = $this->service->edit_appid($_GET);
            if($result === FALSE){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $channel_list = $this->channel_service->get_list();
            $type_list = $this->service->enum_type;
            $this->load->librarys('View')->assign('info',$info)
                ->assign('channels', $channel_list)
                ->assign('type_list', $type_list)
                ->display('appid_edit');
        }
    }

    /**
     * [delete 删除]
     * @return [type] [description]
     */
    /*public function delete(){
        $result = $this->service->delete($_GET['id']);
        if(!$result){
            showmessage($this->service->error);
        }else{
            showmessage(lang('_operation_success_'),url('index'),1);
        }
    }*/

    /**
     * [ajax_name ajax更改名称]
     * @return [type] [description]
     */
    public function ajax_name(){
        $result = $this->service->change_info($_GET);
        if($result === false){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

    /**
     * [ajax_status 更改状态]
     */
    public function ajax_status(){
        $result = $this->service->change_status($_GET['id']);
        if(!$result){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

    public function ajax_appid(){
        $result = $this->service->ajax_appid($_GET['name']);
        if (!$result) {
            showmessage($this->service->error, '', 0, '', 'json');
        } else {
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
    }
}