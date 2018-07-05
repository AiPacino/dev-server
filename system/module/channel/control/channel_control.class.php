<?php
/**
 * 商品渠道管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/18 0018
 * Time: 上午 11:36
 */

hd_core::load_class('init', 'admin');
class channel_control extends init_control
{
    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('channel/channel');
    }

    /**
     * 渠道列表
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
        if(isset($_GET['status']) && $_GET['status'] != -1){
            $where['status'] = $_GET['status'];
        }else{
            $_GET['status'] = -1;
        }
        if(!empty($where1))
            $where['_complex'] = $where1;

        $options['size'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $options['page'] = $_GET['page'];
        $options['orderby'] = 'sort asc';
        $type = $this->service->get_list($where, $options);
        $count = $this->service->count($where);
        $pages = $this->admin_pages($count, $options['size']);
        foreach ($type as &$item){
            $item['alone_goods'] = $this->service->enum_alone_goods[$item['alone_goods']];
        }
        $lists = array(
            'th' => array(
                'id' => ['title' => '渠道ID', 'length' => 10],
                'name' => array('style' => 'double_click','title' => '渠道名称','length' => 20),
                'contacts' => array('length' => 15,'title' => '联系人'),
                'phone' => array('title' => '联系方式','length' => 10),
                'alone_goods' => array('title' => '是否有独立商品','length' => 15),
                'sort' => array('style' => 'double_click','length' => 10,'title' => '排序'),
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
            $url = url('channel/channel/index',array('status'=>$k));
            $status_tabs[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
        }

        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->assign('status_tabs', $status_tabs)
            ->display('channel_list');
    }


    public function add(){
        if(checksubmit('dosubmit')) {
            $result = $this->service->add_channel($_GET);
            if(!$result){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $this->load->librarys('View')->display('channel_edit');
        }
    }

    /**
     * [edit 渠道编辑]
     * @return [type] [description]
     */
    public function edit(){
        $info = $this->service->get_info($_GET['id']);
        if(empty($info)){
            showmessage('渠道不存在');
        }
        if(checksubmit('dosubmit')) {
            $result = $this->service->edit_channel($_GET);
            if($result === FALSE){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $this->load->librarys('View')->assign('info',$info)->display('channel_edit');
        }
    }
    /**
     * [delete 删除渠道]
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
    /**
     * [ajax_sort 改变排序]
     */
    public function ajax_sort(){
        $result = $this->service->change_info($_GET);
        if(!$result){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }
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


}