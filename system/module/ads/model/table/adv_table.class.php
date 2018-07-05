<?php

use zuji\debug\Debug;
use zuji\debug\Location;
/**
 * 		广告列表数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class adv_table extends table {

    protected $fields = [
        'id',
        'position_id',
        'title',
        'link',
        'description',
        'starttime',
        'endtime',
        'loading',
        'hist',
        'images',
        'content',
        'status',
        'sort',
        'tag',
        'content_id',
        'filag',
        'channel_id'
    ];
    protected $type_arr = array('图片', '文字');
    protected $_validate = array(
//	array('title', 'require', '{adv/adv_name_require}', table::MUST_VALIDATE),
	array('position_id', 'require', '{adv/adv_position_require}', table::MUST_VALIDATE),
	array('starttime,endtime', 'checkDay', '{adv/endtime_not_gt_atarttime}', 1, 'callback', 3),
    );
    protected $_auto = array(
	array('starttime', 'strtotime', 3, 'function'),
	array('endtime', 'strtotime', 3, 'function'),
    );
    
    
    
    /**
     * 把商品 或文章 推送到 广告列表中
     */
    public function change_adv($info){
		//实例化redis清除缓存
//        $redis = \zuji\cache\Redis::getInstans();
		
        $ids = $info['id'];
        $position_id = intval($info['position']);
        for ($i=0;$i<count($ids);$i++) {
            $count = $this->where(array('position_id' => $position_id, 'content_id' => $ids[$i], 'filag' => $info['type']))->count();
               if ($count==0) {
                $result = $this->add(array('position_id' => $position_id, 'content_id' => $ids[$i], 'filag' => $info['type'], 'channel_id' => $info['channel_id'][$i]));
//				if( $info['channel_id'][$i] == 0 ) {
//					$redis_result = $redis->del('zuji_pos_appid_'.$position_id.'*');
//				}else{
//					$redis_result = $redis->del('zuji_pos_appid_'.$position_id.'_'.$info['channel_id'][$i]);
//				}
                if($result===false){return false;}
            }       
        } 
        return true;
    }
    public function set_status($id,$status){
        return $this->where(array('id'=>$id))->save(array('status'=>$status));
    }
	/**
	 * 插入之后的操作
	 */
	protected function _after_insert($data,$options) {
		//获取当前更新数据的主键
		$id = $data['id'];
		if( $id ) {
			$this->__update_h5_redis($id);
		}
	}
	/**
	 * 更新之后的操作
	 */
	protected function _after_update($data,$options) {
		//获取当前更新数据的主键
		$id = $data['id'];
		if( $id ) {
			$this->__update_h5_redis($id);
		}
	}
	/**
	 * 根据当前表主键id更新对应位置和渠道的缓存
	 * @param type $id
	 */
	private function __update_h5_redis($id) {
		//实例化redis清除缓存
        $redis = \zuji\cache\Redis::getInstans();
//		//查找当前数据
//		$info = $this->find($id);
//		//主键存在，更新机市首页对应位置和渠道关联的缓存数据
//		if( $info ){
			$redis_key = $redis->keys('zuji_pos_appid_*');
			$redis_result = $redis->del($redis_key);
//		}
	}
    /**
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
     * 
     */
    public function get_list( $where=[], $additional=[] ){
	// where
//	$where = replace_field($where, array(
//	    'position_id' => 'position_id'
//	));
	if( isset($where['position_id']) && $where['position_id']<1 ){
	    return [];
	}
	if( isset($where['filag']) && !in_array($where['filag'],array('spu','article','link'))){
	    return [];
	}
	
	// page
	$min_page = 1;
	if( isset($additional['page']) ){
	    $page = intval( $additional['page'] );
	}
	$page = max($page,$min_page);
	
	// size
	$min_size = 1;
	if( isset($additional['size']) ){
	    $size = intval( $additional['size'] );
	}
	$size = max($size,$min_size);

    $order = 'sort asc';
	if( isset($additional['order']) ){
        $order = $additional['order'];
    }
	
	// 查询
	$ads_list = $this->field('id,position_id,title,images,content,content_id,filag as flag,link,status,channel_id')
		->where($where)
		->page($page)
        ->order($order)
		->limit($size)
		->select();
//	foreach ($ads_list as $k=>$value) {
//	    $ads_list[$k] = $this->_after_find($value, null);
//	}
	return $ads_list;
    }
    
    protected function _after_find(&$result, $options) {
	$position = $this->load->table('adv_position')->field('name')->where(array('id' => $result['position_id']))->find();
	//$result['position_name'] = isset($position['name']) ? $position['name'] : '--';

//	if ($position['filag'] == 'spu') {
//	    $spu = $this->load->table('goods_spu')->field('name,thumb')->where(array('id' => $result['content_id']))->find();
//	    $result['title'] = $spu['name'];
//	    $result['content'] = $spu['thumb'];
//	} elseif ($position['filag'] == 'article') {
//	    $article = $this->load->table('goods_spu')->field('title,thumb')->where(array('id' => $result['content_id']))->find();
//	    $result['title'] = $article['title'];
//	    $result['content'] = $article['thumb'];
//	}

//		$result['type_text'] = $this->type_arr[$position['type']];
//		$result['startime_text'] = date('Y-m-d H:i:s', $result['starttime']);
//		$result['endtime_text'] = date('Y-m-d H:i:s', $result['endtime']);
	return $result;
    }

//    protected function _after_select(&$result, $options) {
//	foreach ($result as &$record) {
//	    $this->_after_find($record, $options);
//	}
//	return $result;
//    }

    //开始结束时间
    protected function checkDay($data) {
	if ($data['starttime'] > $data['endtime'])
	    return false;
	else
	    return true;
    }
    
    

}
