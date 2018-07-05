<?php
/**
 * 租机 API 入口文件
 * @access public
 * @author wangjinlin <wangjinlin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 *
 */
header("Access-Control-Allow-Origin: *");
error_reporting( E_ALL^E_NOTICE^E_WARNING );
date_default_timezone_set("Asia/Shanghai");


// 接口映射
$mcas = array(
    // 用户登录接口
    'zuji.user.login' => array('m'=>'api','c'=>'user','a'=>'login',),
    // 用户退出接口
    'zuji.user.logout' => array('m'=>'api','c'=>'user','a'=>'logout',),
    //授权码获取
    'zuji.auth.code' => array('m'=>'api','c'=>'code','a'=>'get',),
    //授权token获取
    'zuji.auth.token' => array('m'=>'api','c'=>'token','a'=>'get',),
    // 第三方授权初始化接口
    'zuji.authorization.initialize' => array('m'=>'api','c'=>'authorization','a'=>'initialize',),
    'zuji.authorization.query' => array('m'=>'api','c'=>'authorization','a'=>'query',),

    //银联开通并支付接口
    'zuji.unionpay.openandpay' => array('m'=>'api','c'=>'unionpay','a'=>'openandpay',),
    //银联开通接口
    'zuji.unionpay.open' => array('m'=>'api','c'=>'unionpay','a'=>'open',),
    //银联已开通银行卡列表查询接口
    'zuji.unionpay.cardlist' => array('m'=>'api','c'=>'unionpay','a'=>'cardlist',),
    //银联开通银行卡结果查询接口
    'zuji.unionpay.get' => array('m'=>'api','c'=>'unionpay','a'=>'get',),
    //银联短信验证码发送接口
    'zuji.unionpay.sendsms' => array('m'=>'api','c'=>'unionpay','a'=>'sendsms',),
    //银联支付消费接口(限已开通银联用户)
    'zuji.unionpay.consume' => array('m'=>'api','c'=>'unionpay','a'=>'consume',),

    //支付异步通知接口
    'zuji.notify.pay' => array('m'=>'pay_notify_api','c'=>'notify','a'=>'pay',),
    //退款异步通知接口
    'zuji.notify.refund' => array('m'=>'pay_notify_api','c'=>'notify','a'=>'refund',),

    // 实名认证初始化接口
    'zuji.certification.initialize' => array('m'=>'api','c'=>'certification','a'=>'initialize',),
    // 实名认证查询接口
    'zuji.certification.get' => array('m'=>'api','c'=>'certification','a'=>'get',),

    // 信用评分初始化接口
    'zuji/credit/initialize' => array('m'=>'api','c'=>'credit','a'=>'initialize',),
    // 信用评分查询接口
    'zuji.credit.get' => array('m'=>'api','c'=>'credit','a'=>'get',),

    // 用户使用总数统计
    'zuji.user.sum.query' => array('m'=>'api','c'=>'user_sum','a'=>'query',),

    // 用户收货地址查询接口（列表）
    'zuji.user.address.query' => array('m'=>'api','c'=>'address','a'=>'query',),
    // 用户收货地址创建接口
    'zuji.user.address.create' => array('m'=>'api','c'=>'address','a'=>'create',),
    // 用户收货地址修改
    'zuji.user.address.update' => array('m'=>'api','c'=>'address','a'=>'update',),

    // 内容查询接口
    'zuji.content.query' => array('m'=>'api','c'=>'content','a'=>'query',),
    // 全部产品列表查询（包含品牌列表）
    'zuji.goods.spu.query.all' => array('m'=>'api','c'=>'goods','a'=>'queryall',),
    // 产品详情查询
    'zuji.goods.spu.get' => array('m'=>'api','c'=>'goods','a'=>'queryone',),
    // 产品支付列表查询
    'zuji.goods.payment' => array('m'=>'api','c'=>'goods','a'=>'payment',),

    // 地域列表查询接口（全部）
    'zuji.district.query.all' => array('m'=>'api','c'=>'district','a'=>'query_all',),
    'zuji.district.query.all.tree' => array('m'=>'api','c'=>'district','a'=>'query_all_tree',),

    // 短信发送接口
    'zuji.sms.send' => array('m'=>'api','c'=>'sms','a'=>'send',),
    // 短信发送接口
    'zuji.sms_code.send' => array('m'=>'api','c'=>'sms_code','a'=>'send',),
    // 短信发送接口
    'zuji.sms_code.verification' => array('m'=>'api','c'=>'sms_code','a'=>'verification',),
    
    // 物流渠道列表查询接口
    'zuji.wuliu.channel.query' => array('m'=>'api','c'=>'wuliu','a'=>'channel_query',),
    // 物流查询接口
    'zuji.wuliu.get' => array('m'=>'api','c'=>'wuliu','a'=>'get',),

    // 问题原因列表查询接口
    'zuji.reason.query' => array('m'=>'api','c'=>'reason','a'=>'query',),
    // 设备损耗列表查询接口
    'zuji.sunhao.query' => array('m'=>'api','c'=>'sunhao','a'=>'query',),
    // 回寄地址列表查询接口
    'zuji.service.address.query' => array('m'=>'api','c'=>'service_address','a'=>'query',),

    // 优惠券列表查询接口
    'zuji.coupon.query' => array('m'=>'api','c'=>'coupon','a'=>'query',),
    // 优惠券商品可用列表查询接口
    'zuji.coupon.checked' => array('m'=>'api','c'=>'coupon','a'=>'checked',),
    // 优惠券激活接口
    'zuji.coupon.activate' => array('m'=>'api','c'=>'coupon','a'=>'activate',),
    // 优惠券领取接口
    'zuji.coupon.receive' => array('m'=>'api','c'=>'coupon','a'=>'receive',),
    // 优惠券领取接口2
    'zuji.coupon.receives' => array('m'=>'api','c'=>'coupon','a'=>'receives',),

    // 支付渠道列表查询接口
    'zuji.trade.channel.query' => array('m'=>'api','c'=>'trade','a'=>'channel_query',),
    // 支付初始化接口
    'zuji.trade.initialize' => array('m'=>'api','c'=>'trade','a'=>'initialize',),
    // 支付交易查询接口
    'zuji.trade.get' => array('m'=>'api','c'=>'trade','a'=>'get',),

    //预授权冻结金额
    'zuji.fund_auth.initialize' => array('m'=>'api','c'=>'fund_auth','a'=>'initialize',),

    // 退货申请接口
    'zuji.order.return.apply' => array('m'=>'api','c'=>'order','a'=>'return_apply',),
    // 退货记录列表查询接口
    'zuji.order.return.query' => array('m'=>'api','c'=>'order','a'=>'return_query',),
    // 退货结果查看接口
    'zuji.order.return.get' => array('m'=>'api','c'=>'order','a'=>'return_get',),
    // 退货物流上传接口
    'zuji.order.return.logistics' => array('m'=>'api','c'=>'order','a'=>'return_logistics',),
    // 订单确认查询接口
    'zuji.order.confirmation.query' => array('m'=>'api','c'=>'order','a'=>'confirmation_query',),
    // 订单创建接口
    'zuji.order.creation.create' => array('m'=>'api','c'=>'order','a'=>'create',),
    // 线下订单创建接口
    'offline.store.order.create' => array('m'=>'api','c'=>'order','a'=>'offline_create'),
    // 订单列表查询接口
    'zuji.order.query' => array('m'=>'api','c'=>'order','a'=>'query',),
    // 订单详情查询接口
    'zuji.order.get' => array('m'=>'api','c'=>'order','a'=>'get',),
    // 默认订单详情查询接口
    'zuji.order.get_default' => array('m'=>'api','c'=>'order','a'=>'get_default',),
    // 订单取消接口
    'zuji.order.cancel' => array('m'=>'api','c'=>'order','a'=>'cancel',),
    // 订单确认收货接口
    'zuji.order.delivery' => array('m'=>'api','c'=>'order','a'=>'delivery',),
    // 订单申请退款接口
    'zuji.order.refund' => array('m'=>'api','c'=>'order','a'=>'refund',),
    //订单支付状态查询接口
    'zuji.order.pay.status' => array('m'=>'api','c'=>'order','a'=>'pay_status',),
    // 订单支付分期期数查询接口
    'zuji.installment.query' => array('m'=>'api','c'=>'installment','a'=>'query',),
    // 订单分期提前还款接口
    'zuji.installment.prepayment' => array('m'=>'api','c'=>'installment','a'=>'prepayment',),
    //订单电子合同
    'zuji.contract.get' => array('m'=>'api','c'=>'contract','a'=>'get',),

    // 维修服务列表查询接口
    'zuji.weixiu.service.query' => array('m'=>'api','c'=>'weixiu','a'=>'service_query',),
    // 维修申请接口
    'zuji.weixiu.apply' => array('m'=>'api','c'=>'weixiu','a'=>'apply',),
    // 维修记录查看接口
    'zuji.weixiu.query' => array('m'=>'api','c'=>'weixiu','a'=>'query',),
    // 维修详情查看接口
    'zuji.weixiu.get' => array('m'=>'api','c'=>'weixiu','a'=>'get',),
    // 维修物流上传接口
    'zuji.weixiu.upload' => array('m'=>'api','c'=>'weixiu','a'=>'upload',),

    /**********************活动查询接口**********************************/
    // 活动文章查询接口
    'zuji.article.query' => array('m'=>'api','c'=>'article','a'=>'query',),



    /**********************线下门店接口**********************************/

    //门店会员登录接口
    'offline.store.member.login' => array('m'=>'offline_store_api','c'=>'store_member','a'=>'login'),
    //用户密码修改接口
    'offline.store.member.password' => array('m'=>'offline_store_api','c'=>'store_member','a'=>'edit_password'),
    //门店信息查询接口
    'offline.store.shop.get' => array('m'=>'offline_store_api','c'=>'appid','a'=>'query'),

    // 订单列表查询接口
    'offline.store.order.query' => array('m'=>'offline_store_api','c'=>'order','a'=>'query',),
    // 订单详情查询接口
    'offline.store.order.get' => array('m'=>'offline_store_api','c'=>'order','a'=>'get',),
    // 订单取消接口
    'offline.store.order.cancel' => array('m'=>'offline_store_api','c'=>'order','a'=>'cancel',),
    // 确认签署
    'offline.store.order.confirm' => array('m'=>'offline_store_api','c'=>'order','a'=>'confirm',),
    // 提交协议
    'offline.store.order.submit' => array('m'=>'offline_store_api','c'=>'order','a'=>'submit',),
    // 图片上传
    'offline.store.upload.images' => array('m'=>'offline_store_api','c'=>'upload','a'=>'images',),

	// 代扣 签约
    'zuji.withholding.initialize' => array('m'=>'api','c'=>'withholding','a'=>'initialize',),
	// 代扣 解约
    'zuji.withholding.unsign' => array('m'=>'api','c'=>'withholding','a'=>'unsign',),
	// 代扣 状态查询
    'zuji.withholding.get' => array('m'=>'api','c'=>'withholding','a'=>'get',),
	
    /***************************商家管理平台接口********************************************/
    //渠道账户登录接口
    'business.user.login' => array('m'=>'business_manage_api','c'=>'channel_member','a'=>'login'),
    //渠道账户退出接口
    'business.user.logout' => array('m'=>'business_manage_api','c'=>'channel_member','a'=>'logout'),
    //渠道信息查询接口
    'business.channel.get' => array('m'=>'business_manage_api','c'=>'channel','a'=>'query'),
    //品牌列表查询接口
    'business.brand.query' => array('m'=>'business_manage_api','c'=>'brand','a'=>'query'),
    //订单状态列表查询接口
    'business.order.status' => array('m'=>'business_manage_api','c'=>'order','a'=>'status'),
    //渠道订单列表查询接口
    'business.order.query' => array('m'=>'business_manage_api','c'=>'order','a'=>'query',),
    //渠道订单详情查询接口
    'business.order.get' => array('m'=>'business_manage_api','c'=>'order','a'=>'get',),
    //门店数据统计列表接口
    'business.appid.count.query' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'get_appid_count_list',),
    //渠道数据统计列表接口
    'business.channel.count.query' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'get_channel_count_list',),
    //基本数据统计接口
    'business.base.data.count' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'base_data_count',),
    //订单趋势图统计接口
    'business.order.diagram.count' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'order_diagram_count',),
    //机型排行统计接口
    'business.machine.rank.count' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'machine_rank_count',),
    //单个机型趋势图接口
    'business.machine.diagram.count' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'machine_diagram_count',),
    //设置pv,uv接口
    'business.page.view.set' => array('m'=>'business_manage_api','c'=>'count_channel','a'=>'set_page_view',),
    //设置debug接口
    'zuji.debug.debug' => array('m'=>'api','c'=>'debug','a'=>'debug',),

    /*********************************支付宝小程序相关接口**************************************/
    //确认订单接口
    'zuji.mini.order.confirmation.query' => array('m'=>'api', 'c'=>'mini_order', 'a'=>'confirmation_query'),
    //创建临时订单接口
    'zuji.mini.temp.order.get' => array('m'=>'api', 'c'=>'mini_order', 'a'=>'temporary_order'),
    //创建订单接口
    'zuji.mini.order.create' => array('m'=>'api', 'c'=>'mini_order', 'a'=>'create'),
    //取消订单接口
    //'zuji.mini.order.cancel' => array('m'=>'api', 'c'=>'mini_order', 'a'=>'zhima_cancel'),
    //取消订单接口（映射到原来的订单取消接口上）
    'zuji.mini.order.cancel' => array('m'=>'api', 'c'=>'order', 'a'=>'cancel'),
    //前段确认订单同步通知接口
    'zuji.mini.order.front' => array('m'=>'api', 'c'=>'mini_order', 'a'=>'front_transition'),



    /*********************************faceid**************************************/
    'zuji.mini.face.verify' => array('m'=>'api', 'c'=>'face', 'a'=>'verify'),

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
    $GLOBALS['__ApiResponse__']->setData($data)->setCode($code)->setMsg($msg)->setSubCode($subcCode)->setSubMsg($subMsg);
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
            define('IS_API', true);
        }else{
            echo 'error';exit;
        }
    }else{
        // 非 API 请求
        define('IS_API', false);
    }

    include __DIR__.'/index.php';
    $GLOBALS['__ApiResponse__']->flush();
}catch (\Exception $e){
//    var_dump( $e->getMessage() );exit;
    $data = array(
        'File' => $e->getFile(),
        'Line' => $e->getLine(),
        'Msg' => $e->getMessage(),
    );
    api_resopnse($data,  ApiStatus::CODE_50000,$e->getMessage());
    $GLOBALS['__ApiResponse__']->flush();
    exit;
}
