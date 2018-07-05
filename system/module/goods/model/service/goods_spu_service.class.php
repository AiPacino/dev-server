<?php
/**
 *		商品模型数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class goods_spu_service extends service {
    public function _initialize() {
        $this->spu_db = $this->load->table('goods/goods_spu');
        $this->sku_db = $this->load->table('goods/goods_sku');
        $this->sku_service = $this->load->service('goods/goods_sku');
        $this->cate_service = $this->load->service('goods/goods_category');
        $this->goodsattr_db = $this->load->table('goods/goods_attribute');
        $this->goods_index_db = $this->load->table('goods/goods_index');
        $this->spec = $this->load->service('goods/spec');
        $this->brand_db = $this->load->table('goods/brand');
        $this->adv_db = $this->load->table('ads/adv');
    }

    /**
     * 获取商品万能表格列表
     */
    public function get_query_all(){
        // 全部商品 spu表 casualty
        $lists = $this->spu_db->field("id,name,subtitle,thumb,imgs,brand_id,description,sku_total,yiwaixian,min_price,min_month")->where(array('status'=>1))->select();
        foreach ($lists as &$item){
            $item['flag'] = 'spu';
            $_imgs = json_decode($item['imgs'],true);
            $item['imgs'] = $_imgs?$_imgs:[];
        }
        // 全部品牌
        $brands = $this->brand_db->field("id,name")->where("status=1")->select();

        return array('brand_list' => $brands,'spu_list' => $lists);
    }
    /**
     * 获取商品详情
     */
    public function get_query_one($id){
        if ( $id < 1) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $spu_row = $this->spu_db->field("id,name,subtitle,thumb,imgs,brand_id,description,sku_total,yiwaixian,min_price,min_month,machine_id")->where(array('id'=>$id))->find();
        $sku_select = $this->sku_db->field("sku_id,sku_name,number,yajin,shop_price,zuqi,buyout_price,spec")->where(array('spu_id'=>$spu_row['id']))->select();
        $spu_row['flag'] = 'spu';
        $spu_row['sku_list']=$sku_select;
        return array('spu_info'=>$spu_row);
    }

    /**
     * 获取商品万能表格列表
     * @params array $params
     * array(
     *      'label' => '',  // 【必选】tab标识：1：“全部商品”；2：“下架商品”；2：“库存警告商品”；3：“回收站”
     *      'catid' => '',  // 【必选】分类ID
     *      'brand_id' => '',//【必选】品牌ID
     *      'keyword' => 'keyword',  // 搜索关键字
     * )
     */
    public function get_lists($params=array()){
        $lists = $result = array();
        // 全部商品搜索，主要查询 spu表
        if($params['label'] == 1 || $params['label'] == FALSE){
            $params['status'] = array('NEQ',-1);
            if(!empty($params['catid'])){
                if($this->cate_service->has_child($params['catid'])){
                    $params['catid'] = $this->cate_service->get_child($params['catid']);
                }else{
                    $params['catid'] = array(0 => $params['catid']);
                }
            }
            // 搜索 spu ID
            if($params['keyword']){
                $keyword = trim($params['keyword']);
                $keyword_sql = $this->keyword_search($keyword);
                $lists['sure_sku'] = $this->sure_sku($keyword);
                $count  = $this->spu_db->status($params['status'])->category($params['catid'])->brand($params['brand_id'])->channel2($params['channel_id'])->where($keyword_sql)->count();
                $result = $this->spu_db->status($params['status'])->category($params['catid'])->brand($params['brand_id'])->channel2($params['channel_id'])->page($params['page'])->limit($params['limit'])->where($keyword_sql)->order('sort asc,id desc')->getField('id',TRUE);
            }else{
                $result = $this->spu_db->status($params['status'])->category($params['catid'])->brand($params['brand_id'])->channel2($params['channel_id'])->page($params['page'])->limit($params['limit'])->order('sort asc,id desc')->getField('id',TRUE);
                $count  = $this->spu_db->status($params['status'])->category($params['catid'])->brand($params['brand_id'])->channel2($params['channel_id'])->count();
            }
            if(empty($result)){
                $this->error = lang('_select_not_exist_');
                return FALSE;
            }
            // 循环获取 SPU信息 和 附加信息：品牌，分类，SKU
            foreach ($result as $key => $value) {
                $lists['lists'][] = $this->get_by_id($value,'brand,category,sku,channel');
            }

            // 整理返回结果
            $result = array();
            foreach ($lists['lists'] AS $list) {
                $keys = array_keys($list['_sku']);
                $result[] = array(
                    'id' => $list['spu']['id'],
                    'sn' => $list['spu']['sn'],
                    'name' => $list['spu']['name'],
                    'channel_name' => $list['_channel']['name'],
                    'brand_name' => $list['_brand']['name'],
                    'yiwaixian' => $list['spu']['yiwaixian'],
                    'min_price' => $list['spu']['min_price'],
                    'max_price' => $list['spu']['max_price'],
                    'min_month' => $list['spu']['min_month'],
                    'min_zuqi_type' => $list['spu']['min_zuqi_type'],
                    'yajin' => '-',
                    'buyout_price' => '-',
                    'number' => $list['spu']['sku_total'],
                    'sort' => $list['spu']['sort'],
                    'status' => $list['spu']['status'],
                    // 缩略图，如果SPU没有图片，默认使用第1个SKU的图片
                    'thumb' => $list['spu']['thumb'] ? $list['spu']['thumb'] : $list['_sku'][$keys[0]]['thumb'],
                    'cate_name' => $list['_category']['parent_name'],

                );
            }
            $lists['count'] = $count;
        }else{
            // 其他情况，主要是对sku表的查询
            $sqlmap = array();
            switch ($params['label']) {
                // 下架商品
                case '2':
                    $sqlmap[config("DB_PREFIX").'goods_sku.status'] = 0;
                    break;
                // 库存警告
                case '3':
                    $sqlmap[config("DB_PREFIX").'goods_sku.status'] = 1;
                    $sqlmap['number'] = array('EXP','<='.config("DB_PREFIX").'goods_spu.warn_number');
                    break;
                // 回收站
                case '4':
                    $sqlmap[config("DB_PREFIX").'goods_sku.status'] = -1;
                    break;
                default:
                    break;
            }
            if(!empty($params['catid'])){
                if($this->cate_service->has_child($params['catid'])){
                    $catid = $this->cate_service->get_child($params['catid']);
                }else{
                    $catid= array(0 => $params['catid']);
                }
                $sqlmap['catid'] = array('IN',$catid);
            }
            if(!empty($params['brand_id'])){
                $sqlmap['brand_id'] = $params['brand_id'];
            }
            if(!empty($params['channel_id'])){
                $sqlmap['channel_id'] = $params['channel_id'];
            }
            if(!empty($params['status_ext'])){
                $sqlmap['status_ext'] = $params['status_ext'];
            }
            if(!empty($params['keyword'])){
                $sqlmap['sku_name|'.config("DB_PREFIX").'goods_sku.sn|barcode'] = array("LIKE", '%'.$params["keyword"].'%');
            }
            $spu = config("DB_PREFIX").'goods_spu';
            $_out_goods = $this->sku_db->join($spu.' on '.'id = spu_id')->where($sqlmap)->page($params['page'])->limit($params['limit'])->getField('sku_id',TRUE);
            $count = $this->sku_db->field($spu.'.sn AS osn')->join($spu.' on '.'id = spu_id')->where($sqlmap)->count();
            foreach ($_out_goods as $key => $value) {
                $lists['lists'][] = $this->sku_service->goods_detail($value,'spu,brand,channel',FALSE);
            }
            $lists['count'] = $count;
            $result = array();
            foreach ($lists['lists'] AS $list) {
                $result[] = array(
                    'id' => $list['sku_id'],
                    'sn' => $list['sn'],
                    'name' => $list['sku_name'],
                    'channel_name' => $list['channel']['name'],
                    'brand_name' => $list['brand']['name'],
                    'yiwaixian' => $list['yiwaixian'],
                    'min_price' => $list['min_price'],
                    'max_price' => $list['max_price'],
                    'min_month' => $list['min_month'],
                    'min_zuqi_type' => $list['min_zuqi_type'],
                    'yajin' => $list['yajin'],
                    'buyout_price' => $list['buyout_price'],
                    'number' => $list['sku_total'],
                    'sort' => $list['sort'],
                    'status' => $list['status'],
                    'thumb' => $list['thumb'],
                    'cate_name' => $list['catname'],
                    'spec_show' => $list['spec_show']
                );
            }
        }
        return array('lists' => $result,'count' => $lists['count']);
    }
    /**
     * [count_spu_info 统计商品信息]
     * @param  [type] $status [商品状态]
     * @return [type]         [description]
     */
    public function count_spu_info($status){
        $spu = config("DB_PREFIX").'goods_spu';
        if($status != 2){
            $result = $this->sku_db->where(array('status' => $status))->count();
        }else{
            $sqlmap[config("DB_PREFIX").'goods_sku.status'] = 1;
            $sqlmap['number'] = array('EXP','<='.$spu.'.warn_number');
            $result =  $this->sku_db->join($spu.' on id = spu_id')->where($sqlmap)->count();
        }
        return $result;
    }
    /**
     * [goods_add 添加商品]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function goods_add($goods){
        //spu参数
        $spu = $goods['spu'];
        //规格参数
        $specs = $goods['specs'];
        //参数验证
        if(empty($specs)){
            $this->error = "商品规格至少选择一种!";
            return false;
        }
        if(empty($goods['rule'])){
            $this->error = '请选择支付方式';
            return false;
        }
        $spu['contract_id'] = $goods['contract_id'];
        $spu['spec_id'] = $spu['spec_id'] ? $spu['spec_id'] : 0;
        //图册列表
        $spu_imgs = $goods['album'][0];
        if($spu_imgs){
            $spu['imgs'] = json_encode($spu_imgs);
            $spu['thumb'] = $spu_imgs[0];
        }

        $add_album = $spu_imgs ? $spu_imgs : [];
        unset($goods['album'][0]);
        $spu['sku_total'] = 0;
        //品牌id为机型关联的品牌
        $machine_info = $this->load->service('goods/goods_machine_model')->get_info($spu['machine_id']);
        $spu['brand_id'] = !empty($machine_info) ? $machine_info['brand_id'] : 0;

        //处理规格数据
        $params = array();
        foreach ($specs as $key => $spec) {
            $_value = array();
            if(!strpos($spec,'___hd___')){
                parse_str($spec,$myAarray);
                $_value = current($myAarray);
            }else{
                $arr = explode("___hd___",$spec);
                foreach ($arr as $k => $v) {
                    preg_match('/.*\[([a-zA-Z0-9]{32}+)\].*\=(.*)/',$v,$res);
                    if($key == 'spec'){
                        $_value[$res[1]][] = $res[2];
                    }else{
                        $_value[$res[1]] = $res[2];
                    }
                }
            }
            $params[$key] = $_value;
        }

        //获取原有图片数据
        $info = array();
        $info['skus'] = $this->sku_service->get_sku($spu['id']);
        $del_spec_img = array();
        $del_album = $this->spu_db->where(array('id'=>$spu['id']))->getfield('imgs');
        $del_album = json_decode($del_album,TRUE);
        foreach ($info['skus'] as $key => $_sku) {
            foreach ($_sku['spec'] AS $spec) {
                $del_spec_img[] = $spec['img'];
            }
            foreach ($_sku['imgs'] AS $sku_imgs) {
                $del_album[] = $sku_imgs;
            }
        }

        //获取原有的内容信息
        $del_content = $this->spu_db->where(array('id'=>$spu['id']))->getfield('content');

        if($params['spec']){
            foreach ($params['spec'] as $key => $specs) {
                foreach ($specs as $k => $spec) {
                    $params['spec'][$key][$k] = json_decode($spec,TRUE);
                    $params['add_spec_img'][] = $params['spec'][$key][$k]['img'];
                }
            }
        }

        $add_spec_img = mult_unique($params['add_spec_img']);
		
		// 最小租期类型
		$min_zuqi_type = 1000000;
		$min_zuqi = 1000000;
		$max_zuqi_type = 0;
		$max_zuqi = 0;
		
		// 最小单位租金
		$min_price = 1000000;
		// 最大单位租金
		$max_price = 0;
		
        //组装sku数据
        $skus = array();
        foreach ($params['sn'] as $key => $sn) {
            $skus[$key]['sn'] = $sn;
            $skus[$key]['status_ext'] = $params['status_ext'][$key];
            $skus[$key]['barcode'] = $params['barcode'][$key];
            $skus[$key]['spec'] = $params['spec'][$key] ? $params['spec'][$key] : '';
            $skus[$key]['shop_price'] = $params['shop_price'][$key];
            $skus[$key]['market_price'] = $params['market_price'][$key];
            $skus[$key]['number'] = $params['number'][$key];
            $skus[$key]['pre_number'] = $params['pre_number'][$key];
            $skus[$key]['sku_id'] = $params['sku_id'][$key];
            $spu['sku_total'] += $params['number'][$key];
            $skus[$key]['yajin'] = $params['yajin'][$key];//押金
            $skus[$key]['buyout_price'] = $params['buyout_price'][$key];//押金
            foreach ($params['spec'][$key] as $k=>$v){
                if($v['id']=='1'){
                    $skus[$key]['chengse'] = $this->spec->spec_transformation_chengse($v['value']);//成色
                }elseif ($v['id']=='4'){
                    $skus[$key]['zuqi'] = $this->spec->spec_transformation_zuqi($v['value']);//租期
                    $skus[$key]['zuqi_type'] = $this->spec->spec_transformation_zuqi_type($v['value']);//租期类型
                }
            }
			// 查找最小租期类型
			if( $skus[$key]['zuqi_type']<$min_zuqi_type ){
				$min_zuqi_type = $skus[$key]['zuqi_type'];
			}
			// 查找最大租期类型
			if( $skus[$key]['zuqi_type']>$max_zuqi_type ){
				$max_zuqi_type = $skus[$key]['zuqi_type'];
			}
			// 查找最小单位租金
			if( $skus[$key]['shop_price']<$min_price ){
				$min_price = $skus[$key]['shop_price'];
			}
			// 查找最大单位租金
			if( $skus[$key]['shop_price']>$max_price ){
				$max_price = $skus[$key]['shop_price'];
			}
            if( $skus[$key]['zuqi']<$min_zuqi ){
                $min_zuqi = $skus[$key]['zuqi'];
            }
            if( $skus[$key]['zuqi']>$max_price ){
                $max_zuqi = $skus[$key]['zuqi'];
            }
        }

        $spu['specs'] = unit::json_encode($this->create_goods_spec_array($skus));
        runhook('goods_add_spu',$spu);

        //开启事务
        if(!$this->spu_db->startTrans()){
            $this->error = '服务器繁忙，请稍后再试！';
            return false;
        }
		
		// sku 统计数据（最大最小的：单位租金，租期和租期类型）
		$spu = array_merge($spu,[
			'min_price' => $min_price,
			'max_price' => $max_price,
			'min_month' => $min_zuqi,
			'max_month' => $max_zuqi,
			'min_zuqi_type' => $min_zuqi_type,
			'max_zuqi_type' => $max_zuqi_type,
		]);
		
        //编辑goods_spu表数据
        $result = $this->spu_db->update($spu);
        if($result === false){
            $this->spu_db->rollback();
            $this->error = $this->spu_db->getError();
            return false;
        }
        $spu['id'] = isset($spu['id']) && $spu['id'] > 0 ? $spu['id'] : $result;
        $local_adv_ids = $local_sku_ids = [];
        if((int)$spu['id'] > 0){
            $local_sku_ids = $this->sku_db->where(array('spu_id' => $spu['id'], 'status' => ['IN', [0, 1]]))->getField('sku_id',TRUE);
            $local_adv_ids = $this->adv_db->where(['content_id' => $spu['id']])->getField('id', TRUE);
        }

        //添加商品，支付规则关联数据
        $relation_result = $this->load->service('payment/goods_spu_rule')->edit_relation($spu['id'], $goods['rule']['payment_rule_id']);
        if($relation_result === false){
            $this->spu_db->rollback();
            $this->error = '关联支付方式失败';
            return false;
        }

        if($local_adv_ids){
            //更新内容推荐里的渠道
            $adv_info = $this->adv_db->where(['id' => ['IN', $local_adv_ids]])->save(['channel_id' => $spu['channel_id']]);
            if($adv_info === false){
                $this->spu_db->rollback();
                $this->error = $this->adv_db->getError();
                return FALSE;
            }
        }

        $albums = array();
        foreach ($goods['album'] as $k => $album) {
            $albums['goodsphoto'][$k] = $album;
            $albums['show_in_lists'][$k] = 1;
            $add_album = array_merge($add_album,$album);
        }

        $this->load->service('attachment/attachment')->attachment($add_album,$del_album,false);
        $this->load->service('attachment/attachment')->attachment($add_spec_img,$del_spec_img,false);
        $this->load->service('attachment/attachment')->attachment($goods['spu']['content'],$del_content);

        $sku_key = key($skus);
        $data = $sku_ids = [];
        foreach ($skus as $key => $sku) {
            if(is_null($sku['spec'])){
                $sku['imgs'] = $spu_imgs;
                $sku['show_in_lists'] = 1;
            }else{
                if(empty($goods['album'])){
                    if($key == $sku_key){
                        $sku['show_in_lists'] = 1;
                    }
                }else{
                    foreach ($sku['spec'] AS $spec) {
                        $spec_md5_imgs = $goods['album'][md5($spec['name'].':'.$spec['value'])];
                        if(!empty($spec_md5_imgs)){
                            $sku['imgs'] = $spec_md5_imgs;
                            $sku['show_in_lists'] = $albums['show_in_lists'][md5($spec['name'].':'.$spec['value'])] ? $albums['show_in_lists'][md5($spec['name'].':'.$spec['value'])] : 0;
                            unset($albums['show_in_lists'][md5($spec['name'].':'.$spec['value'])]);
                        }
                    }
                }
            }
            $sku['spu_id'] = $spu['id'];
            $sku['status'] = $spu['status'];
            $sku['sort'] = $spu['sort'];
            $sku['sku_name'] = $spu['name'].' '.$this->sku_service->create_sku_name($sku['spec']);
            //sku编辑版本
            $edition = $this->sku_db->where(array('sku_id'=>$sku['sku_id']))->getfield('edition');
            $sku['edition'] = $edition + 1;
            $sku_ids[] = $sku['sku_id'];
            //组装sku编辑、新增、删除数组
            if(!$sku['sku_id']){
                $data['new'][] = $sku;
            }else{
                if(in_array($sku['sku_id'],$local_sku_ids)){
                    $data['edit'][] = $sku;
                }
            }
        }
        $data['del'] = array_diff($local_sku_ids,$sku_ids);
        //sku数据入库操作
        $skuinfo = $this->sku_service->create_sku($data);
        if($skuinfo === false){
            $this->spu_db->rollback();
            $this->error = $this->sku_service->error;
            return FALSE;
        }

//		
//        /* 组织spu最大最小价格 */
//        $_price = $this->sku_db->field("min(shop_price) AS min_price, max(shop_price) AS max_price")->where(array("spu_id" => $spu['id'],'status' => array('NEQ', -1)))->find();
//        $this->spu_db->save(array('id' => $spu['id'], 'min_price' => $_price['min_price'], 'max_price' => $_price['max_price']));
//        /* 组装spu最小起租月 */
//        $_month = $this->sku_db->field("min(zuqi) AS min_month, max(zuqi) AS max_month")->where(array("spu_id" => $spu['id'],'status' => array('NEQ', -1)))->find();
//        $this->spu_db->save(array('id' => $spu['id'], 'min_month' => $_month['min_month'], 'max_month' => $_month['max_month']));
//		
        /* 属性数据组织 */
        if(isset($goods['attr'])){
            $this->create_goods_attr_spec($goods['attr'],$skuinfo);
        }
        $rs = $this->create_goods_index($spu,$skuinfo);
        //提交事务
        $this->spu_db->commit();
        runhook('goods_publish', $result);
        $skuinfo['spu_id'] = $spu['id'];
        return $skuinfo;
    }
    /**
     * 获取SPU信息
     * @param  numeric 	$id    SPU_ID
     * @param  string 	$extra 输出拓展信息
     * @author xuewl <master@xuewl.com>
     * @return array
     */
    public function get_by_id($id, $extra='brand,category,sku,type,channel,machine'){
        $id = (int) $id;
        $result = array();
        if($id < 1) {
            $this->error = '参数错误';
            return false;
        }
        $spu = $this->spu_db->find($id);
        if(empty($spu)) {
            $this->error = '商品SPU不存在';
            return false;
        }
        $result['spu'] = $spu;

        /* 返回值 */
        if($extra) {
            $extra = explode(",", $extra);
            foreach ($extra AS $val) {
                $method = "get_extra_".$val;
                if(method_exists($this->spu_db,$method)) {
                    $result['_'.$val] = $this->spu_db->$method($spu);
                }
            }
        }
        return $result;
    }
    /**
     * [create_goods_spec_array 生成goods中的spec_array]
     * @param  [type] $data [规格json数组]
     * @return [array]       [商品的规格数组]
     */
    private function create_goods_spec_array($skus){
        if(isset($skus)){
            $goods_spec_array = array();
            foreach($skus as $key => $sku) {
                foreach ($sku['spec'] as $key => $spec) {
                    if(!isset($goods_spec_array[$spec['id']])) {
                        $goods_spec_array[$spec['id']] = array('id' => $spec['id'],'name' => $spec['name'],'value' => array(),'style' => array(),'img' => array(),'color' => array());
                    }
                    $goods_spec_array[$spec['id']]['value'][] = $spec['value'];
                    $goods_spec_array[$spec['id']]['style'][] = $spec['style'];
                    $goods_spec_array[$spec['id']]['img'][] = $spec['img'];
                    $goods_spec_array[$spec['id']]['color'][] = $spec['color'];
                }
            }
            foreach($goods_spec_array as $key => $val) {
                $val['value'] = array_unique($val['value']);
                $val['img'] = array_unique($val['img']);
                $val['color'] = array_unique($val['color']);
                $val['style'] = array_unique($val['style']);
                $goods_spec_array[$key]['value'] = join(',',$val['value']);
                $goods_spec_array[$key]['img'] = join(',',$val['img']);
                $goods_spec_array[$key]['color'] = join(',',$val['color']);
                $goods_spec_array[$key]['style'] = join(',',$val['style']);
            }
        }
        return $goods_spec_array;
    }
    /**
     * [ get_goods_spec_cache 获取商品规格处理页的规格数据]
     * @return [type] [description]
     */
    public function get_goods_specs($skus){
        $selectedItem = array();
        foreach ($skus as $key => $specs) {
            foreach ($specs['spec'] AS $spec) {
                $item = array();
                $item['id'] = $spec['id'];
                $item['name'] = $spec['name'];
                $item['value'] = $spec['value'];
                $item['style'] =  $spec['style'];
                $item['color'] = $spec['color'];
                $item['img'] = $spec['img'];
                $item['spec_md5'] = md5($spec['id'].$spec['value']);
                $selectedItem[] = $item;
            }
        }
        $selectedItem = more_array_unique($selectedItem);
        return $selectedItem;
    }
    /**
     * [keyword_search 商品关键字查询]
     * @param  [str] $keyword [关键字]
     * @return [type]          [description]
     */
    public function keyword_search($keyword){
        $_nameids = (array)$this->sku_db->where(array('sku_name'=>array('LIKE','%'.$keyword.'%')))->getField('spu_id',TRUE);
        $where['sn|barcode'] = array("LIKE", '%'.$keyword.'%');
        $_goodsids = (array)$this->sku_db->where($where)->distinct(TRUE)->getField('spu_id',TRUE);
        $result_ids = array_unique(array_merge($_nameids,$_goodsids));
        $sqlmap = array();
        $sqlmap['id'] = array('IN',$result_ids);
        return $sqlmap;
    }
    /**
     * 确认是否查询sku商品
     * @param $keyword
     * @return int
     */
    public function sure_sku($keyword){
        $map['sn'] = $keyword;
        $map['sku_name'] = $keyword;
        $map['_logic'] = 'OR';
        $result = $this->sku_db->where($map)->find();
        if ($result) return $result['sn'];
        return 0;
    }
    /**
     * [detail 获取主商品信息]
     * @param  [type]  $id    [主商品id]
     * @return [array]         [商品数据]
     */
    public function detail($id){
        $id = (int) $id;
        if ($id < 1) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $goods_info = array();
        $goods_info = $this->spu_db->detail($id)->sku_id()->brandname()->catname()->cat_format()->output();
        if (!$goods_info) {
            $this->error = lang('goods_goods_not_exist','goods/language');
            return FALSE;
        }
        if(empty($goods_info['thumb'])){
            $goods_info['thumb'] = $this->sku_db->where(array('sku_id'=>$goods_info['sku_id']))->getField('thumb');
        }
        $goods_info['price'] = $goods_info['min_price'].'-'.$goods_info['max_price'];
        return $goods_info;
    }
    /**
     * [create_goods_attr_spec 生成商品属性]
     * @return [type] [description]
     */
    private function create_goods_attr_spec($types,$data){
        //对删除数据做处理
        if(!empty($data['del'])){
            $this->goodsattr_db->where(array('sku_id'=>array('IN',$data['del'])))->delete();
        }
        //对编辑数据进行处理
        foreach ($data['edit'] AS $sku) {
            if($sku['sku_id']){
                $this->goodsattr_db->where(array('sku_id'=>$sku['sku_id']))->delete();
            }
        }
        $skus = array_merge($data['edit'] ? $data['edit'] : array(),$data['new'] ? $data['new'] : array());
        if(!empty($skus)){
            foreach ($skus AS $sku) {
                foreach ($sku['spec'] AS $spec) {
                    $item = array();
                    $item['sku_id'] = $sku['sku_id'];
                    $item['attribute_id'] = $spec['id'];
                    $item['attribute_value'] = $spec['value'];
                    $item['type'] = 2;
                    $this->goodsattr_db->update($item);
                }
            }
            //属性处理
            foreach ($types AS $type_id => $type) {
                foreach ($type AS $attr) {
                    foreach ($skus AS $sku) {
                        if(!empty($attr)){
                            $item = array();
                            $item['sku_id'] = $sku['sku_id'];
                            $item['attribute_id'] = $type_id;
                            $item['attribute_value'] = $attr;
                            $item['type'] = 1;
                            $this->goodsattr_db->update($item);
                        }
                    }
                }
            }
        }
        return TRUE;
    }
    /**
     * [create_goods_index 生成商品索引表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function create_goods_index($spu,$skus){
        if(!empty($skus['del'])){
            $this->goods_index_db->where(array('sku_id'=>array('IN',$skus['del'])))->delete();
        }
        $skuinfo =  array_merge($skus['edit'] ? $skus['edit'] : array(),$skus['new'] ? $skus['new'] : array());
        if(!empty($skuinfo)){
            foreach ($skuinfo AS $sku) {
                $item = array();
                $item['sku_id'] = $sku['sku_id'];
                $item['spu_id'] = $spu['id'];
                $item['catid'] = $spu['catid'];
                $item['brand_id'] = $spu['brand_id'];
                $item['shop_price'] = $sku['shop_price'];
                $item['show_in_lists'] = $sku['show_in_lists'] ? $sku['show_in_lists'] : 0;
                $item['status'] = $spu['status'];
                $item['status_ext'] = $sku['status_ext'] ? $sku['status_ext'] : 0;
                $item['sort'] = $spu['sort'];
                $skuindex = $this->goods_index_db->find($item['sku_id']);
                if(empty($skuindex)){
                    $this->goods_index_db->add($item);
                }else{
                    $this->goods_index_db->save($item);
                }
            }
        }
        return TRUE;
    }
    /**
     * [delete 删除商品，在商品列表里删除只改变状态，在回收站里删除直接删除]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function delete($params){
        $id = $params['id'];
        $label = $params['label'];
        $data = $sqlmap = $map = array();
        if($id){
            if($label == 4){
                $result =$this->delete_goods($id);
                return $result;
            }else{
                $sqlmap['id'] = $map['spu_id'] = array('IN',$id);
                $data['status'] = -1;
                $result = $this->spu_db->where($sqlmap)->save($data);
                $this->sku_db->where($map)->save($data);
                $this->goods_index_db->where($map)->save($data);
                //逻辑删除商品轮播数据
                $adv_info = $this->adv_db->where(array('content_id' => $map['spu_id']))->field('id,position_id,title,content,content_id,filag as flag,link')->select();
                if($adv_info){
                    $status['status'] = 0;
                    foreach ($adv_info as $key => $val) {
                        if ($val['flag'] == 'spu') {
                            $this->adv_db->where(array('id' => $val['id']))->save($status);
                        } else {
                            continue;
                        }
                    }
                }
                if(!$result){
                    $this->error = lang('_operation_fail_');
                    return FALSE;
                }
                return TRUE;
            }
        }else{
            $this->error = lang('_param_error_');
            return FALSE;
        }
    }
    /**
     * [delete_goods 删除商品,只有在回收站里进行此操作]
     * @param  [array] $id [商品id]
     * @return [type]     [description]
     */
    private function delete_goods($id){
        $id = (array)$id;
        if(empty($id)) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $_goods_del_result = $this->spu_db->where(array('id' => array("IN", $id)))->delete();
        $_pro_del_result = $this->sku_db->where(array('spu_id' => array('IN', $id)))->delete();
        $this->goods_index_db->where(array('spu_id' => array('IN', $id)))->delete();
        return true;
    }
    /**
     * [recover 批量恢复商品]
     * @param  [array] $id [要恢复的商品id]
     * @return [type]     [description]
     */
    public function recover($id){
        $id = (array)$id;
        if(empty($id)) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $data = array();
        $data['status'] = 1;
        $result = $this->sku_db->where(array('sku_id' => array('IN',$id)))->save($data);
        $spu_ids = $this->sku_db->where(array('sku_id' => array('IN',$id)))->getField('spu_id',TRUE);
        foreach ($spu_ids AS $spu_id) {
            $this->spu_db->where(array('id'=>$spu_id))->save($data);
        }
        $this->goods_index_db->where(array('sku_id' => array('IN',$id)))->save($data);
        if(!$result){
            $this->error = lang('goods_recover_fail','goods/language');
        }
        return $result;
    }
    /**
     * [change_spu_info 改变商品货号]
     * @param  [array] $params []
     * @return [boolean]     [返回更改结果]
     */
    public function change_spu_info($params){
        if((int)$params['id'] < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $result = $this->spu_db->where(array('id' => $params['id']))->save($params);
        if(isset($params['sort'])) $this->goods_index_db->where(array('spu_id' => $params['id']))->save($params);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }
    /**
     * [change_status 改变商品状态]
     * @param  [array] $params []
     * @return [boolean]     [返回更改结果]
     */
    public function change_status($id,$type = 'spu'){
        if((int)$id < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        if($type == 'sku'){
            //SKU表
            $data = array();
            $data['status']=array('exp',' 1-status ');
            $result = $this->sku_db->where(array('sku_id'=>$id))->save($data);
            $this->goods_index_db->where(array('sku_id'=>$id))->save($data);
            $spu_id = $this->sku_db->where(array('sku_id'=>$id))->getField('spu_id');
            $sku_status = $this->sku_db->where(array('spu_id'=>$spu_id))->getField('sku_id,status',TRUE);
            $sku_status_num = 0;
            foreach ($sku_status AS $status) {
                if($status != 1){
                    $sku_status_num++;
                }
            }
            if($sku_status_num == count($sku_status)){
                $this->spu_db->where(array('id'=>$spu_id))->save(array('status' => 0));
            }else{
                $this->spu_db->where(array('id'=>$spu_id))->save(array('status' => 1));
            }
        }else{
            //SPU表
            $data = array();
            $data['status']=array('exp',' 1-status ');
            $result = $this->spu_db->where(array('id'=>$id))->save($data);
            $this->goods_index_db->where(array('spu_id'=>$id))->save($data);
            $spu_status = $this->spu_db->where(array('id'=>$id))->getfield('status');
            if($spu_status == 1) {
                $this->sku_db->where(array('spu_id' => $id, 'status' => 0))->save(array('status' => 1));
                $this->goods_index_db->where(array('spu_id' => $id))->save(array('status' => 1));
                $adv_info = $this->adv_db->where(array('content_id' => $id))->field('id,position_id,title,content,content_id,filag as flag,link')->select();
                foreach ($adv_info as $key => $val) {
                    if ($val['flag'] == 'spu') {
                        $this->adv_db->where(array('id' => $val['id'], 'status' => 0))->save(array('status' => 1));
                    } else {
                        continue;
                    }
                }
            }else{
                $this->sku_db->where(array('spu_id'=>$id,'status' => 1))->save(array('status' => 0));
                $this->goods_index_db->where(array('spu_id'=>$id))->save(array('status' => 0));
                $adv_info = $this->adv_db->where(array('content_id'=>$id))->field('id,position_id,title,content,content_id,filag as flag,link')->select();
                $s = 'spu';
                foreach ($adv_info as $val){
                    if($val['flag'] == $s) {
                        $this->adv_db->where(array('id'=>$val['id'], 'status' => 1))->save(array('status'=> 0));
                    } else {
                        continue;
                    }
                }
            }

        }

        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }
    /**
     * [get_goods_album 获取图册]
     * @param  [array] $params []
     * @return [boolean]     [返回更改结果]
     */
    public function get_goods_album($goods){
        $skus = $goods['_sku'];
        foreach ($skus as $sku_id => $sku) {
            foreach ($sku['spec'] as $key => $spec) {
                if($spec['id'] == $goods['spu']['spec_id']){
                    $item = array();
                    $item['spec_md5'] = md5($spec['name'].':'.$spec['value']);
                    $item['imgs'] = $sku['imgs'];
                    $select[] = $item;
                }
            }
            $select = mult_unique($select);
        }
        $album = array();
        foreach($select as $item) {
            list($t,$n) = array_values($item);
            $album[$t] = $n;
        }
        return $album;
    }

    /**
     * 查找机型最多的
     * @param int $num 多少条
     * @param array $where 查询条件
     */
    public function count_machine_top($where = [],$num){
        $machine = config("DB_PREFIX").'goods_machine_model';
        $spu = config("DB_PREFIX").'goods_spu';
        return $this->spu_db->field("count(t2.id) as machine_num, t2.`name`")->join($machine.' as t2 on t2.id=machine_id')->where($where)->group($spu.'.id')->order('machine_num desc')->limit($num)->select();
    }
}