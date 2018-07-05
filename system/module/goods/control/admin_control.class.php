<?php

hd_core::load_class('init', 'admin');

class admin_control extends init_control {

    protected $service = '';
    protected $brand;

    public function _initialize() {
        parent::_initialize();
        $this->spu_service = $this->load->service('goods/goods_spu');
        $this->sku_service = $this->load->service('goods/goods_sku');
        $this->cate_service = $this->load->service('goods/goods_category');
        $this->brand_service = $this->load->service('goods/brand');
        $this->upload_service = $this->load->service('upload/upload');
        $this->channel_service = $this->load->service('channel/channel');
        helper('attachment');
    }

    /**
     * [index 商品后台列表页]
     * @return [type] [description]
     */
    public function index() {
        $sqlmap = array();
        $_GET['limit'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $info = $this->spu_service->get_lists($_GET);
        $goods = $info['lists'];
		$zuqi_types = [
			'2' => '月',
			'1' => '天',
		];
		foreach( $goods as &$it ){
			$it['min_month_show'] = $it['min_month'].$zuqi_types[$it['min_zuqi_type']];
		}
		
        $count = $info['count'];
        $cache = $this->cate_service->get();
        $category = $this->cate_service->get_category_tree($cache);
        if ($_GET['catid']) {
            $cate = $this->cate_service->detail($_GET['catid'], 'id,name');
        }
        if ($_GET['brand_id']) {
            $brand = $this->brand_service->detail($_GET['brand_id'], 'id,name');
        }
        if($_GET['channel_id']) {
            $channel = $this->channel_service->get_info($_GET['channel_id']);
        }
        $brands = $this->brand_service->get_lists();
        $pages = $this->admin_pages((int) $count, $_GET['limit']);
        $lists = array(
            'th' => array(
                'sn' => array('title' => '商品货号', 'length' => 10, 'style' => 'double_click'),
                'name' => array('title' => '商品名称', 'length' => 20, 'style' => 'goods'),
                'channel_name' => array('title' => '渠道名称', 'length' => 10),
                'brand_name' => array('length' => 8, 'title' => '品牌&分类', 'style' => 'cate_brand'),
                'yiwaixian' => array('title' => '意外险/元', 'length' => 5),
                'min_price' => array('title' => '最小起租价/元', 'length' => 7),
//                'min_month' => array('title' => '起租', 'length' => 5),
                'min_month_show' => array('title' => '起租', 'length' => 5),
                'yajin' => array('title' => '押金', 'length' => 5),
                'buyout_price' => array('title' => '买断价格', 'length' => 5),
                'number' => array('title' => '库存', 'length' => 5),
                'sort' => array('title' => '排序', 'style' => 'double_click', 'length' => 5),
                'status' => array('title' => '上架', 'style' => 'ico_up_rack', 'length' => 5),
            ),
            'lists' => $goods,
            'pages' => $pages,
        );
        if ($_GET['label'] != 1 && isset($_GET['label'])) {
            unset($lists['th']['sort'], $lists['lists']['sort']);
            $lists['th']['name']['length'] = 25;
        }
        if ($_GET['label'] > 2) {
            unset($lists['th']['status'], $lists['lists']['status'],$lists['th']['buyout_price'],$lists['lists']['buyout_price']);
            $lists['th']['name']['length'] = 30;
            $lists['th']['yajin']['length'] = 10;
        }
        $channel_list = $this->channel_service->get_list(['status' => 1]);
        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->assign('goods', $goods)
            ->assign('category', $category)
            ->assign('cate', $cate)
            ->assign('brand', $brand)
            ->assign('brands', $brands)
            ->assign('channel', $channel)
            ->assign('channels', $channel_list)
            ->assign('pages', $pages)
            ->display('goods_list');
    }
    /**
     * [ajax_copy 复制一份商品到其他渠道]
     * @return [type]         [description]
     */
    public function ajax_copy() {
        if (checksubmit('dosubmit')) {
            // 参数校验
            $spu_id = trim($_POST['spu_id']);
            $channel_id = intval($_POST['channel_id']);
            if($spu_id < 0){showmessage("选择错误",'',1);}
            $this->order_service = $this->load->service('order2/order');
            $this->spu_table = $this->load->table('goods/goods_spu');
            $this->sku_table = $this->load->table('goods/goods_sku');
            // 开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0);
            }
            $spu_info =$this->spu_table->find($spu_id);
            unset($spu_info['id']);
            $spu_info['status'] =0;
            $spu_info['specs'] =json_encode($spu_info['specs']);
            $spu_info['imgs'] = json_encode($spu_info['imgs']);
            $spu_info['channel_id'] = $channel_id;
            $new_spu_id =$this->spu_table->add($spu_info);
            if(!$new_spu_id){
                $this->order_service->rollback();
                showmessage('spu添加失败', 'null');
            }
            $skus = $this->sku_table->where(array('spu_id' => $spu_id, 'status' => array('NEQ', -1)))->select();
            foreach ($skus as $k=>$v){
                unset($v['sku_id']);
                $v['spu_id'] =$new_spu_id;
                $v['sn'].='-'.$channel_id;
                $v['update_time'] =time();
                $v['spec'] = json_encode($v['spec']);
                $v['status'] =0;
                $new_sku_id =$this->sku_table->add($v);
                if(!$new_sku_id){
                    $this->order_service->rollback();
                    showmessage('渠道已经有该商品', 'null');
                }

            }
            $this->order_service->commit();
            showmessage("操作成功",'null',1,'json');


        } else {
            // 参数校验
            $spu_id = trim($_GET['spu_id']);
            if($spu_id < 0){showmessage("选择错误",'',1);}

            // 获取所有渠道
            $channels = $this->channel_service->get_list();
            $list="<select name='channel_id'>";
            foreach ($channels as $k => $v) {
                $list.="<option value='".$v[id]."'>".$v['name']."</option>";
            }
            $list.="</select>";
            $this->load->librarys('View')->assign('spu_id',$spu_id)->assign('list',$list)->display('alert_copy');
        }

    }

