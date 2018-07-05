<?php
namespace zuji;

/**
 * 时间
 * @author Administrator
 */
class Time {
    private $time = 0;
    private $year = 1970;
    private $month = 1;
    private $mday = 1;
    private $hours = 0;
    private $minutes = 0;
    private $seconds = 0;
    public function __construct( $time=null ){
	if( $time == null ){
	    $time = time();
	}
	if( $time>0 ){
	    $this->time = $time;
	    $info = getdate( $time );
	    $this->year = $info['year'];
	    $this->month = $info['mon'];
	    $this->mday = $info['mday'];
	    $this->hours = $info['hours'];
	    $this->minutes = $info['minutes'];
	    $this->seconds = $info['seconds'];
	}
    }
    public static function getTime($time=null){
	return new self( $time );
    }
    
    /**
     * 格式化时间
     * @param mixed $time   时间戳或Time对象
     * @param string $format  格式，默认：Y-m-d H:i:s
     * @return string	格式化时间字符串
     */
    public static function format( $time=null,$format='Y-m-d H:i:s' ){
	if(!$time){
	    $time = time();
	}elseif($time instanceof self){
	    $time = $time->toTimestamp();
	}
	return date($format,$time);
    }
    
    /**
     * 转字符串
     * @return string	格式化时间字符串
     */
    public function toString( ){
	return self::format($this);
    }
    
    /**
     * 转字符串
     * @return string	格式化时间字符串
     */
    public function __toString( ){
	return $this->toString();
    }
    
    
    /**
     * 获取时间戳
     * @return int  时间戳
     */
    public function toTimestamp(){
	return $this->time;
    }
    
    /**
     * 获取 当日 开始时间对象
     * @return Time  时间对象
     */
    public function getDayBegin( ){
	return new self(mktime(0, 0, 0, $this->month, $this->mday, $this->year));
    }
    /**
     * 获取 相对某日 当前时间对象
     * @param int $n	天数；取正整数
     * @return Time  时间对象
     */
    public function getOtherDay( $n ){
	return new self(mktime($this->hours, $this->minutes, $this->seconds, $this->month, $this->mday+$n, $this->year));
    }
    /**
     * 获取 相对某日 开始时间
     * @param int $n	天数；取正整数
     * @return Time  时间对象
     */
    public function getOtherDayBegin( $n ){
	return new self(mktime(0, 0, 0, $this->month, $this->mday+$n, $this->year));
    }
    
    /**
     * 获取 当月 当前时间对象
     * @return Time  时间对象
     */
    public function getMonthBegin( ){
	return new self(mktime(0, 0, 0, $this->month, 1, $this->year));
    }
    /**
     * 获取 相对某月 当前时间戳
     * @param int $n	天数；取正整数
     * @return Time  时间对象
     */
    public function getOtherMonth( $n ){
	return new self(mktime($this->hours, $this->minutes, $this->seconds, $this->month+$n, 1, $this->year));
    }
    /**
     * 获取 相对某月 开始时间
     * @param int $n	天数；取正整数
     * @return Time  时间对象
     */
    public function getOtherMonthBegin( $n ){
	return new self(mktime(0, 0, 0, $this->month+$n, 1, $this->year));
    }
    /**
     * 获取 当年 开始时间
     * @return Time  时间对象
     */
    public function getYearBegin( ){
	return new self(mktime(0, 0, 0, 1, 1, $this->year));
    }
    /**
     * 获取 相对某年 当前时间对象
     * @param int $n	相对年数；取正整数
     * @return Time  时间对象
     */
    public function getOtherYear( $n ){
	return new self(mktime($this->hours, $this->minutes, $this->seconds, 1, 1, $this->year+$n));
    }
    /**
     * 获取 相对某年 开始时间
     * @param int $n	天数；取正整数
     * @return Time  时间对象
     */
    public function getOtherYearBegin( $n ){
	return new self(mktime(0, 0, 0, 1, 1, $this->year+$n));
    }
    
}
