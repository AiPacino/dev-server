<?php

namespace zuji\sms;
use zuji\Config;

/**
 * @author wuhaiyan
 */
class SendSms {

    /**
     * 取消订单短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function cancel_order($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_8d9dc',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }
    /**
     * 解除资金预授权短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function remove_authorize($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'SMS_113451380',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }
    /**
     * 退货检测合格发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function evaluation_qualified($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_1607a',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }
    /**
     * 退货检测不合格发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function evaluation_unqualified($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_1c8bf',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 申请退货发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function apply_return($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_771f7',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 申请退货-同意发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'shoujianrenName'=>''【必须】收件人名字
     * ]
     */
    public static function agree_return($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'shoujianrenName'=>'required',
            'returnAddress'=>'required',
        ]);
        if( count($data)!=6 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'SMS_113455999',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'shoujianrenName' => $data['shoujianrenName'],
            'returnAddress' => $data['returnAddress'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 申请退货-拒绝发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function adenied_return($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_d284d',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 收到退货发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     * ]
     */
    public static function receive_confirmed($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_e36c8',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }


    /**
     * 确认收货发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuQi'=>''【必须】租期
     *      'zuQiType'=>''【必须】租期类型
     *      'beginTime'=>''【必须】开始时间
     *      'endTime'=>''【必须】结束时间
     *      'zuJin'=>''【必须】租金
     *      'createTime'=>''【必须】首次扣款日期
     *
     * ]
     */
    public static function confirmed_delivery($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuQi' => 'required',
            'zuQiType' => 'required',
            'beginTime' => 'required',
            'endTime' =>'required',
            'zuJin' =>'required',
            'createTime' =>'required',
        ]);
        if( count($data)!=10 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
		if( $data['zuQiType'] == 1 ) {
			$b = true;
		}elseif( $data['zuQiType'] == 2 ){
			$b = $sms->send_sm($data['mobile'],'hsb_sms_c87b3',[
				'realName' => $data['realName'],
				'orderNo' => $data['orderNo'],
				'goodsName' => $data['goodsName'],
				'zuQi' => $data['zuQi'],
				'beginTime' => $data['beginTime'],
				'endTime' => $data['endTime'],
				'zuJin' => $data['zuJin'],
				'createTime' => $data['createTime']."15",
				'serviceTel'=>Config::Customer_Service_Phone,
			],$data['orderNo']);
		}else{
			$b = true;
		}
        return $b;
    }


    /**
     * 分期扣款前三天发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     *      'createTime'=>''【必须】首次扣款日期
     *
     * ]
     */
    public static function instalment_three_day($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
            'createTime' =>'required',
        ]);
        if( count($data)!=6 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_fe7c8',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'zuJin' => $data['zuJin'],
            'createTime' => $data['createTime'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }


    /**
     * 分期扣款前一天发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     *      'createTime'=>''【必须】首次扣款日期
     *
     * ]
     */
    public static function instalment_one_day($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
            'createTime' =>'required',
        ]);
        if( count($data)!=6 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_b5fd2',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'zuJin' => $data['zuJin'],
            'createTime' => $data['createTime'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 扣款成功发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     * ]
     */
    public static function instalment_pay($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
        ]);
        if( count($data)!=5 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_b427f',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'zuJin' => $data['zuJin'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }
    /**
     * 首次扣款失败发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     * ]
     */
    public static function instalment_pay_failed($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
        ]);
        if( count($data)!=5 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_99a6f',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'zuJin' => $data['zuJin'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }
    /**
     * 第二次第三次扣款失败发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     * ]
     */
    public static function instalment_pay_next_failed($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
        ]);
        if( count($data)!=5 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_16f75',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'zuJin' => $data['zuJin'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 首次扣款失败发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     * ]
     */
    public static function instalment_pay_more_failed($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
        ]);
        if( count($data)!=5 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'hsb_sms_7326b',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'zuJin' => $data['zuJin'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        return $b;
    }

    /**
     * 授权成功发送短信
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     *      'orderNo'=>'',【必须】订单编号
     *      'realName'=>''【必须】真实姓名
     *      'goodsName'=>''【必须】商品名称
     *      'zuJin'=>''【必须】租金
     * ]
     */
    public static function authorize_success($data){
        $data = filter_array($data, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
        ]);
        if( count($data)!=4 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();
        $b = $sms->send_sm($data['mobile'],'SMS_113455988',[
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
        \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '支付短信', $b);
        return $b;
    }

    /**
     * 发送验证码
     * @param $data
     * [
     *      'mobile'=>'',【必须】手机号
     * ]
     * @return boolean
     */
    public static function send_code($data){
        $data = filter_array($data, [
            'mobile' => 'required',
        ]);
        $code = mt_rand(100000,999999);

        if( count($data) != 1 ){
            set_error('短信参数错误');
            return false;
        }
        $sms = new HsbSms();

        $b = $sms->send_code($data['mobile'],'SMS_113450943',[
            'code' => $code,    // 冗余参数，验证码接口内部自己生成随机数
        ]);
        return $b;
    }

}
