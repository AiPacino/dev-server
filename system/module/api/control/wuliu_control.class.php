<?php


hd_core::load_class('api', 'api');
/**
 * 物流渠道列表控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class wuliu_control extends api_control {

    protected $member = array();
    private $url = null;

    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('order/delivery');
        $this->url = config("Api_Logistics_Url");
    }

    /**
    * 获取物流渠道列表
    * @return $data
    * @author limin
    */
    public function channel_query() {

        $data = $this->service->get_api_list("id as channel_code,name as channel_name",['enabled'=>0]);
        api_resopnse( array('channel_list'=>$data), ApiStatus::CODE_0 );
        return;
    }

    /**
     * 物流单号查询
     * @return $data
     * @author limin
     */
    public function get() {
        $params = $this->params;
        $params = filter_array($params,[
            'wuliu_channel_id'=>'required',
            'wuliu_no'=>'required'
        ]);
        if(!$params['wuliu_no']){
            api_resopnse( [], ApiStatus::CODE_20001,'wuliu_no必须', ApiSubCode::Retrun_Error_Wuliu_no);
            return;
        }
        if(!$params['wuliu_channel_id']){
            api_resopnse( [], ApiStatus::CODE_20001,'wuliu_channel_id必须', ApiSubCode::Retrun_Error_Wuliu_channel_id);
            return;
        }
        $code  =  $params['wuliu_no'];
        $request = json_encode(['mailno'=>$code]);

		$key = 'zuji_wuliu_'.$params['wuliu_channel_id'].'_'.$code;
		$redis = zuji\cache\Redis::getInstans();
		$data = $redis->get($key);
		if( $data ){
			$data = json_decode($data,true);
		}
		if( !$data ){
			//发起GET请求
			$result    = zuji\Curl::post($this->url,$request);
			$arrNum = json_decode($result,true);
			$data = $arrNum['data'];
			$redis->set($key, json_encode($data ),60*30);
		}
        //验证返回结果
        if(empty($data)){
            api_resopnse( [], ApiStatus::CODE_50000,'未查询到该物流单号物流信息');
            return;
        }

        $newarry    = array();

        for($i=count($data)-1;$i>0;$i--)
        {
            $array['createTime']  = date("Y-m-d H:i:s",strtotime($data[$i]['barTm']));
            $array['message']     = $data[$i]['status'];
            $array['sectionIcon'] = "";

            $newarry[]  =  $array;
        }

        $info['logisticsCode']  = $code;
        $info['companyName']    = "顺丰";
        $info['companyImg']     = "https://s1.huishoubao.com/zuji/images/content/152239542650123.png";
        $info['serviceTel']     = "95338";
        $info['logisticStatus'] = "";

        $list['wuliu_info']['info']           = $info;
        $list['wuliu_info']['logStatusList']  = $newarry;

        api_resopnse( $list, ApiStatus::CODE_0 );
        return;
    }
}
