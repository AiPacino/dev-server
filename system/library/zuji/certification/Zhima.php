<?php
/**
 *
 */

namespace zuji\certification;


use alipay\ZhimaMini;

class Zhima extends Certification {

    private $credit_max = 950;
    private $credit_min = 350;


    //-+-----------------------------------------------------------
    // | 注意：
    // | 芝麻认证平台的信用值的取值范围[350,950]，所以不需要做特殊映射，
    // | 直接返回原值
    //-+-----------------------------------------------------------

    /**
     * @param $credit_decoded
     * @return mixed
     */
    public static function creditEncode($credit_decoded){
        return $credit_decoded;
    }
    public static function creditDecode($credit_encoded){
        return $credit_encoded;
    }


    /**
     * 判断是否满足免押条件
     * @param $credit
     * @return bool     true：满足免押条件；false：不满足免押条件
     */
    public static function isFreeYajin( $credit ){
        if( $credit>700 ){
            return true;
        }
        false;
    }

    /**
     * 获取认证订单内容
     * @param type $order_no
     * @return mixed
     */
    public function getOrderInfo( $order_no, $member_id ){
	
		$flag = true;
		$msg = '';
		$trade_no = '';
		
		$load = \hd_load::getInstance();

		$this->certification_alipay = $load->service('member2/certification_alipay');
		// 查询本地是否存在 芝麻订单编号
		$cert_info = $this->certification_alipay->get_info_by_order_no( $order_no );
		// 存在+请求流水存在+创建时间为当天
		if( $cert_info && $cert_info['trade_no'] && $cert_info['create_time'] &&date('Y-m-d')==substr($cert_info['create_time'],0,10) ){
			$trade_no = $cert_info['trade_no'];// 
		}else{
			$trade_no = \zuji\Business::create_business_no();//
		}
		
//	// 直接调用芝麻认证接口
	// 实例化 芝麻认证查询接口
	try {
	    $orderQuery = new \alipay\ZhimaOrderQuery('300001198');
	    // 查询芝麻认证结果
	    $data = $orderQuery->getOrderInfo($order_no, $trade_no );
	    //\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]信用套餐接口返回值',$data );
	    // 接口协议有问题...
	    if( $data==false ){
			$flag = false;
			$msg = get_error();
	    }
		// 租机请求流水号
		$data['trade_no'] = $trade_no;
	    
	} catch (\Exception $exc) {
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]信用套餐接口异常',[
			'error'=>$exc->getMessage(),
			'file' => $exc->getFile(),
			'line' => $exc->getLine(),
		] );
		$flag = false;
		$msg = '[芝麻认证]信用套餐接口异常';
	}
	
//	// 深圳接口
//	// 查询认证订单的详细信息（用户认证的信息）
//	$url = 'http://www.huishoubao.com/zm/get_contract_fields';
//	$params = '{
//	    "head":{
//		"interface":"getuserinfo",
//		"msgtype":"request",
//		"remark":"",
//		"version":"0.01"
//	    },
//	    "params":{
//		"channel":"3",
//		"orderno":"'.$order_no.'"
//	    }
//	}';
//	$str = \zuji\Curl::post($url, $params);
//	$return = json_decode($str,true);
//	if( !$return ){
//	    // debug
//	    \zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '认证结果查询接口异常',[
//		'url' => $url,
//		'request' => json_decode($params,true),
//		'response' => $str,
//	    ] );
//	    set_error('认证结果查询接口异常');
//	    //api_resopnse([],  ApiStatus::CODE_50005, '认证失败',  ApiSubCode::Certivication_Failed,'认证失败');
//	    return false;
//	}
//	if( !isset($return['ret']) || $return['ret']!=0 ){
//	    $flag = false;
//	    $msg .= 'ret非0；';
//	    //echo '获取认证信息失败，跳转失败页面'; exit;
//	}
//	if( !isset($return['body']['data']) ){
//	    $flag = false;
//	    $msg .= 'data不存在；';
//	    //echo '获取认证信息接口返回值错误，跳转失败页面'; exit;
//	}
//	$data = $return['body']['data'];
//	if( !isset($data['zm_score']) ){
//	    $flag = false;
//	    $msg .= 'zm_score不存在；';
//	    //echo '获取认证信息接口返回值错误，跳转失败页面'; exit;
//	}
	
	$member_table = $load->table('member2/member');
	// 接口协议有问题... 
	if( $flag==false ){
		// 认证失败，清除用户表的认证信息（只更新 certified 和 face 两个字段）
		$b = $member_table->where(['id'=>$member_id])->save([
			'certified' => 0,
			'face' => 0,
			'credit_time' => time(),
		]);
	    set_error($msg);
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '认证结果查询接口异常：'.$msg,[
		'url' => $url,
		'request' => json_decode($params,true),
		'response' => $return,
	    ] );
	    //api_resopnse([],  ApiStatus::CODE_50005, '认证失败',  ApiSubCode::Certivication_Failed,'认证失败');
	    return false;
	}else{
	    //认证成功，判断该支付宝用户号是否被租机的签过代扣协议
        /*$other_member = $this->certification_alipay->check_cert_other($data['user_id'], $member_id);
        if($other_member){
            $withholding_no_list = $member_table->where(['id' => ['in', $other_member]])->getField('withholding_no', true);
            if(!empty($withholding_no_list)){
                foreach ($withholding_no_list as $item){
                    if(!empty($item)){
                        \zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]该支付宝号已被其他用户签过代扣协议。',[] );
                        return false;
                    }
                }
            }
        }*/

        $member_info = $member_table->find($member_id);
        if(!empty($member_info['cert_no']) && $member_info['cert_no'] != $data['cert_no']){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]改手机号被其他用户认证过，不能重新认证。',$data );
            return false;
        }

		// 更新认证信息
		$b = $member_table->where(['id'=>$member_id])->save([
			'certified' => 1,
			'credit' => $data['zm_score'],
			'face' => $data['zm_face']=='Y'?1:0,
			'risk' => $data['zm_risk']=='Y'?1:0,
			'cert_no' => $data['cert_no'],
			'realname' => $data['name'],
			'credit_time' => time(),
		]);
	}
	// 人脸识别标志
	$data['zm_face'] = $data['zm_face']=='Y'?1:0;
	// 芝麻风控产品集联合结果
	$data['zm_risk'] = $data['zm_risk']=='Y'?1:0;
	// 当前用户标识
	$data['member_id'] = $member_id;
	$data['order_no'] =$order_no;
	
	// 保存用户认证信息
	$id = $this->certification_alipay->create($data);
	$data['id'] = $id;
	//\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '保存认证结果log',$data );
	if( !$id ){
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]'. get_error(),$data );
	    //api_resopnse([],  ApiStatus::CODE_50005, '认证失败',  ApiSubCode::Certivication_Failed,'认证失败');
	    return false;
	    //echo '认证信息保存错误，跳转失败页面： '.  get_error(); exit;
	}
	return $data;
    }

}