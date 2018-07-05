<?php
/**
 * 租机 OPENAPI 入口文件
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2017/12/26 0026-下午 5:42
 * @copyright (c) 2017, Huishoubao
 */
header("Access-Control-Allow-Origin: *");
error_reporting( E_ALL^E_NOTICE^E_WARNING );
date_default_timezone_set("Asia/Shanghai");


// 接口映射
$mcas = array(


    // 全部产品列表查询（包含品牌列表）
    'zuji.goods.spu.query.all' => array('m'=>'openapi','c'=>'goods','a'=>'queryall',),
    // 产品详情查询
    'zuji.goods.spu.get' => array('m'=>'openapi','c'=>'goods','a'=>'queryone',),

    
    // 退货申请接口
    'zuji.order.return.apply' => array('m'=>'openapi','c'=>'order','a'=>'return_apply',),

    // 订单创建接口
    'zuji.order.creation.create' => array('m'=>'openapi','c'=>'order','a'=>'create',),

    // 订单取消接口
    'zuji.order.cancel' => array('m'=>'openapi','c'=>'order','a'=>'cancel',),
    // 订单确认收货接口
    'zuji.order.delivery' => array('m'=>'openapi','c'=>'order','a'=>'delivery',),

    // 换货(7天内)

    // 换货成功

    // 换货失败

    
);

include __DIR__.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'/include.php';


$GLOBALS['__ApiRequest__'] = new ApiRequest();

$GLOBALS['__ApiResponse__'] = new ApiResponse();

//获取参数集
function api_request(){
    return $GLOBALS['__ApiRequest__'];
}

//请求参数的集合
function api_params(){
    return $GLOBALS['__ApiRequest__']->getParams();
}

//获取用户授权
function api_auth_token(){
    return $GLOBALS['__ApiRequest__']->getAuthToken();
}
function api_resopnse($data, $code=ApiStatus::CODE_0,$msg='',$subcCode='',$subMsg=''){
    $GLOBALS['__ApiResponse__']->setData($data)->setCode($code)->setMsg($msg)->setSubCode($subcCode)->setSign()->setSubMsg($subMsg);
    return $GLOBALS['__ApiResponse__'];
}


try{

    $status = $GLOBALS['__ApiRequest__']->receive();
    if( $status->isSuccessed() ){
	$method = $GLOBALS['__ApiRequest__']->getMethod();

	// 映射method 到 m c a
	if( $method && isset($mcas[$method]) ){
	    $mca = $mcas[$method];
	    $_GET['m'] = $mca['m'];
	    $_GET['c'] = $mca['c'];
	    $_GET['a'] = $mca['a'];
	    define('IS_OPEN_API', true);

	}else{
		echo 'error';exit;
	}
    }else{
	// 非 API 请求
	define('IS_OPEN_API', false);

    }

    include __DIR__.'/index.php';
    $GLOBALS['__ApiResponse__']->flush();
}catch (\Exception $e){
    var_dump( $e->getMessage() );exit;
    $data = array(
	'File' => $e->getFile(),
	'Line' => $e->getLine(),
	'Msg' => $e->getMessage(),
    );
    api_resopnse($data,  ApiStatus::CODE_50000,$e->getMessage());
    $GLOBALS['__ApiResponse__']->flush();
    exit;
}