    public function ajax_spu_list() {
        $sqlmap = array();
        $_GET['limit'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $lists = $this->spu_service->get_lists($_GET);
        $lists['pages'] = $this->admin_pages($lists['count'], $_GET['limit']);
        $this->load->librarys('View')->assign('lists', $lists);
        $lists = $this->load->librarys('View')->get('lists');
        echo json_encode($lists);
    }

    public function spu_select() {
        $sqlmap = array();
        $_GET['limit'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $lists = $this->spu_service->get_lists($_GET);
        $goods = $lists['lists'];
        $count = $lists['count'];
        $cache = $this->cate_service->get();
        $category = $this->cate_service->get_category_tree($cache);
        if ($_GET['catid']) {
            $cate = $this->cate_service->detail($_GET['catid'], 'id,name');
        }
        if ($_GET['brand_id']) {
            $brand = $this->brand_service->detail($_GET['brand_id'], 'id,name');
        }
        $brands = $this->brand_service->get_lists();
        $pages = $this->admin_pages($count, $_GET['limit']);
        $this->load->librarys('View')->assign('lists', $lists)->assign('goods', $goods)->assign('category', $category)->assign('cate', $cate)->assign('brand', $brand)->assign('brands', $brands)->assign('pages', $pages)->display('ajax_spu_list_dialog');
    }

    /**
     * [goods_look_attr 查看子商品]
     * @return [type] [description]
     */
    public function sku_edit() {
        if (checksubmit('dosubmit')) {
            runhook('send_notice');
            $result = $this->sku_service->sku_edit($_GET);
            if (!$result) {
                showmessage($this->sku_service->error);
            } else {
                //获取用户ID
                $userId = ADMIN_ID;
                //获取操作人的IP：
                $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
                //操作功能ID
                $optionId = \zuji\debug\Opreation::GOODS_STE_SKU_EDIT;
                //操作备注
                $remark = '根据子商品sku_id为'.$_GET['sku_id'].'修改商品 imgs || thumb字段';
                //操作时间
                $dateline = time();
                //操作连接
                $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=sku_edit';
                $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
                showmessage(lang('_operation_success_'), url('index'));
            }
        } else {
            $info = $this->sku_service->fetch_by_id($_GET['sku_id'], 'show_index');
            $attachment_init = attachment_init(array('module' => 'goods', 'path' => 'goods', 'mid' => $this->admin['id'], 'allow_exts' => array('gif', 'jpg', 'peg', 'bmp', 'png')));
            $this->load->librarys('View')->assign('info', $info)->assign('attachment_init', $attachment_init)->display('sku_edit');
        }
    }

    /**
     * [ajax_get_sku ajax获取主商品的子商品]
     * @return [type] [description]
     */
    public function ajax_get_sku() {
        $result['lists'] = $this->sku_service->get_sku($_GET['id']);
        foreach($result['lists'] as &$val){
            if($val['zuqi_type'] ==1){
                $unit = "天";
            }
            elseif($val['zuqi_type'] ==2){
                $unit = "期";
            }
            $val['zuqi'] = $val['zuqi'].$unit;
        }
        $result['id'] = $_GET['id'];
        $this->load->librarys('View')->assign('result', $result);
        $result = $this->load->librarys('View')->get('result');
        echo json_encode($result);
    }

    /**
     * [goods_spec_modify 批量修改规格]
     * @return [type] [description]
     */
    public function goods_spec_modify() {
        $this->load->librarys('View')->display('goods_spec_modify');
    }

    /**
     * [goods_spec_pop 编辑商品规格]
     * @return [type] [description]
     */
    public function goods_spec_pop() {
        $specs = $this->load->service('goods/spec')->get_spec_name();
        $attachment_init = attachment_init(array('module' => 'goods', 'path' => 'goods', 'mid' => $this->admin['id'], 'allow_exts' => array('gif', 'jpg', 'jpeg', 'bmp', 'png')));
        $this->load->librarys('View')
                ->assign('specs', $specs)
                ->assign('selected', $selected)
                ->assign('attachment_init', $attachment_init)
                ->display('goods_spec_popup');
    }

    /**
     * [goods_add 商品编辑]
     * @return [type] [description]
     */
    public function goods_add() {
        $id = (int) $_GET['id'];
        if (checksubmit('dosubmit')) {
            $result = $this->spu_service->goods_add($_GET);
            runhook('make_watermark', $_GET);
            if ($result === false) {
                showmessage($this->spu_service->error);
            } else {
                //获取用户ID
                $userId = ADMIN_ID;
                //获取操作人的IP：
                $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
                //操作功能ID
                $optionId = \zuji\debug\Opreation::GOODS_STE_ADD;
                //操作备注
                $remark = '编辑spu_id：'.$result['spu_id'].'--';
                if(isset($result['new'])){
                    $sku_ids = implode(',', array_column($result['new'], 'sku_id'));
                    $remark .= '添加的子商品有：'.$sku_ids;
                }
                if (isset($result['edit'])){
                    $sku_ids = implode(',', array_column($result['edit'], 'sku_id'));
                    $remark .= '修改的子商品有：'.$sku_ids;
                }
                if (!empty($result['del'])){
                    $sku_ids = implode(',', $result['del']);
                    $remark .= '删除的子商品有：'.$sku_ids;
                }

                //操作时间
                $dateline = time();
                //操作连接
                $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=goods_add';
                $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
                showmessage(lang('_operation_success_'), url('index'));
            }
        } else {
            $goods = array();
            if ($id > 0) {
                $goods = (array) $this->spu_service->get_by_id($_GET['id']);
                if ($goods) {
                    $goods['extra']['attr'] = $this->load->service('goods/type')->get_type_by_catid($goods['spu']['catid']);
                    $goods['extra']['attr']['types'][0] = '请选择商品类型';
                    $goods['extra']['specs'] = $this->spu_service->get_goods_specs($goods['_sku']);
                    $goods['extra']['album'] = $this->spu_service->get_goods_album($goods);

                    $goods['spu']['payment_rule_id'] = $this->load->service('payment/goods_spu_rule')->where(['spu_id' => $_GET['id'], 'status' => 1])->getField('rule_id', true);
                }
            }
            $delivery_template = $this->load->service('order/delivery_template')->getField(array(), 'id,name', true);
            $goods['extra']['attachment_init'] = attachment_init(array('path' => 'goods', 'mid' => $this->admin['id'], 'allow_exts' => array('jpg', 'jpeg', 'bmp', 'png')));

            $where = [
                '_logic' => 'or',
                'id' => 1,
                '_complex' => [
                    '_logic' => 'and',
                    'status' => 1,
                    'alone_goods' => 1
                ]
            ];
            $channel_list = $this->channel_service->get_list($where);
            $machine_list = $this->load->service('goods/goods_machine_model')->get_list(['status' => 1]);

            //支付列表
            $options['order'] = 'id desc';
            $payment_list = $this->load->service('payment/payment_rule')->arrListByPage(0, 0, ['status' => 1], $options);
            $payments = [];
            foreach ($payment_list['rows'] as $item){
                $payments[$item['id']] = $item['name'];
            }
            //合同模板列表
            $this->contract = $this->load->table("contract/contract");
            $contract = $this->contract->select();
            foreach ($contract as $key=>$v){
                $contract_list[$v['id']] = $v;
            }
            $this->load->librarys('View')
                    ->assign('goods', (array) $goods)
                    ->assign('delivery_template', $delivery_template)
                    ->assign('channels', $channel_list)
                    ->assign('machines', $machine_list)
                    ->assign('payment_list', $payments)
                    ->assign('contract_list', $contract_list)
                    ->display('goods_add');
        }
    }

    /**
     * [ajax_brand ajax查询品牌]
     * @return [type] [description]
     */
    public function ajax_brand() {
        $result = $this->brand_service->ajax_brand($_GET['brandname']);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
    }

    /**
     * [ajax_channel ajax查询渠道]
     * @return [type] [description]
     */
    public function ajax_channel() {
        $result = $this->channel_service->ajax_channel($_GET['name']);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
    }

    public function ajax_machine() {
        $result = $this->load->service('goods/goods_machine_model')->ajax_machine($_GET['name']);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
    }

    /**
     * [ajax_sn ajax更改商品货号]
     * @return [type] [description]
     */
    public function ajax_sn() {
        $_GET['sn'] = $_GET['name'];
        unset($_GET['name']);
        $result = $this->spu_service->change_spu_info($_GET);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_SN_EDIT;
            //操作备注
            $remark = '根据商品ID为'.$_GET['id'].'修改商品货号 sn 字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_sn';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1, '', 'json');
        }
    }

