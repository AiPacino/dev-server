<?php

namespace zuji\debug;
/**
 * 
 *
 * @author Administrator
 */
class Debug {
    
    private static function _get_debug_service(){
	static $service = null;
	if( !$service ){
	    $load = \hd_load::getInstance();
	    $service = $load->service('debug/debug');
	}
	return $service;
    }
    
    public static function error( $location_id, $subject, $data ){
	self::_get_debug_service()->create([
	    'location_id'=>$location_id,
	    'subject'=>$subject,
	    'data'=>$data
	]);
    }
    
}
