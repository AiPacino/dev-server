<?php
/**
 *	  地域控制器
 */
hd_core::load_class('api', 'api');
class district_control extends api_control {

    public $position = array('index-lunbo'=>4,'index-hot'=>7,'index-brand'=>6);
    public $limit = 20;

    public function _initialize() {
	    parent::_initialize();
	    $this->service = $this->load->service('admin/district');
    }

    /**
     * 根据位置ID，获取内容方式列表接口
     */
    public function query_all() {
        
        $data = [
            'district_list' => []
        ];
        
	    $data['address_list'] = $this->service->get_children2();
        foreach( $data['address_list'] as &$item ){
            
            $item['children'] = $this->service->get_children2($item['id']);
            
            foreach( $item['children']  as &$it ){
                $it['children'] = $this->service->get_children2($it['id']);
            }
        }
    	api_resopnse( $data, ApiStatus::CODE_0 );
    	return ;
	}
    
    /**
     * 根据位置ID，获取内容方式列表接口 返回三个数组
     */
    public function query_all_tree() {
        
        $data = [
            'district_list' => []
        ];
        
        
	    $list = $this->service->get_children2();
        $provin = $list;
        $city   = array();
        $country = array();
        foreach( $list as &$item ){
            
            $item['children'] = $this->service->get_children2($item['id']);
            if($item['children']){
                $city  = array_merge($city,$item['children']);
            }
            foreach( $item['children']  as &$it ){
                $it['children'] = $this->service->get_children2($it['id']);
                if($it['children'] ){
                    $country  = array_merge($country,$it['children']);
                }
            }
        }
        
        $data = array("provin"=>$provin,"city"=>$city,"country"=>$country);
        api_resopnse( $data, ApiStatus::CODE_0 );
    	return ;
	}
    
}
