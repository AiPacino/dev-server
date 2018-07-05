<?php
namespace zuji\order;

//有关下拉框的项目
class Lists{

    //订单标识下拉框
    public static function getOrderBeizhuList(){
        return [
            '1'  => '已回访',
            '2'  => '已联系',
            '3'  => '无法联系',
        ];
    }

    public static function getOrderBeizhuName($beizhu){
        $list = self::getOrderBeizhuList();
        if( isset($list[$beizhu]) ){
            return $list[$beizhu];
        }
        return '';
    }

    //蚁盾风控等级
    public static function getOrderYidunList(){
        return [
            'accept'  => '无风险',
            'reject'  => '高风险',
            'validate'  => '低风险',
        ];
    }

    public static function getOrderYidunName($decision){
        $list = self::getOrderYidunList();
        if( isset($list[$decision]) ){
            return $list[$decision];
        }
        return '';
    }
	
	/**
	 * 蚁盾分数第零级别：未获取到蚁盾分数
	 */
	const YIDUN_SCORE_LEVEL_0 = 0;
	/**
	 * 蚁盾分数第一级别：无风险【蚁盾分数低于60分】
	 */
	const YIDUN_SCORE_LEVEL_1 = 1;
	/**
	 * 蚁盾分数第一级别：低风险【蚁盾分数[60,80)】
	 */
	const YIDUN_SCORE_LEVEL_2 = 2;
	/**
	 * 蚁盾分数第一级别：高风险【蚁盾分数高于80分】
	 */
	const YIDUN_SCORE_LEVEL_3 = 3;
	
	/**
	 * 蚁盾风控（级别=》描述）列表【根据分数级别获取】
	 */
	public static function getYidunScoreLevelDescList() {
		return [
			self::YIDUN_SCORE_LEVEL_0 => '',
			self::YIDUN_SCORE_LEVEL_1 => '无风险',
			self::YIDUN_SCORE_LEVEL_2 => '低风险',
			self::YIDUN_SCORE_LEVEL_3 => '高风险',
		];
	}
	//蚁盾风控（级别=》颜色）列表【根据分数级别获取】
	public static function getYidunScoreLevelColorList() {
		return [
			self::YIDUN_SCORE_LEVEL_0 => '',
			self::YIDUN_SCORE_LEVEL_1 => '#289526',
			self::YIDUN_SCORE_LEVEL_2 => '#e09832',
			self::YIDUN_SCORE_LEVEL_3 => '#c13d4a',
		];
	}
	/**
	 * 蚁盾分数获取风控级别
	 */
	public static function getYidunScoreLevel($yidun_score) {
		if( $yidun_score === '' ) {
			return self::YIDUN_SCORE_LEVEL_0;
		}
		$yidun_score = floatval($yidun_score);
		if($yidun_score<60){
			return self::YIDUN_SCORE_LEVEL_1;
		}elseif($yidun_score>=60 && $yidun_score<80){
			return self::YIDUN_SCORE_LEVEL_2;
		} else {
			return self::YIDUN_SCORE_LEVEL_3;
		}
	}
	/**
	 * 根据蚁盾分数获取蚁盾风控描述
	 */
	public static function getYidunScoreLevelDesc($yidun_score) {
        $yidun_desc_list = self::getYidunScoreLevelDescList();
		$yidun_score_level = self::getYidunScoreLevel($yidun_score);
        if( isset($yidun_desc_list[$yidun_score_level]) ){
            return $yidun_desc_list[$yidun_score_level];
        }
        return '';
	}
	/**
	 * 根据蚁盾分数获取蚁盾分数值颜色设置
	 */
	public static function getYidunScoreLevelColor($yidun_score) {
        $yidun_color_list = self::getYidunScoreLevelColorList();
		$yidun_score_level = self::getYidunScoreLevel($yidun_score);
        if( isset($yidun_color_list[$yidun_score_level]) ){
            return $yidun_color_list[$yidun_score_level];
        }
        return '';
	}
	
	/**
	 * 蚁盾描述第零级别：未获取到蚁盾描述
	 */
	const YIDUN_DECISION_LEVEL_0 = 0;
	/**
	 * 蚁盾描述第一级别：无风险【蚁盾描述accept】
	 */
	const YIDUN_DECISION_LEVEL_1 = 1;
	/**
	 * 蚁盾描述第二级别：中风险【蚁盾描述validate】
	 */
	const YIDUN_DECISION_LEVEL_2 = 2;
	/**
	 * 蚁盾描述第一级别：高风险【蚁盾描述reject】
	 */
	const YIDUN_DECISION_LEVEL_3 = 3;
	
	/**
	 * 蚁盾风控（级别=》描述）列表【根据描述级别获取】
	 */
	public static function getYidunDecisionLevelDescList() {
		return [
			self::YIDUN_DECISION_LEVEL_0 => '',
			self::YIDUN_DECISION_LEVEL_1 => '无风险',
			self::YIDUN_DECISION_LEVEL_2 => '低风险',
			self::YIDUN_DECISION_LEVEL_3 => '高风险',
		];
	}
	//蚁盾风控（级别=》颜色）列表【根据描述级别获取】
	public static function getYidunDecisionLevelColorList() {
		return [
			self::YIDUN_DECISION_LEVEL_0 => '',
			self::YIDUN_DECISION_LEVEL_1 => '#289526',
			self::YIDUN_DECISION_LEVEL_2 => '#e09832',
			self::YIDUN_DECISION_LEVEL_3 => '#c13d4a',
		];
	}
	/**
	 * 蚁盾描述获取风控级别
	 */
	public static function getYidunDecisionLevel($yidun_decision) {
		if( $yidun_score === '' ) {
			return self::YIDUN_DECISION_LEVEL_0;
		}
		$yidun_decision = strval($yidun_decision);
		if($yidun_decision==\oms\order_creater\YidunComponnet::RISK_ACCEPT){
			return self::YIDUN_DECISION_LEVEL_1;
		}elseif($yidun_decision==\oms\order_creater\YidunComponnet::RISK_REJECT){
			return self::YIDUN_DECISION_LEVEL_3;
		} else {
			return self::YIDUN_DECISION_LEVEL_2;
		}
	}
	/**
	 * 根据描述分数获取蚁盾风控描述
	 */
	public static function getYidunDecisionLevelDesc($yidun_decision) {
        $yidun_desc_list = self::getYidunDecisionLevelDescList();
		$yidun_decision_level = self::getYidunDecisionLevel($yidun_decision);
        if( isset($yidun_desc_list[$yidun_decision_level]) ){
            return $yidun_desc_list[$yidun_decision_level];
        }
        return '';
	}
	/**
	 * 根据蚁盾描述获取蚁盾分数值颜色设置
	 */
	public static function getYidunDecisionLevelColor($yidun_decision) {
        $yidun_color_list = self::getYidunDecisionLevelColorList();
		$yidun_decision_level = self::getYidunDecisionLevel($yidun_decision);
        if( isset($yidun_color_list[$yidun_decision_level]) ){
            return $yidun_color_list[$yidun_decision_level];
        }
        return '';
	}
}

