<?php

/* 除非您特别了解以下配置项，否则不建议更改任何代码 */
return array(
    /* 默认设定 */
    'DEFAULT_H_LAYER'  =>  'module',
    'DEFAULT_M_LAYER'  =>  'model', // 默认的模型层名称
    'DEFAULT_C_LAYER'  =>  'control', // 默认的控制器层名称
    'DEFAULT_V_LAYER'  =>  'template', // 默认的视图层名称
    'DEFAULT_LANG'     =>  'zh-cn', // 默认语言
    'DEFAULT_THEME'    =>  'default',	// 默认模板主题名称
    'DEFAULT_MODULE'   =>  'goods',  // 默认模块
    'DEFAULT_CONTROL'  =>  'index', // 默认控制器名称
    'DEFAULT_METHOD'   =>  'index', // 默认操作名称
    'DEFAULT_CHARSET'  =>  'utf-8', // 默认输出编码
    'DEFAULT_TIMEZONE' =>  'PRC',	// 默认时区

    /* 系统变量名称设置 */
    'VAR_MODULE'        =>  'm',     // 默认模块获取变量
    'VAR_CONTROL'       =>  'c',    // 默认控制器获取变量
    'VAR_METHOD'        =>  'a',    // 默认操作获取变量
    'VAR_AJAX_SUBMIT'   =>  'inajax',  // 默认的AJAX提交变量
    'VAR_JSONP_HANDLER' =>  'callback',
    'VAR_TEMPLATE'      =>  't',    // 默认模板切换变量
    'VAR_LANG'          =>  'l',    // 默认语言切换变量
    'VAR_AUTO_STRING'   =>	false,	// 输入变量是否自动强制转换为字符串 如果开启则数组变量需要手动传入变量修饰符获取变量

    /* 数据缓存设置 */
    'DATA_CACHE_TIME'       => 0,      // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS'   => false,   // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK'      => false,   // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX'     => '',     // 缓存前缀
    'DATA_CACHE_TYPE'       => 'file',  // 数据缓存类型,支持:file|db|apc|memcache|shmop|sqlite|xcache|apachenote|eaccelerator
    'DATA_CACHE_PATH'       => CACHE_PATH,// 缓存路径设置 (仅对File方式缓存有效)
    'DATA_CACHE_SUBDIR'     => false,    // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
    'DATA_PATH_LEVEL'       => 1,        // 子目录缓存级别

    'AUTHKEY'               =>'GtVzWpFf6gCquTwB6GuE9Tu6',

    /* Cookie设置 */
    'COOKIE_EXPIRE'         => 0,    // Coodie有效期
    'COOKIE_DOMAIN'         => '',      // Cookie有效域名
    'COOKIE_PATH'           => '/',     // Cookie路径
    'COOKIE_PREFIX'         =>'nMZwW_',      // Cookie前缀 避免冲突

    /* SESSION设置 */
    'SESSION_AUTO_START'    => true,    // 是否自动开启Session
    'SESSION_OPTIONS'       => array(), // session 配置数组 支持type name id path expire domain 等参数
    'SESSION_TYPE'          => '', // session hander类型 默认无需设置 除非扩展了session hander驱动
    'SESSION_PREFIX'        =>'xJBv3_', // session 前缀
    //'VAR_SESSION_ID'      => 'session_id',     //sessionID的提交变量


    /* 模板相关配置 */
    'TMPL_CACHE_ON' => TRUE, // 开启模板缓存
    'TMPL_CACHE_COMPARE'    => 0, //缓存时间
    'TMPL_TEMPLATE_SUFFIX'  => '.html', //模板后缀



    /*自定义类前缀*/
    'SUBCLASS_PREFIX'   => 'MY_',

    'OUTPUT_ENCODE'      =>  false, // 页面压缩输出
    'HTTP_CACHE_CONTROL' =>  'private', // 网页缓存控制
    
    // 图片url前缀（域名部分）
    'IMG_URL' => 'https://s1.huishoubao.com',

	// redis 配置
	'REDIS_HOST' => $_SERVER['REDIS_HOST']?$_SERVER['REDIS_HOST']:'192.168.3.31',
	'REDIS_PORT' => $_SERVER['REDIS_PORT']?$_SERVER['REDIS_PORT']:'6379',
	'REDIS_AUTH' => $_SERVER['REDIS_AUTH']?$_SERVER['REDIS_AUTH']:'',

	// session 存储默认配置（在 base.php 文件中，有限选用 $_SERVER 中的 session 存储配置 ）
	'SESSION_SAVE_HANDLER' => 'redis',
	'SESSION_SAVE_PATH' => 'tcp://192.168.3.31:6379',
	'SESSION_GC_MAXLIFETIME' => '604800',
    
    // 租金 最小价格，单位（元）
    'ZUJIN_MIN_PRICE' => 0.01,

    //图片服务器访问地址
    'Images_server_url' => $_SERVER['Images_server_url']?$_SERVER['Images_server_url']:'https://s1.huishoubao.com',
    
    // 上传配置
    'Api_Upload_Key' => $_SERVER['Api_Upload_Key']?$_SERVER['Api_Upload_Key']:'8oxq1kma0eli9vlxnyj8v7qk335uvrf0',
    
    // 图片上传地址
    'Api_Upload_File_Url' => $_SERVER['Api_Upload_File_Url']?$_SERVER['Api_Upload_File_Url']:'http://push.huanjixia.com/upload/handle',
    
    /*上传缩略图规格*/
    'THUMB_SUFFIX' => "_thumb",
    //商品图规格
    'GOODS'  => [
        'width'=>385,
        'height'=>385
    ],
    //轮播图规格
    'BANNER' => [
        'width'=>680,
        'height'=>325
    ],
    //推荐图规格
    'RECOMMEND' => [
        'width'=>214,
        'height'=>214
    ],
    
    // 支付宝应用号ID
    'ALIPAY_APP_ID' => $_SERVER['ALIPAY_APP_ID']?$_SERVER['ALIPAY_APP_ID']:'2017101309291418',
    // 支付宝小程序APP_ID
    'ALIPAY_MINI_APP_ID' => $_SERVER['ALIPAY_MINI_APP_ID']?$_SERVER['ALIPAY_MINI_APP_ID']:'2018032002411058',

    //测试账号
    'Test_Mobile' => "14444444444",
    'Test_Mobile_Verify' => "123456",
    'Test_Mobile_On' => true,
	
	// 支付宝代扣 签约异步通知
	'ALIPAY_WITHHOLDING_NOTIFY' => $_SERVER['ALIPAY_WITHHOLDING_NOTIFY']?$_SERVER['ALIPAY_WITHHOLDING_NOTIFY']:'https://dev-admin-zuji.huishoubao.com/alipay/withholding_notify_url.php',
	// 支付宝代扣 扣款异步通知
	'ALIPAY_WITHHOLDING_CREATEPAY_NOTIFY' => $_SERVER['ALIPAY_WITHHOLDING_CREATEPAY_NOTIFY']?$_SERVER['ALIPAY_WITHHOLDING_CREATEPAY_NOTIFY']:'https://dev-admin-zuji.huishoubao.com/alipay/withholding_createpay_notify_url.php',
	
	// 资金预授权 授权或解冻 异步通知接口
	'ALIPAY_FundAuth_Notify_Url' => $_SERVER['ALIPAY_FundAuth_Notify_Url']?$_SERVER['ALIPAY_FundAuth_Notify_Url']:'https://dev-api-zuji.huishoubao.com/alipay/fundauth_notify_url.php',

	// 资金预授权 解冻转支付 异步通知
	'ALIPAY_FundAuth_Pay_Url' => $_SERVER['ALIPAY_FundAuth_Pay_Url']?$_SERVER['ALIPAY_FundAuth_Pay_Url']:'https://dev-api-zuji.huishoubao.com/alipay/fundauth_createpay_notify_url.php',

    // 资金预授权 分期解冻异步通知
    'ALIPAY_FundAuth_Fenqi_Unfreeze_Notify_Url' => $_SERVER['ALIPAY_FundAuth_Fenqi_Unfreeze_Notify_Url']?$_SERVER['ALIPAY_FundAuth_Fenqi_Unfreeze_Notify_Url']:'https://dev-api-zuji.huishoubao.com/alipay/fundauth_fenqi_unfreeze_notify_url.php',

    // 资金预授权 分期解冻转支付异步通知
    'ALIPAY_FundAuth_Fenqi_Unfreeze_To_Pay_Notify_Url' => $_SERVER['ALIPAY_FundAuth_Fenqi_Unfreeze_To_Pay_Notify_Url']?$_SERVER['ALIPAY_FundAuth_Fenqi_Unfreeze_To_Pay_Notify_Url']:'https://dev-api-zuji.huishoubao.com/alipay/fundauth_fenqi_unfreeze_to_pay_notify_url.php',

    // 资金预授权 押金解冻异步通知
    'ALIPAY_FundAuth_Yajin_Unfreeze_Notify_Url' => $_SERVER['ALIPAY_FundAuth_Yajin_Unfreeze_Notify_Url']?$_SERVER['ALIPAY_FundAuth_Yajin_Unfreeze_Notify_Url']:'https://dev-api-zuji.huishoubao.com/alipay/fundauth_yajin_unfreeze_notify_url.php',

    // 资金预授权 押金解冻转支付异步通知
    'ALIPAY_FundAuth_Yajin_Unfreeze_To_Pay_Notify_Url' => $_SERVER['ALIPAY_FundAuth_Yajin_Unfreeze_To_Pay_Notify_Url']?$_SERVER['ALIPAY_FundAuth_Yajin_Unfreeze_To_Pay_Notify_Url']:'https://dev-api-zuji.huishoubao.com/alipay/fundauth_yajin_unfreeze_to_pay_notify_url.php',

	
	// 流水号前缀（正式环境不使用）
	'BUSINESS_NO_PREFIX' => 'Dev',
	
	// 蚁盾请求的接口url地址
	'YIDUN_REQUEST_URL' => $_SERVER['YIDUN_REQUEST_URL']?$_SERVER['YIDUN_REQUEST_URL']:'http://pmant:8080/pmantcloud/release/get_proposal',

	//内部支付系统接口url地址
	'Interior_Pay_Url' => $_SERVER['Interior_Pay_Url']?$_SERVER['Interior_Pay_Url']:'https://dev-pay-zuji.huishoubao.com/api',

    //退款回调url地址
    'Alipay_Refund_Notify_Url'=>$_SERVER['Alipay_Refund_Notify_Url']?$_SERVER['Alipay_Refund_Notify_Url']:'https://dev-admin-zuji.huishoubao.com/index.php?m=pay_notify_api&c=notify&a=refund',

	// 提前还款 支付异步通知URL地址
	'ALIPAY_Prepayment_Notify_Url' => $_SERVER['ALIPAY_Prepayment_Notify_Url']?$_SERVER['ALIPAY_Prepayment_Notify_Url']:'https://dev-api-zuji.huishoubao.com/alipay/prepayment_notify_url.php',

	//支付宝小程序代扣（关闭订单）异步通知处理
	'ZHIMA_ALIPAY_WITHHOLDING_NOTIFY' => $_SERVER['ZHIMA_ALIPAY_WITHHOLDING_NOTIFY']?$_SERVER['ZHIMA_ALIPAY_WITHHOLDING_NOTIFY']:'https://dev-admin-zuji.huishoubao.com/zhima/zhima_withholding_notify_url.php',

	//支付宝小程序取消订单异步通知处理
	'ZHIMA_ALIPAY_ORDER_CANCEL_NOTIFY' => $_SERVER['ZHIMA_ALIPAY_ORDER_CANCEL_NOTIFY']?$_SERVER['ZHIMA_ALIPAY_ORDER_CANCEL_NOTIFY']:'https://dev-admin-zuji.huishoubao.com/zhima/zhima_order_cancel_notify_url.php',
	//电子合同模板生成接口
	'Contract_Create_Url' => $_SERVER['Contract_Create_Url']?$_SERVER['Contract_Create_Url']:'http://ec-zuji.huishoubao.com/javaserver/contract/upload',
	//电子合同签署接口
	'Contract_Sign_Url' => $_SERVER['Contract_Sign_Url']?$_SERVER['Contract_Sign_Url']:'http://ec-zuji.huishoubao.com/javaserver/contract/sign',
	//物流查询地址
    'Api_Logistics_Url' => $_SERVER['Api_Logistics_Url']?$_SERVER['Api_Logistics_Url']:"https://sf-zuji.huishoubao.com",
);