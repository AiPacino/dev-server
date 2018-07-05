<?php

namespace zuji\sms;
/**
 * 
 *
 * @author wuhaiyan
 */
class Sms {
    
    private static function _get_sms_service(){
	static $service = null;
	if( !$service ){
	    $load = \hd_load::getInstance();
	    $service = $load->service('order2/sms');
	}
	return $service;
    }
    
    public static function save($sms_no,$mobile, $data,$order_no,$response){
        $json = json_encode($data);		// 参数序列化
        self::_get_sms_service()->create([
            'sms_no'=>$sms_no,
            'user_mobile'=>$mobile,
            'order_no'=>$order_no,
            'json_data'=>$json,
            'response'=>$response,
        ]);
    }

    public static function getSmsAllList(){
        return [
            "hsb_sms_b6667" =>'【机市】您在机市${storeName}下单的${goodsName}，将在2小时后过期，请您在店员的协助下完成订单。',
            'hsb_sms_f3b1c' =>'【机市】亲爱哒用户${userName}，您好！您在机市上的订单${orderNo}购买${goodsName}即将缴纳租金，租金金额${daikouSum}元，缴纳时间：${koukuanTime}，请保持账户余额充足，以便清算，提高您的芝麻信用值。',
            'hsb_sms_1a8df' =>'【机市】亲爱哒用户${userName}，您好！您好！您在机市上的订单${orderNo}购买${goodsName}的租金，已经成功扣款，扣款金额${daikouSum}元。有任何疑问，可咨询客服：${serviceTel}。用机市，信用开启新生活~',
            'hsb_sms_85df0' =>'【机市】亲爱哒用户${userName}，您好！您好！您在机市上的订单${orderNo}购买${goodsName}的租金${daikouSum}元 ，未扣款成功。请保持支付宝余额充足，以免逾期影响您的信用！',
            'hsb_sms_c6adf' =>'【机市】您在机市${storeName}的订单：${orderNo}已付款成功！请在门店中补全租赁信息后提货。如有疑问可以拨打客服电话${serviceTel}。',
            'hsb_sms_bf0d8' =>'【机市】您在机市${storeName}的订单：${orderNo}已提货成功！如有疑问可以拨打客服电话${serviceTel}。',
            'SMS_113450944' =>'【机市】您已下单成功，请尽快登录支付宝关注“机市”生活号进行付款，http://t.cn/RE2kcPG。订单将在2小时后取消。',
            'SMS_113455988' =>'【机市】亲爱的用户${realName}在机市下了新的订单${orderNo},${goodsName}，机市已经准备您的机型，1-2个工作日内给您安排发货。http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            'SMS_113460968' =>'【机市】亲爱的用户${realName}，您在机市的订单${orderNo}，已经被顺丰小哥哥收走啦，顺丰运单编号${logisticsNo}，物流信息或有延迟，请耐心等待。签收时请准备好身份证或复印件，并将用户协议寄回。',
            'SMS_113455999' =>'【机市】亲爱哒用户${realName}，您好！您的订单${orderNo}，${goodsName}退货申请已通过。请您在三天内将设备寄出，并填写快递单号。邮寄地址：${returnAddress}；收件人：${shoujianrenName}；收件人电话：${serviceTel}。',
            'SMS_113461439' =>'您在机市的订单：${orderNo}，租期即将到期！请您尽快选择处理，如需帮住可联系客服：${serviceTel}。',
            'SMS_113660623' =>'您在机市的订单：${orderNo}，还机成功！感谢您使用机市提供的租机服务！欢迎您再来享受我们提供的服务！',
            'SMS_113660193' =>'您在机市的订单：${orderNo}，买断成功！感谢您使用机市提供的租机服务！欢迎您再来享受我们提供的服务！',
            'SMS_113450943' =>'您在机市登录验证码为：${code}',
            'hsb_sms_8d9dc'=>'【机市】亲爱的用户${realName}，您在机市的租赁订单${orderNo},${goodsName}已经取消，如果对机市有意见或者建议，可致电客服：${serviceTel}。在http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            'SMS_113451380'=>'【机市】亲爱哒用户${realName}，您好！您在机市上的订单${orderNo}，${goodsName}预授权资金已解冻。感谢您使用机市租机。如有疑问，可致电客服：${serviceTel}。在http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            'hsb_sms_1607a'=>'【机市】亲爱哒用户${realName}，您好！您在机市上的订单${orderNo}，${goodsName}退货检测合格，我们将尽快为您解冻预授权金额。如有疑问，可致电客服：${serviceTel}。在http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            'hsb_sms_1c8bf'=>'【机市】亲爱哒用户${realName}，您好！您在机市上的订单${orderNo}，${goodsName}退货检测不合格。我们将尽快和您取得联系，请您保持联系畅通。如有疑问，可致电客服：${serviceTel}。在http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            'hsb_sms_771f7'=>'【机市】亲爱哒用户${realName}，您好！您在机市上的订单${orderNo}，${goodsName}已申请退货。我们将尽快和您取得联系，以验证您是否可以进行退货，请您保持联系畅通。',
            'hsb_sms_d284d'=>'【机市】亲爱哒用户${realName}，您好！您在机市上的订单${orderNo}，${goodsName}退货申请未通过。如有疑问，可致电客服：${serviceTel}。',
            'hsb_sms_e36c8'=>'【机市】亲爱哒用户${realName}，您好！您在机市上的订单${orderNo}，${goodsName}已收到您的退货手机。我们将开始对您的退货进行检测，并尽快为您处理后续流程。如有疑问，可致电客服：${serviceTel}。在http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            'hsb_sms_c87b3'=>'【机市】亲爱的用户${realName}，您在机市的租赁订单${orderNo},${goodsName}，${zuQi}个月 已成功签收，租赁初始日期为${beginTime}，租期${zuQi}个月，租赁结束日期${endTime}，每月租金${zuJin}元，扣款方式：支付宝代扣，扣款日期 每月15日，首次扣款日期${createTime}，为避免扣款异常给您的信用消费带来的不便，请保证15日前您的账户余额充足。感谢您对机市的支持，祝您租机愉快。如有疑问，可致电客服：${serviceTel}。在http://t.cn/RE2kcPG查看更多详情，退订回TD。',
            
            'hsb_sms_b427f'=>'【机市】亲爱哒用户（${realName}），您好！您在机市上的订单（${orderNo}）购买（${goodsName}）的租金，已经成功扣款，扣款金额${zuJin}元。有任何疑问，可咨询客服：${serviceTel}。用机市，信用开启新生活~',
            'hsb_sms_99a6f'=>'【机市】亲爱哒用户（${realName}），您好！您在机市上的订单（${orderNo}）购买（${goodsName}）的租金${zuJin}元 ，未扣款成功。请保持支付宝余额充足，以免逾期影响您的信用！',
            'hsb_sms_16f75'=>'【机市】亲爱哒用户（${realName}），您好！您在机市上的订单（${orderNo}）（${goodsName}） ，未扣款成功，即将形成逾期账单，逾期金额${zuJin}元。逾期将按规定向金融信用基础数据库提供您的不良信息等相关信息。如您已还款，请忽略以上信息。如有疑问，可致电客服：${serviceTel}。',
            'hsb_sms_7326b'=>'【机市】亲爱哒用户（${realName}），您好！您在机市上的订单（${orderNo}）（${goodsName}） ，未扣款成功，已形成账单逾期，拖欠金额${zuJin}元。逾期已向金融信用基础数据库提供您的不良信息等相关信息。逾期将对您的贷款产生影响。如您已还款，请忽略以上信息。如有疑问，可致电客服：${serviceTel}。',
            'hsb_sms_ba3a6'=>'【机市】亲爱的用户 ${realName}，您在机市下的订单${orderNo},${goodsName}，已成交。客服将尽快与您取得联系，并在1-2个工作日内为您安排发货。',
            'hsb_sms_bbb41'=>'【机市】尊敬的用户，您已在机市成功预约租赁三星Galaxy S9，商品到货后我们将第一时间通知您。',
            'hsb_sms_5b828'=>'【机市】尊敬的用户${realName}，您的本月账单应付金额为{zuJin}元。您可以选择在机市：我的>全部订单>点击提前还款，进行提前还款。提前还款成功后将不再执行本月代扣。如您在使用中遇到问题或有其它疑问请联系客服电话：${serviceTel}。',
            'hsb_sms_b5fd2'=>'【机市】尊敬的用户${realName}，您的本月账单应付金额为${zuJin}元。您可以选择在机市：我的>全部订单>点击提前还款，进行提前还款。提前还款成功后将不再执行本月代扣。如您在使用中遇到问题或有其它疑问请联系客服电话：${serviceTel}。',
            'hsb_sms_fe7c8'=>'【机市】尊敬的用户${realName}，您的本月账单应付金额为${zuJin}元。您可以选择在机市：我的>全部订单>点击提前还款，进行提前还款。提前还款成功后将不再执行本月代扣。如您在使用中遇到问题或有其它疑问请联系客服电话：${serviceTel}。',

            'hsb_sms_e0d5a9'=>'【机市】您已下单成功，在节日期间内付款成功的用户，将收到由机市提供的${buchangGift}。由于${jieRi}的到来，机市在${yanchiZhouqi}期间将产生发货延迟，节后统一发货。请尽快登录“机市”进行付款。订单将在${zidongQuxiao}小时后取消。',
            'hsb_sms_7eb75f'=>'【机市】您已下单成功，请尽快在支付宝小程序“机市”中进行付款。订单将在${zidongQuxiao}小时后取消。',
            'hsb_sms_a3b24b'=>'【机市】亲爱哒用户${realName}，您已提前支付本月租金，守信记录已反馈给芝麻信用！机市将为守信用户提供更多福利，请持续关注后续活动。以租代购，畅想信用生活！',

            'hsb_sms_a3bd84'=>'【机市】您已下单成功，由于{jieRi}的到来，机市在${yanchiZhouqi}期间将产生发货延迟，节后统一发货。给您带来的不便，敬请谅解。请尽快登录“机市”进行付款。订单将在${zidongQuxiao}小时后取消。',
            'hsb_sms_4b8a19'=>'【机市】亲爱的用户${realName}，您在机市下的订单${orderNo},${goodsName}，已成交。客服将尽快与您取得联系，请保持电话畅通。由于${jieRi}的到来，机市在${yanchiZhouqi}期间将产生发货延迟，节后统一发货。给您带来的不便，敬请谅解。',
            ];
    }

    public static function getSmsAllName($sms_no){
        $list = self::getSmsAllList();
        if( isset($list[$sms_no]) ){
            return $list[$sms_no];
        }
        return '';
    }

}
