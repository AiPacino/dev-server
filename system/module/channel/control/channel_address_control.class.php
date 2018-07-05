<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/19 0019
 * Time: 下午 3:31
 */

hd_core::load_class('init', 'admin');
class channel_address_control extends init_control
{

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('channel/channel_address');
        $this->channel_service = $this->load->service('channel/channel');
        $this->appid_service = $this->load->service('channel/appid');
        $this->district_service = $this->load->service('admin/district');
    }

    public function index(){
        // 查询条件
        $where = [];
        if($_GET['channel_id']>'0' ){
            $where['channel_id'] = intval($_GET['channel_id']);
        }
        if($_GET['keywords']!=''){
            $where['name'] = array('like','%'.$_GET['keywords'].'%');
        }

        $options['size'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $options['page'] = $_GET['page'];
        $options['orderby'] = 'id asc';
        $address_list = $this->service->get_list($where, $options);
        $count = $this->service->count($where);
        $pages = $this->admin_pages($count, $options['size']);
        if($address_list){
            foreach ($address_list as &$item){
                $province = $this->district_service->get_name($item['province_id']);
                $city = $this->district_service->get_name($item['city_id']);
                $country = $this->district_service->get_name($item['country_id']);
                $item['address'] = $province . ' ' . $city . ' ' . $country . ' ' . $item['address'];
                $item['type'] = $this->service->enum_type[$item['type']];
                $channel_info = $this->channel_service->get_info($item['channel_id']);
                $item['channel_id'] = $channel_info['name'];
            }
        }

        $lists = array(
            'th' => array(
                'name' => array('title' => '收货人','length' => 10),
                'channel_id' => array('title' => '渠道','length' => 10),
                'type' => array('title' => '类型','length' => 10),
                'address' => array('title' => '详细地址','length' => 30),
                'zipcode' => array('title' => '邮政编码','length' => 10),
                'remark' => array('title' => '备注','length' => 15),
            ),
            'lists' => $address_list,
            'pages' => $pages,
        );

        $channel_list = $this->channel_service->get_list(['status' => 1]);
        $id_arr = array_column($channel_list, 'id');
        $name_arr = array_column($channel_list, 'name');
        $channel_list = array_combine($id_arr, $name_arr);
        $channel_list[0] = '全部';
        ksort($channel_list);
        $this->load->librarys('View')->assign('lists', $lists)->assign('channel_list', $channel_list)->display('channel_address_list');
    }

    public function add(){
        if(checksubmit('dosubmit')) {
            $list = $this->load->service('admin/district')->fetch_parents( $_GET['district_id'] );
            foreach( $list as $it){
                if($it['level']==3){
                    $_GET['country_id'] = $it['id'];
                }elseif($it['level']==2){
                    $_GET['city_id'] = $it['id'];
                }elseif($it['level']==1){
                    $_GET['province_id'] = $it['id'];
                }
            }
            $result = $this->service->edit_channel_address($_GET);
            if(!$result){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $channel_list = $this->channel_service->get_list(['status' => 1]);
            $type_arr = $this->service->enum_type;
            $address_info['cids']=[100000];
            $address_info['cid']=0;
            $this->load->librarys('View')
                ->assign('channels', $channel_list)
                ->assign('address_info',$address_info)
                ->assign('type_arr', $type_arr)
                ->display('channel_address_edit');
        }
    }

    /**
     * [edit 地址编辑]
     * @return [type] [description]
     */
    public function edit(){
        $address_info = $this->service->get_info($_GET['id']);
        if(empty($address_info)){
            showmessage('地址不存在');
        }
        if(checksubmit('dosubmit')) {
            $list = $this->load->service('admin/district')->fetch_parents( $_GET['district_id'] );
            foreach( $list as $it){
                if($it['level']==3){
                    $_GET['country_id'] = $it['id'];
                }elseif($it['level']==2){
                    $_GET['city_id'] = $it['id'];
                }elseif($it['level']==1){
                    $_GET['province_id'] = $it['id'];
                }
            }
            $result = $this->service->edit_channel_address($_GET);
            if($result === FALSE){
                showmessage($this->service->error);
            }else{
                showmessage(lang('_operation_success_'),url('index'));
            }
        }else{
            $channel_list = $this->channel_service->get_list(['status' => 1]);
            $address_info['cids']=[100000,$address_info['province_id'],$address_info['city_id'],$address_info['country_id']];
            $address_info['cid']=$address_info['country_id'];
            $type_arr = $this->service->enum_type;
            $this->load->librarys('View')
                ->assign('channels', $channel_list)
                ->assign('address_info',$address_info)
                ->assign('type_arr', $type_arr)
                ->display('channel_address_edit');
        }
    }
    
    /**
     * [delete 删除地址]
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
     * [ajax_name ajax更改名称]
     * @return [type] [description]
     */
    public function ajax_name(){
        $result = $this->service->change_info($_GET);
        if(!$result){
            showmessage($this->service->error,'',0,'','json');
        }else{
            showmessage(lang('_operation_success_'),'',1,'','json');
        }
    }

}