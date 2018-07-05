<?php
hd_core::load_class('api', 'api');
/**
 * 用户收货地址 API
 * @access public （访问修饰符）
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class address_control extends user_control {

    public function _initialize() {
	    parent::_initialize();
        $this->userId = $this->member['id'];
	    $this->service = $this->load->service('member/member_address');
    }
    /**
    * 用户收货地址
    * @return $address
    * @author limin
    */
    public function query(){
        $userid = $this->userId ;
        $data = $this->service->user_address_default($userid);
        if(!$data){
            api_resopnse(array('address_list'=>""), ApiStatus::CODE_0 );
            return;
        }
        if ($data['mid'] != $this->userId) {
            api_resopnse( [], ApiStatus::CODE_50001);
            return;
        }
        $data['address_id'] = $data['id'];
        unset($data['id']);
        unset($data['mid']);
        api_resopnse( array('address_list'=>$data), ApiStatus::CODE_0 );
        return;
    }

    /**
    * 收货地址创建
    * @return $address
    * @author limin
    */
    public function create() {
        //接收请求参数
        $data = $this->params;
        //过滤参数
        $param = filter_array($data,[
            "country_id"  => "required",
            "provin_id"    => "required",
            "city_id"        => "required",
            "address"      => "required",
            "name"          => "required",
            "mobile"         => "required|is_mobile",
            "zipcode"        => "required",
        ]);
        //验证参数
        if( empty($param['country_id'])){
            api_resopnse( [], ApiStatus::CODE_20001,'country_id必须', ApiSubCode::Address_Error_Country_id);
            return;
        }
        if( empty($param['provin_id'])){
            api_resopnse( [], ApiStatus::CODE_20001,'provin_id必须', ApiSubCode::Address_Error_Provin_id);
            return;
        }
        if( empty($param['city_id'])){
            api_resopnse( [], ApiStatus::CODE_20001,'city_id必须', ApiSubCode::Address_Error_City_id);
            return;
        }
        if( empty($param['address'])){
            api_resopnse( [], ApiStatus::CODE_20001,'address必须', ApiSubCode::Address_Error_Address);
            return;
        }
        if( strlen($param['address'])<5){
            api_resopnse( [], ApiStatus::CODE_20001,'address最少5位', ApiSubCode::Address_Error_Address);
            return;
        }
        if( empty($param['name'])){
            api_resopnse( [], ApiStatus::CODE_20001,'name必须', ApiSubCode::Address_Error_Name);
            return;
        }
        if( empty($param['mobile'])){
            api_resopnse( [], ApiStatus::CODE_20001,'mobile必须', ApiSubCode::Address_Error_Mobile);
            return;
        }

        $data = array(
            "district_id"  => $param['country_id'],
            "address"    => $param["address"],
            "name"      => $param["name"],
            "mobile"     => $param["mobile"],
            "zipcode"    => $param["zipcode"],
            "mid"        => $this->userId,
        );
        //添加地址
        $address_id = $this->service->add($data);
        if(!$address_id){
            api_resopnse( [], ApiStatus::CODE_50000,'网络异常' );
            return ;
        }
        $data['provin_id'] = $param["provin_id"];
        $data['city_id']   = $param["city_id"];
        $data['address_id'] = $address_id;
        unset($data['mid']);
        api_resopnse( array('address_info'=>$data),ApiStatus::CODE_0 );
        return ;
    }

    /**
    * 收货地址更新
    * @return $address
    * @author limin
    */
    public function update()
    {
        $param   = $this->params;
        $param = filter_array($param,[
            "address_id"    => "required|is_id",
            "country_id"    => "required",
            "provin_id"	    => "required",
            "city_id"       => "required",
            "address"       => "required",
            "name"          => "required",
            "mobile"        => "required|is_mobile",
            "zipcode"       => "required",
        ]);
        if( empty($param['zipcode']) ){
            $param['zipcode'] = '';
        }
        if( empty($param['address_id'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_Address_id,'' );
            return;
        }
        if( empty($param['country_id'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_Country_id,'' );
            return;
        }
        if( empty($param['provin_id'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_Provin_id,'' );
            return;
        }
        if( empty($param['city_id'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_City_id,'' );
            return;
        }
        if( empty($param['address'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_Address,'' );
            return;
        }
        if( empty($param['name'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_Name,'' );
            return;
        }
        if( empty($param['mobile'])){
            api_resopnse( [], ApiStatus::CODE_20000,'', ApiSubCode::Address_Error_Mobile,'' );
            return;
        }
        //获取地址信息
        $address = $this->service->mid($this->userId)->fetch_by_id($param['address_id']);

        if (!$address || $address['mid'] != $this->userId){
            api_resopnse( [], ApiStatus::CODE_50000);
            return ;
        }

        $data = array(
            "district_id" => $param['country_id'],
            "address"  => $param["address"],
            "name"    => $param["name"],
            "mobile"   => $param["mobile"],
            "zipcode"  => $param["zipcode"],
            "id"	       => $param['address_id'],
            "mid"	   => $this->userId,
        );
        //更新地址
        $result = $this->service->edit($data);
        if(!$result){
            api_resopnse( [], ApiStatus::CODE_50000,'业务处理失败' );
            return ;
        }
        $data['provin_id'] = $param["provin_id"];
        $data['city_id']   = $param["city_id"];
	    $data['address_id'] = $param['address_id'];
        unset($data['id']);
        unset($data['mid']);
        api_resopnse( array('address_info'=>$data),ApiStatus::CODE_0  );
        return ;
    }
}
