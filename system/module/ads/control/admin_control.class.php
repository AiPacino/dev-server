<?php
/**
 *	  后台内容设置控制器
 *	  [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *	  This is NOT a freeware, use is subject to license terms
 *
 *	  http://www.haidao.la
 *	  tel:400-600-2042
 */
hd_core::load_class('init', 'admin');
class admin_control extends init_control {

    protected $position_name_map = [];
    protected $tag_list = [
        0 => '请选择',
        1 => 'hot',
        2 => 'new',
        3 => 'youpin',
    ];
    protected $tag_list_map = [
        '0' => '',
        '1' => '热门',
        '2' => '新机',
        '3' => '优品',
    ];

    public function _initialize() {
		parent::_initialize();
		$this->model = $this->load->table('adv');
		$this->service = $this->load->service('adv');
        $this->upload = $this->load->service("upload/upload");

		$this->position_service = $this->load->service('adv_position');
		$this->position_model = $this->load->table('adv_position');

		$this->attachment_service = $this->load->service('attachment/attachment');
		$this->attachment_service->setConfig(authcode(serialize(array('module'=>'common','path' => 'common','mid' => 1,'allow_exts' => array('gif','jpg','jpeg','bmp','png'))), 'ENCODE'));
        $this->channel_service = $this->load->service('channel/channel');

		$positon_list = $this->position_service->get_lists();

		foreach( $positon_list as $item){
		    $this->position_name_map[$item['id']] = $item['name'];
		}

	}

	/*内容=====*/
	/**
	 * 获取内容方式列表
	 */
	public function index() {
		if(isset($_GET['position_id']) && intval($_GET['position_id'])>0){
		    $sqlmap = array(
		        'position_id'=>intval($_GET['position_id']),
//                'status' => 1,查询未禁用的数据
            );
		}else{
		    $sqlmap = [];
		}
        $position_list = $this->position_service->getposition();
        $position[0]='全部';
        foreach ($position_list as $key=>$value){
            $position[$value['id']]=$value['name'];
        }

		$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
		$additional = [
		    'page' => $_GET['page'],
		    'size' => $limit,
            'order' => 'status desc'
		];
		$ads = [];
		$count = $this->service->count($sqlmap);
		if( $count>0 ){
		    $ads = $this->service->get_lists($sqlmap,$additional);
		    foreach( $ads as &$item ){
			    $item['position_name'] = $this->position_name_map[$item['position_id']];
                if($item['status'] == 1){
                    $item['btn_name'] = "禁用";
                }
                else{
                    $item['btn_name'] = "启用";
                }
                if($item['flag'] == 'spu'){
                    $item['flag_name'] = '商品';
                }elseif ($item['flag'] == 'article'){
                    $item['flag_name'] = '文章';
                }elseif ($item['flag'] == 'link'){
                    $item['flag_name'] = '链接';
                }
                $channel_info = $this->channel_service->get_info($item['channel_id']);
                $item['channel_name'] = !empty($channel_info) ? $channel_info['name'] : '全部';
		    }
		}
		$pages = $this->admin_pages($count, $limit);
		$lists = array(
		    'th' => array(
			'title' => array('title' => '标题','length' => 30),
            'flag_name' => array('title' => '类型','length' => 15),
			'position_name' => array('title' => '位置','length' => 20),
            'channel_name' => array('title' => '渠道', 'length' => 15),
            'status' => array('title' => '启用','style' => 'ico_up_rack','length' => 10)
		    ),
		    'lists' => $ads,
		    'pages' => $pages,
		);
		$this->load->librarys('View')->assign('lists',$lists)->assign('position',$position)->display('index');
	}
	/**
	 * [ajax_push 推送商品，把商品推送到广告表中]
	 * @return [type]         [description]
	 */
	public function ajax_push() {
	    if (checksubmit('dosubmit')) {
	        // 参数校验
	        $_GET['type'] = trim($_GET['type']);
	        if( !in_array( $_GET['type'],array('spu','article') ) ){ showmessage("失败",'',1);}
	        if( !isset($_GET['id'])){ showmessage("参数错误",'',1);}

	        $result=$this->service->change_adv_info($_GET);
	        if($result){
	            showmessage(lang('push_success','ads/language'),'',1,'json');
	        }else{
	            showmessage("失败",'',1,'json');
	        }

	    } else {
	        // 参数校验
	        $type = trim($_GET['type']);
	        $ids = trim($_GET['id']);
	        if( !in_array( $type,array('spu','article') ) ){showmessage("失败",'',1);}
	        if( strlen($ids)<=0 ){showmessage("失败",'',1);}
	        // 获取所有推送位置
	        $pays = model('ads/adv_position','service')->getposition();
	        $list="<select name='position'>";
	        foreach ($pays as $k => $pay) {
	            $list.="<option value='".$pay[id]."'>".$pay['name']."</option>";
	        }
	        $list.="</select>";
	        $this->load->librarys('View')->assign('id',$ids)->assign('type',$type)->assign('list',$list)->display('alert_push');
	    }

	}
	/**
	 * 添加内容
	 */
	public function add() {
		$position = $this->position_service->getposition();
		if(!$position)showmessage(lang('_no_advposition_','ads/language'),url('position_add'),0);
		$position_format = format_select_data($position);
		if (checksubmit('dosubmit')) {
                    if(!empty($_FILES['content_pic']['name'])) {
                        $result = $this->upload->file_upload();
                        if($result['ret']!=0){
                        showmessage($this->attachment_service->error);
                    }
                    $_GET['content_pic']  = $result['img']['picturePath'];
                            }
                    $_GET['content'] = isset($_GET['content_text'])?''.$_GET['content_text']:'';;
                    $_GET['images'] = isset($_GET['type']) && $_GET['type'] == 0 ? $_GET['content_pic'] :'';
                    $_GET['sort'] = isset($_GET['sort'])?intval($_GET['sort']):0;
                    $_GET['tag'] = isset($_GET['tag'])&& key_exists($_GET['tag'], $this->tag_list)?$_GET['tag']:0;;
                    $_GET['flag'] = 'link';
                    if(empty($_GET['images'])){
                        unset($_GET['images']);
                    }
                    $r = $this->service->save($_GET,true);
                    if($r == FALSE)showmessage($this->service->error, url('add'), 0);
                    $this->attachment_service->attachment($_GET['content_pic'],'',false);
                    showmessage(lang('_update_adv_success_','ads/language'), url('index'), 1);
		} else {
            $channel_list = $this->channel_service->get_list(['status' => 1]);
		    $this->load->librarys('View')
                                ->assign('position',$position)
                                ->assign('tag_list',$this->tag_list)
                ->assign('channels', $channel_list)
                                ->assign('position_format',$position_format)
                                ->display('update');
		}
	}