    /**
     * [ajax_name ajax更改商品名称]
     * @return [type] [description]
     */
    public function ajax_name() {
        $result = $this->spu_service->change_spu_info($_GET);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_NAME_EDIT;
            //操作备注
            $remark = '根据商品ID为'.$_GET['id'].'修改商品名称 name 字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_name';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1, '', 'json');
        }
    }

    /**
     * [ajax_recover 批量恢复商品]
     * @param  [array] $id [要恢复的商品id]
     * @return [type]     [description]
     */
    public function ajax_recover() {
        $result = $this->spu_service->recover($_GET['id']);
        if (!$result) {
            showmessage($this->spu_service->error);
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_RECOVER;
            //操作备注
            $remark = '根据子商品sku_id为'.$_GET['id'].'批量恢复商品 status字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_recover';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'));
        }
    }

    /**
     * 更改商品名称
     */
    public function ajax_sku_name() {
        $_GET['sku_id'] = $_GET['id'];
        $_GET['sku_name'] = $_GET['name'];
        $result = $this->sku_service->change_sku_info($_GET);
        if (!$result) {
            showmessage($this->spu_service->error);
        }
        //获取用户ID
        $userId = ADMIN_ID;
        //获取操作人的IP：
        $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        //操作功能ID
        $optionId = \zuji\debug\Opreation::GOODS_STE_SKU_NAME_EDIT;
        //操作备注
        $remark = '根据子商品sku_id为'.$_GET['id'].'修改商品名称 sku_name 字段';
        //操作时间
        $dateline = time();
        //操作连接
        $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_sku_name';
        $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
        showmessage(lang('_operation_success_'));
    }
    /**
     * 更改买断价格
     */
    public function ajax_buyout_price() {
        $_GET['sku_id'] = $_GET['sku_id'];
        $_GET['buyout_price'] = $_GET['buyout_price'];
        $result = $this->sku_service->change_sku_info($_GET);
        if (!$result) {
            showmessage($this->spu_service->error);
        }
        //获取用户ID
        $userId = ADMIN_ID;
        //获取操作人的IP：
        $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        //操作功能ID
        $optionId = \zuji\debug\Opreation::GOODS_STE_SKU_NAME_EDIT;
        //操作备注
        $remark = '根据子商品sku_id为'.$_GET['id'].'修改商品买断价格 buyout_price 字段';
        //操作时间
        $dateline = time();
        //操作连接
        $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_buyout_price';
        $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
        showmessage(lang('_operation_success_'));
    }
    /**
     * 更改商品货号
     */
    public function ajax_sku_sn() {
        $_GET['sku_id'] = $_GET['id'];
        $_GET['sn'] = $_GET['name'];
        $result = $this->sku_service->change_sku_info($_GET);
        if (!$result) {
            showmessage($this->spu_service->error);
        }
        //获取用户ID
        $userId = ADMIN_ID;
        //获取操作人的IP：
        $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        //操作功能ID
        $optionId = \zuji\debug\Opreation::GOODS_STE_SKU_SN_EDIT;
        //操作备注
        $remark = '根据子商品sku_id为'.$_GET['id'].'修改商品货号 sn 字段';
        //操作时间
        $dateline = time();
        //操作连接
        $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_sku_sn';
        $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
        showmessage(lang('_operation_success_'));
    }

    /**
     * [ajax_name ajax更改商品上下架]
     * @return [type] [description]
     */
    public function ajax_status() {
        $result = $this->spu_service->change_status($_GET['id'], $_GET['type']);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_STATUS;
            //操作备注
            $remark = "根据商品ID为".$_GET['id']."修改商品上下架 status字段";
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_status';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1, '', 'json');
        }
    }

    /**
     * [ajax_name ajax更改商品是否在列表显示]
     * @return [type] [description]
     */
    public function ajax_show() {
        $result = $this->sku_service->change_show_in_lists($_GET['sku_id']);
        if (!$result) {
            showmessage($this->sku_service->error, '', 0);
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_SHOW;
            //操作备注
            $remark ="根据商品sku_id为".$_GET['sku_id']."修改商品列表显示状态 show_in_lists字段";
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_show';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1);
        }
    }

    /**
     * [ajax_name ajax更改商品属性]
     * @return [type] [description]
     */
    public function ajax_sku() {
        $result = $this->sku_service->change_sku_info($_GET);
        if (!$result) {
            showmessage($this->sku_service->error, '', 0);
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_ATTR;
            //操作备注
            $remark = '根据商品sku_id为'.$_GET['sku_id'].'修改商品属性';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_sku';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1);
        }
    }

    /**
     * [ajax_name ajax更改排序]
     * @return [type] [description]
     */
    public function ajax_sort() {
        $result = $this->spu_service->change_spu_info($_GET);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_SORT;
            //操作备注
            $remark = '根据商品id'.$_GET['id'].'修改排序 sort 字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_sort';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1, '', 'json');
        }
    }

    /**
     * [ajax_del 删除商品，在商品列表里删除只改变状态，在回收站里删除直接删除]
     * @return [type]         [description]
     */
    public function ajax_del() {
        $result = $this->spu_service->delete($_GET);
        if (!$result) {
            showmessage($this->spu_service->error);
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_DEL;
            //操作备注
            $remark = '根据商品ID为'.$_GET['id'].'删除商品 status字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_del';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), url('index'), 1);
        }
    }

    /**
     * [ajax_del 删除商品，在商品列表里删除只改变状态，在回收站里删除直接删除]
     * @return [type]         [description]
     */
    public function ajax_del_sku() {
        $result = $this->sku_service->ajax_del_sku($_GET);
        if (!$result) {
            showmessage($this->spu_service->error);
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_SKU_DEL;
            //操作备注
            $remark = '根据子商品ID为'.$_GET['sku_id'].'删除商品 status字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_del_sku';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), url('index'), 1);
        }
    }

    /**
     * [ajax_statusext ajax更改商品标签状态]
     * @return [type] [description]
     */
    public function ajax_statusext() {
        $result = $this->sku_service->ajax_statusext($_GET);
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_STATUSSEXT_EDIT;
            //操作备注
            $remark = '根据sku_id更改商品'.$_GET['sku_id'].'标签状态 status_ext字段';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=ajax_statusext';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1, '', 'json');
        }
    }

    /**系统历史商品上传方法
     * [upload 上传商品图片]
     * @return [type] [description]
     */
