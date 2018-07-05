<?php

/**
 * 检测单状态
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\order;

/**
 * EvaluationStatus （平台）收货单状态
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class EvaluationStatus {
    
    /**
     * @var int 无效状态（为订单表的状态默认值设计）
     * 【注意：】绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const EvaluationInvalid = 0;
    
    /**
     * @var int 检测单创建状态（预留）【暂时未使用】
     */
    const EvaluationCreated = 1;
    
    /**
     * @var int 待检测（生效状态）（收货流程已经结束，开始进入检测流程）【起点】
     */
    const EvaluationWaiting = 2;
    
    /**
     * @var int 检测中（预留，对接检测平台使用）【中间状态】
     */
    const EvaluationUnderway = 3;
    
    /**
     * @var int （对接平台的）检测完成（检测员录入或检测系统返回检测结果）【中间状态】
     */
    const EvaluationFinished = 4;
    
    /**
     * @var int 检测状态合格【终点】
     */
    const EvaluationQualified = 5;
    /**
     * @var int 检测状态不合格【终点】
     */
    const EvaluationUnqualified =6;
    
    
    /**
     * 获取检测状态列表
     * @return array	检测状态列表
     */
    public static function getStatusList(){
        return [
            self::EvaluationCreated => '检测单创建',
            self::EvaluationWaiting => '待检测',
            self::EvaluationUnderway => '检测中',
            self::EvaluationFinished => '检测完成',
            self::EvaluationQualified => '检测合格',
            self::EvaluationUnqualified => '检测不合格',
        ];
    }
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    /**
     * 校验状态值是否正确
     * @param int   $status
     * @return boolean
     */
    public static function verifyStatus( $status ){
        return array_key_exists($status,self::getStatusList());
    }
    
    //-+----------------------------------------------------------------------
    // | 检测结果分类
    //-+----------------------------------------------------------------------
    /**
     * @var int 待检测（为记录创建时的默认值设计）
     */
    const ResultInvalid = 0;
    /**
     * @var int 检测结果 合格
     */
    const ResultQualified = 1;
    /**
     * @var int 检测结果 不合格
     */
    const ResultUnqualified = 2;
    
    /**
     * 获取检测结果分类列表
     * @return array	检测结果分类列表
     */
    public function getResultList(){
	return [
	    self::ResultInvalid => '待检测',
	    self::ResultQualified => '合格',
	    self::ResultUnqualified => '不合格',
	];
    }
    public static function getResultName( $resultStatus ){
        $list = self::getResultList();
        if( isset($list[$resultStatus]) ){
            return $list[$resultStatus];
        }
        return '--';
    }
    /**
     * 验证检测结果
     * @param int   $result
     * @return boolean
     */
    public static function verifyResult( $result ){
        return array_key_exists($result,self::getResultList());
    }
    
    
    //-+----------------------------------------------------------------------
    // | 检测合格的分类
    //-+----------------------------------------------------------------------
    /**
     * @var int 合格分类 优品
     */
    const QualifiedExcellent = 1;
    /**
     * @var int 合格分类 良品
     */
    const QualifiedGood = 2;
    /**
     * @var int 合格分类 其他
     */
    const QualifiedOther = 127;
    
    /**
     * 获取检测合格的分类列表
     * @return array	检测合格的分类列表
     */
    public function getQualifiedList(){
	return [
	    self::QualifiedExcellent => '优品',
	    self::QualifiedGood => '良品',
	    self::QualifiedOther => '其他',
	];
    }
    /**
     * 验证检测合格的级别
     * @param int   $v
     * @return boolean
     */
    public static function verifyQualified( $qualified ){
        return array_key_exists($qualified,self::getQualifiedList());
    }
    
    //-+----------------------------------------------------------------------
    // | 检测不合格的处理结果（异常处理的结果）
    //-+----------------------------------------------------------------------
    /**
     * @var int 初始化 未处理
     */
    const UnqualifiedInvalid =0;
    
    /**
     * @var int 接受退货 (入库)
     */
    const UnqualifiedAccepted = 1;
    /**
     * @var int 寄回用户并买断(退回)
     */
    const UnqualifiedBuyout = 2;
    /**
     * @var int 【暂时不考虑】赔付入库（入库，用户需要支付赔损费）（新发起一个 赔付订单）
     */
    const UnqualifiedPayfor = 3;
    /**
     * @var int 寄回用户使用(退回)
     */
    const UnqualifiedGoUse = 4;
    /**
     * @var int 换货
     */
    const UnqualifiedExchange=5;
    
    public static function getUnqualifiedList(){
	return [
	    self::UnqualifiedInvalid => '未处理',
	    self::UnqualifiedAccepted => '接受退货',
	    self::UnqualifiedBuyout => '寄回用户并买断',
	    self::UnqualifiedPayfor => '赔付入库（支付赔损费）',
	    self::UnqualifiedGoUse => '寄回用户使用',
	    self::UnqualifiedExchange=>'用户换货,为用户寄回',
	   
	];
    }
    public static function getUnqualifiedName( $unqualified ){
        $list = self::getUnqualifiedList();
        if( isset($list[$unqualified]) ){
            return $list[$unqualified];
        }
        return '';
    }
    /**
     * 验证检测异常的处理结果
     * @param int $v
     * @return boolean
     */
    public static function verifyUnqualified( $unqualified ){
        return array_key_exists($unqualified,self::getUnqualifiedList());
    }
    
}
