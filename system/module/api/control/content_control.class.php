<?php

/**
 *	  内容控制器
 */
hd_core::load_class('api', 'api');
class content_control extends api_control {

    public $position = array('index-lunbo'=>4,'index-hot'=>7,'index-brand'=>6,'index-nubia'=>8,'index-uav'=>9);
    public $limit = 20;
	/**
	 *租机根据位置id和渠道id缓存key前缀
	 * @var string
	 */
	protected $redis_key = 'zuji_pos_appid_';

	public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('ads/adv');
        $this->channel_appid = $this->load->service('channel/channel_appid');
    }

    /**
     * 根据位置ID，获取内容方式列表接口
     */
    public function query() {

        // 长度最大值
        $max_len = 20;
        //-+--------------------------------------------------------------------
        // | 接收请求
        //-+--------------------------------------------------------------------
        $params = $this->params;

        // 判断 position 必须
        if(empty($params['position'])){
            api_resopnse( [], ApiStatus::CODE_20000,'1',  ApiSubCode::Content_Position_id_Error, '' );
            return ;
        }

        $position = trim($params['position']);
        $position_id = $this->position[$position];
        // 判断 position 值域
        if( empty($position_id) ){
            api_resopnse( [], ApiStatus::CODE_20000,'2',  ApiSubCode::Content_Position_id_Error, '' );
            return;
        }
        // 默认读取长度
        $length = 10;

        if( $params['length'] ){
            $length = intval($params['length']);
            if( $length < 1 ){
                api_resopnse( [], ApiStatus::CODE_20000,'',  ApiSubCode::Content_Length_Error, '' );
                return;
            }
            // 读取长度不可以超过最大长度
            $length = min($length,$max_len);
        }

        $additional = [
            'page' => 1,
            'size' => $length,
        ];

        //条件
        $where = ['position_id'=>$position_id,'status'=>1];
        $appid = api_request()->getAppid();
		
		
        $appid_info = $this->channel_appid->get_info($appid);
		
        if($appid_info){
            //有独立商品，获取对应渠道的内容，没有：获取官方渠道的内容
            $where['channel_id'] = $appid_info['_channel']['alone_goods'] ? $appid_info['appid']['channel_id'] : 1;
        }
		
		//-+--------------------------------------------------------------------
		//-| 获取数据，有限获取缓存
		//-+--------------------------------------------------------------------
		$where['channel_id'] = $where['channel_id'] ? $where['channel_id']:1;
		//拼接当前位置当前渠道的缓存key
		$redis_key = $this->redis_key . $position_id . '_' . $where['channel_id'];
		//获取redis实例
        $redis = \zuji\cache\Redis::getInstans();
        $result = $redis->get($redis_key);
		//判断缓存获取结果并判断
        if( $result ){
			$data = json_decode($result,true);
			//缓存存在并且不为空，返回数据
			if( $data ) {
				api_resopnse( $data );
				return ;
			}
        }
		
		//-+--------------------------------------------------------------------
		//-| 缓存不存在，读取数据返回并记录到redis
		//-+--------------------------------------------------------------------
        $ads = $this->service->get_lists($where,$additional);
        $data = [];
        foreach ($ads as $key=>$item){
            if($item['status']==1){
                if($item['flag'] != 'link'){
                    $item['id'] = $item['content_id'];
                    unset($item['content_id']);
                }
                $data[] = $item;
            }
        }
		//记录到缓存
		if( $data ){
			$redis->set($redis_key, json_encode($data),12*3600);
		}
        //$data['appid'] = $this->appid;
        api_resopnse( $data );
        return ;
    }

}
