<?php
class log_table extends table
{
	protected function _after_find(&$result, $options) {
		$username = $this->load->table('admin_user')->getFieldById($result['user_id'],'username');
		$result['username'] = isset($username) ? $username : '--';
		$result['dateline_text'] = date('Y-m-d H:i:s', $result['starttime']);
		return $result;
	}
	protected function _after_select(&$result, $options) {
		foreach ($result as &$record) {
			$this->_after_find($record, $options);
		}
		return $result;
	}
    /**
     * 保存退货单
     * @params array	退货单信息
     * [
     *	   'user_id' => '',	        //【必须】int;执行用户id
     *	   'business_key' => '',    //【必须】int;执行行为者ip
     *	   'remark' => '',	    //【必须】string;日志备注
     *	   'url' => '',	    //【必须】string;操作URL
     *     'dateline' =>'',  //【必须】int;执行行为的时间
     * ]
     */
	public function create_log($data){
        $result =$this->add($data);
        if ( $result ) {
            return $result;
        } else {
            return false;
        }
    }
}