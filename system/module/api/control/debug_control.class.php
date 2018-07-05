<?php
/**
 * DEBUG块 基类控制器
 */
use zuji\debug\Debug;
use zuji\debug\Location;

class debug_control{
    public function debug() {
        $params = api_params();
        $params = filter_array($params,[
            'message'=>'required',
            'data'=>'required',
        ]);
        if(!isset($params['message'])){
            api_resopnse( [], ApiStatus::CODE_20001,'message参数不存在', ApiSubCode::Params_Error,'');
            return;
        }
        if(!isset($params['data'])){
            api_resopnse( [], ApiStatus::CODE_20001,'data参数不存在', ApiSubCode::Params_Error,'');
            return;
        }
        Debug::error(Location::L_Payment_product, $params['message'], $params['data'] );
        api_resopnse( [], ApiStatus::CODE_0);
        return;

    }
}