	/**
	 * 编辑内容
	 */
	public function edit() {
            $position = $this->position_service->getposition();
            $position_format = format_select_data($position);
            $data = $this->service->fetch_by_id($_GET['id']);
            $channel_info = [];
            if($data['channel_id']){
                $channel_info = $this->channel_service->get_info($data['channel_id']);
            }
            if (checksubmit('dosubmit')) {
                if(!empty($_FILES['content_pic']['name'])) {
                    $result = $this->upload->file_upload();
                    if($result['ret']!=0){
                        showmessage($this->attachment_service->error);
                    }
                    $_GET['content_pic']  = $result['img']['picturePath'];
                }
                if( isset($_GET['content_text']) ) {
                    $_GET['content'] = ''.$_GET['content_text'];
                }
                if( isset($_GET['sort']) ) {
                    $_GET['sort'] = $_GET['sort'];
                }
                if( isset($_GET['tag']) ) {
                    $_GET['tag'] = $_GET['tag'];
                }
                $_GET['images'] = isset($_GET['type']) && $_GET['type'] == 0 ? $_GET['content_pic'] :'';
                $_GET = array_filter($_GET);
                $r = $this->service->save($_GET,false);
                $this->attachment_service->attachment($_GET['content_pic'],$data['content'],false);
                showmessage(lang('_update_adv_success_','ads/language'), url('index'), 1);
            } else {
                if(!$data['title']){
                }
                if($data['filag'] == "spu"){
                    //是否只编辑图片的标识
                    $data['edit_flag'] = true;
                }
                elseif($data['filag'] == "article"){
                    //是否只编辑图片的标识
                    $data['edit_flag'] = true;
                }
                elseif($data['filag'] == "link"){
                    //是否只编辑图片的标识
                    $data['edit_flag'] = false;
                }
                extract($data);
                $channel_list = $this->channel_service->get_list(['status' => 1]);
                $this->load->librarys('View')->assign('position',$position)
                                ->assign('tag_list',$this->tag_list)->assign('position_format',$position_format)->assign('channels', $channel_list)->assign('channel_info', $channel_info)->assign($data,$data)->display('update');
            }
	}

