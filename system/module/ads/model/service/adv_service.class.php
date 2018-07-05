<?php

/**
 *      广告设置服务层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class adv_service extends service {

    protected $count;
    protected $pages;
    protected $type_arr = array('spu' => '商品', 'article' => '文章', 'link' => '链接');

    public function _initialize() {
        $this->model = $this->load->table('ads/adv');
        $this->spu_service = $this->load->service('goods2/goods_spu');
        $this->article_service = $this->load->service('misc/article');
    }

    /**
     * 查询单个信息
     * @param int $id 主键ID
     * @param string $field 被查询字段
     * @return mixed
     */
    public function fetch_by_id($id, $field = NULL) {
	$r = $this->model->find($id);
	if (!$r)
	    return FALSE;
	return ($field !== NULL) ? $r[$field] : $r;
    }

    /**
     * 
     * 查询推荐内容列表
     * @param array $where	【可选】查询条件
     * [
     *	    'position_id' => '',    //【可选】int；推荐位ID
     *	    'flag' => '',	    //【可选】string；内容标识；spu：商品；article：文章；link：链接；
     * ]
     * @param array $additional
     * [
     *	    'page'	=> '',	    【可选】int；分页码，默认为1
     *	    'size'	=> '',	    【可选】int；每页大小，默认20
     *	    'orderby'	=> '',	    【可选】string；排序；check_time_DESC：时间倒序；check_time_ASC：时间顺序；默认 id_DESC
     * ]
     * @return array
     * [
     *	    [
     *		'flag' => '',	    //【必须】string；内容标识；spu：商品；article：文章；link：链接；
     *		'id' => '',	    //【必须】string；内容ID；flag=spu时为SPU ID；flag=article时为文章ID；flag=link时为链接ID
     *		'title' => '',	    //【必须】string；标题
     *		'thumb' => '',	    //【必须】string；缩略图url地址
     *		'url' => '',	    //【必须】string；链接url，flag=link时必须
     *	    ]
     * ]
     */
    public function get_lists($where=[], $additional=[]) {

        $ads_list = $this->model->get_list($where,$additional);
        $lists = [];
        $spu_ids = [];
        $article_ids = [];
        //取出商品id和文章id
        foreach($ads_list as $key=>$val){
            if ($val['flag'] == 'spu') {
                array_push($spu_ids,$val['content_id']);
            }elseif ($val['flag'] == 'article') {
                array_push($article_ids,$val['content_id']);
            }
        }

        //获取spu商品列表信息
        $spu_list = [];
        if($spu_ids){
            $spu_where = [
              'id' => ['in',implode(",",$spu_ids)]
            ];
            $spu_list = $this->spu_service->api_get_list($spu_where);
            $spu_list = $this->arrayKey($spu_list,"id");
        }
        //获取文章列表信息
        $article_list = [];
        if($article_ids){
            $article_where = [
                'id' => ['in',implode(",",$article_ids)]
            ];
            $article_list = $this->article_service->get_lists($article_where);
            $article_list = $this->arrayKey($article_list,"id");
        }

        foreach ($ads_list as $k => $v) {
	    
            $thumb = $v['images'];
            $sub_title = "";
            $min_price = "";
            $catid = "";
            $unit = "";
            $init = 1;
            if(empty($thumb)){
                if ($v['flag'] == 'spu') {
                    if($spu_list[$v['content_id']]){
                        $v['title'] = $spu_list[$v['content_id']]['name'];
                        $thumb = $spu_list[$v['content_id']]['thumb'];
                        $sub_title = $spu_list[$v['content_id']]['subtitle'];
                        $catid = $spu_list[$v['content_id']]['catid'];
                        $min_price = zuji\order\Order::priceFormat($spu_list[$v['content_id']]['min_price']);
                        if($spu_list[$v['content_id']]['min_zuqi_type']==1){
                            $unit = "day";
                        }
                        elseif($spu_list[$v['content_id']]['min_zuqi_type']==2){
                            $unit = "month";
                        }

                    }else
                    {
                        $init = 0;
                    }

                } elseif ($v['flag'] == 'article') {
                    if($article_list[$v['content_id']]){
                        $v['title'] = $article_list[$v['content_id']]['title'];
                        $thumb = $article_list[$v['content_id']]['thumb'];
                    }
                    else
                    {
                        $init = 0;
                    }

                }
                else
                {
                    $thumb = "";
                }
            }
            if($init==1){
                $lists[] = array(
                    'id'           => $v['id'],
                    'content_id'   => $v['content_id'],
                    'position_id'  => $v['position_id'],
                    'title'         => $v['title'],
                    'sub_title'   =>   $sub_title,
                    'min_price'  => $min_price,
                    'unit'        =>$unit,
                    'catid'       => $catid,
                    'flag'        => $v['flag'],
                    'thumb'      => $thumb,
                    'url'         => $v['link'],
                    'status'      => $v['status'],
                    'channel_id'      => $v['channel_id'],
                );
            }

        }

        return $lists;
    }

    /**
     * [change_adv_info 把商品/文章推荐到广告位置中]
     * @param  [array] $info
     * array(
     *      'id'=>'商品/文章ID 字符串，多个逗号分隔',//必选
     *      'position'=>'存放位置ID',           // 必须
     * )
     * @param  [string] $type 内容类型，spu:商品；article：文章；
     * @return [boolean]
     */
    public function change_adv_info($info=[]) {
        $ids = explode(',', $info['id']);
        $where = ['id' => ['in', $ids]];
        $list = $channel_ids = [];

        if($info['type'] == 'article'){
            $list = $this->article_service->get_list($where);
        }elseif($info['type'] == 'spu'){
            $where['status'] = 1;
            $list = $this->spu_service->api_get_list($where);
        }
        if($list){
            $channel_ids = array_column($list, 'channel_id');
            $ids = array_column($list, 'id');
        }
        $info['id'] = $ids;
        $info['channel_id'] = $channel_ids;
	    return $this->model->change_adv($info);
    }

    /**
     * [更新广告]
     * @param array $data 数据
     * @param bool $valid 是否M验证
     * @return bool
     */
    public function save($data=[], $valid = FALSE) {
	if ($valid == TRUE) {
	    $data = $this->model->create($data);
	    $result = $this->model->add($data);
	} else {
	    $result = $this->model->save($data);
	}
	return $result;
    }

    /**
     * 编辑广告title
     */
    public function save_title($data=[]) {
	$result = $this->model->save($data);
	if (!$result) {
	    return false;
	}
	return $result;
    }

    /**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap = array()) {
	$result = $this->model->where($sqlmap)->count();
	if ($result === false) {
	    $this->error = $this->model->getError();
	    return false;
	}
	return $result;
    }
    /**
     * [删除]
     * @param array $ids 主键id
     */
    public function set_status($id,$status) {
        if (empty($id)) {
            $this->error = lang('_param_error_');
            return false;
        }
        if(!isset($status)){
            $this->error = "状态错误";
            return false;
        }
        $result = $this->model->set_status($id,$status);
        if ($result === false) {
            $this->error = $this->model->getError();
            return false;
        }
        return true;
    }
    /**
     * [删除]
     * @param array $ids 主键id
     */
    public function delete($ids) {
	if (empty($ids)) {
	    $this->error = lang('_param_error_');
	    return false;
	}
	$_map = array();
	if (is_array($ids)) {
	    $_map['id'] = array("IN", $ids);
	} else {
	    $_map['id'] = $ids;
	}
	$result = $this->model->where($_map)->delete();
	if ($result === false) {
	    $this->error = $this->model->getError();
	    return false;
	}
	return true;
    }

    /**
     * @param [值]
     * @param [数]
     * @param [sql条件]
     */
    public function setInc($val, $num, $sqlmap) {
	return $this->model->where($sqlmap)->setInc($val, $num);
    }
    //重构数组键名
    function arrayKey($infos,$key){
        $retArr = array();
        if( $infos && count($infos) > 0 )
        {
            foreach( $infos as $info )
            {
                $retArr[ $info[ $key ] ] = $info;
            }
        }
        return $retArr;
    }
}
