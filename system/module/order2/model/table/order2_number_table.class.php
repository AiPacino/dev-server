<?php
/**
 * 订单编号模型
 *
 * @outhor wang jinlin
 */
class order2_number_table extends table {

    /**
     * 获取当天最大编号值
     * @return int  0：
     */
    public function get_day_max_no($day) {
	$this->startTrans();
	$options = [
	    'lock' => true,
	];
	$r = $this->where(['day'=>$day])->field('max(number) as num')->limit(1)->find( $options );
	if($r['num']>0){
	    return intval($r['num']);
	}
	return 0;
    }
    
    public function create( $day, $num ){
	$b = $this->add([
	    'day' => $day,
	    'number' => $num,
	]);
	if( $b ){
	    return true;
	}
	return false;
    }
    
    /**
     * 
     * @param type $day
     * @param type $num
     */
    public function  increase( $day ){
	return $this->where(['day'=>$day])->save(['number'=>['exp','`number`+1']]);
    }

}