	/**
	 * 删除内容
	 */
	public function del() {
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$data = $this->service->fetch_by_id($_GET['id']);

		//$this->attachment_service->attachment('',$data['content'],false);
        if($data['status']==0){
            $status = 1;
        }
        else{
            $status = 0;
        }
        $id = $_GET['id'];
		$result = $this->service->set_status($id,$status);
		if($result === false) showmessage($this->service->error);
		showmessage('操作成功', url('index','position_id='.$_GET['position_id']), 1);
	}

	/**
	 * 编辑标题
	 */
	public function save_title() {
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$this->service->save_title(array('id' => $_GET['id'], 'title' => $_GET['title']));
		showmessage(lang('_update_adv_success_','ads/language'), url('index'), 1);
	}


	/*内容位=======================================================*/
	/**
	 * 获取内容方式列表
	 */
	public function position_index() {
		$sqlmap = array();
		$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
		$count = $this->position_service->count($sqlmap);
		$position = $this->position_service->get_lists($sqlmap,$_GET['page'],$limit);
		$pages = $this->admin_pages($count, $limit);
		$lists = array(
            'th' => array(
                'name' => array('title' => '名称','length' => 35,'style' => 'double_click'),
                'type_text' => array('title' => '类别','length' => 10),
                'width'=>array('title' => '宽度','length' => 10),
                'height' => array('title' => '高度','length' => 10),
                'adv_count'=>array('title' => '已发布','length' => 10),
                'status'=>array('title' => '启用','length' => 10,'style' => 'ico_up_rack'),
            ),
            'lists' => $position,
            'pages' => $pages,
            );
		$this->load->librarys('View')->assign('lists',$lists)->display('position_index');
	}

	/**
	 * 启用禁用内容位
	 */
	public function ajax_status() {
		$id = $_GET['id'];
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		if ($this->position_service->change_status($id)) {
			showmessage(lang('_status_success_','ads/language'), '', 1);
		} else {
			showmessage(lang('_status_error_','ads/language'), '', 0);
		}
	}

	/**
	 * 删除内容位
	 */
	public function position_del() {
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$result = $this->service->count(array('position_id' => array('IN', (array)$_GET['id'])));
		if($result > 0) showmessage(lang('no_delete_advposition_','ads/language'), url('position_index'), 0);
		$position = $this->position_service->fetch_by_id($_GET['id']);
		$this->attachment_service->attachment('',$position['defaultpic'],false);
		$this->position_service->delete((array)$_GET['id']);
//		$this->model->where(array('position_id' => array('IN', (array)$_GET['id'])))->delete();
		showmessage(lang('_del_adv_position_success_','ads/language'), url('position_index'), 1);
	}

	/**
	 * 添加内容位
	 */
	public function position_add() {
		if (checksubmit('dosubmit')) {
			if(!empty($_FILES['defaultpic']['name'])) {
				$_GET['defaultpic'] = $this->attachment_service->upload('defaultpic');
				if(!$_GET['defaultpic']){
					showmessage($this->attachment_service->error);
				}
			}
			$r = $this->position_service->save($_GET);
			if(!$r)showmessage($this->position_service->getError, url('position_add'), 0);
			$this->attachment_service->attachment($_GET['defaultpic'],'',false);
			showmessage(lang('_update_adv_position_success_','ads/language'), url('position_index'), 1);
		} else {
			$status = 1;
			$this->load->librarys('View')->display('position_update');
		}
	}

	/**
	 * 编辑内容位
	 */
	public function position_edit() {
		$position = $this->position_service->fetch_by_id($_GET['id']);
		if (checksubmit('dosubmit')) {
			if(!empty($_FILES['defaultpic']['name'])) {
				$_GET['defaultpic'] = $this->attachment_service->upload('defaultpic');
				if(!$_GET['defaultpic']){
					showmessage($this->attachment_service->error);
				}
			}
			$r = $this->position_service->save($_GET);
			$this->attachment_service->attachment($_GET['defaultpic'],$position['defaultpic'],false);
			showmessage(lang('_update_adv_position_success_','ads/language'), url('position_index'), 1);
		} else {
			extract($position);
			$this->load->librarys('View')->assign($position,$position)->display('position_update');
		}
	}

	/**
	 * 编辑标题
	 */
	public function position_save_name() {
		if(empty($_GET['formhash']) || $_GET['formhash'] != FORMHASH) showmessage('_token_error_');
		$this->position_service->save(array('id' => $_GET['id'], 'name' => $_GET['name']));
		showmessage(lang('_update_adv_position_success_','ads/language'), url('index'), 1);
	}

}