/*    public function upload() {
        $result['url'] = $this->load->service('attachment/attachment')->setConfig($_GET['code'])->upload('upfile');
        $this->load->service('attachment/attachment')->attachment($result['url'], '');
        $result['img_id'] = $_GET['img_id'];
        if (!$result) {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        } else {
            $this->load->librarys('View')->assign('result', $result);
            $result = $this->load->librarys('View')->get('result');
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
    }*/
    /**第三方接口上传方法
     * [upload 上传商品图片]
     * @return [type] [description]
     */
    public function upload() {
        $data = $this->upload_service->file_upload();
        if($data && $data['ret']==0){
            $result['url'] =$data['img']['picturePath'];
            $result['img_id'] = $_GET['img_id'];
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::GOODS_STE_UPLOAD;
            //操作备注
            $remark = '上传商品图片';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=goods&c=admin&a=upload';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('_operation_success_'), '', 1, $result, 'json');
        }
        else
        {
            showmessage($this->spu_service->error, '', 0, '', 'json');
        }

    }
    //获取类型数据
    public function ajax_get_attr() {
        $result = $this->load->service('goods/type')->get_type_by_catid($_GET['id']);
        showmessage(lang('_operation_success_'), '', 1, $result);
    }

    /**
     * 根据channnel_id获取支付规则列表
     */
    public function ajax_get_payment_rule_list(){
        $channel_id = $_POST['channel_id'];
        $rule_list = $this->load->service('payment/payment_rule_channel')->get_rule_list($channel_id);
        $payment_list = [];
        foreach ($rule_list as $item){
            $payment_list[$item['id']] = $item['name'];
        }

        echo form::input('checkbox', 'rule[payment_rule_id][]', '', '支付方式<b style="color:red">*</b>：', '【必填】请填写支付方式。', array('items' => $payment_list,'colspan' => count($payment_list)));
        exit();
    }

}
