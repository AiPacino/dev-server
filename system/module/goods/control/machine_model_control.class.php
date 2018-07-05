<?php
/**
 * 商品机型控制器
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/21 0021
 * Time: 下午 4:09
 */

hd_core::load_class('init', 'admin');
class machine_model_control extends init_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('goods/goods_machine_model');
        $this->brand_service = $this->load->service('goods/brand');
    }

    /**
     * 渠道列表
     */
    public function index(){
        $where = [];
        $options['limit'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $options['page'] = $_GET['page'];
        $options['orderby'] = 'id asc';
        $list = $this->service->get_list($where, $options);
        $count = $this->service->count($where);
        $pages = $this->admin_pages($count, $options['limit']);
        foreach ($list as &$item){
            $item['brand_id'] = $item['brand_name'];
        }
        $lists = array(
            'th' => array(
                'name' => array('style' => 'double_click','title' => '机型名称','length' => 40),
                'brand_id' => array('title' => '品牌名称','length' => 35),
                'status' => array('title' => '启用','style' => 'ico_up_rack','length' => 10)
            ),
            'lists' => $list,
            'pages' => $pages,
        );

        $this->load->librarys('View')->assign('lists', $lists)->display('machine_model_list');
    }


    public function add(){
        if(checksubmit('dosubmit')) {
            $result = $this->service->add_machine_model($_GET);
            if(!$result){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $this->load->librarys('View')->assign('brands', $this->brand_service->get_lists())->display('machine_model_edit');
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
            $result = $this->service->edit_machine_model($_GET);
            if($result === FALSE){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $this->load->librarys('View')
                ->assign('info',$info)
                ->assign('brands', $this->brand_service->get_lists())
                ->display('machine_model_edit');
        }
    }
    /**
     * [delete 删除渠道]
     * @return [type] [description]
     */
    public function delete(){
        $result = $this->service->delete($_GET['id']);
        if(!$result){
            showmessage($this->service->error);
        }else{
            showmessage(lang('_operation_success_'),url('index'),1);
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