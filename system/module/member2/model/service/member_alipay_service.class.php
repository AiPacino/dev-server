<?php

/** 
 * 支付宝用户服务
 * 
 */
class member_alipay_service extends service {

    public function _initialize() {
        $this->model = $this->load->table('member2/member_alipay');
    }
    
    public function create( $data,$member_id=0 ){
	if( $member_id>0 ){
	    $data['member_id'] = $member_id;
	}
	$id = $this->model->add($data);
	return $id;
    }
    
    /**
     * 根据支付宝用户ID，获取已授权信息
     * @param int $alipay_user_id   支付宝用户ID
     */
    public function get_info($alipay_user_id)
    {
        // 都没有通过过滤器（都被过滤掉了）
        if( $alipay_user_id<1 ){
            return false;
        }
        return $this->model->get_info($alipay_user_id);
    }

    /**
     * 根据查询条件，查询用户列表
     * @param array $where
     * [
     *	    'user_id' => '',	    【可选】mixed；用户ID；int：用户ID；string：用户ID集合，逗号分隔；array：用户ID数组
     * ]
     * @param array $additional
     * [
     *	    'page' => '1',
     *	    'size' => '20',
     *	    'orderby' => '',
     * ]
     */
    public function get_list($where,$additional){
        $where = filter_array($where, [
            'user_id' => 'required'
        ]);
        if( count($where)==0 ){
            return [];
        }
        if( is_string($where['user_id']) ){
            $where['user_id'] = explode(',', $where['user_id']);
        }
        if( count($where['user_id'])==1 ){
            $where['address_id'] = $where['address_id'][0];
        }
        if( count($where['user_id'])==0 ){
            return [];
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }
        $additional['size'] = min( $additional['size'], 20 );

        return $this->model->get_list($where,$additional);
    }
}
