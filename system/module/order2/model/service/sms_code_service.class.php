<?php

/**
 * 发送短信验证码
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */
class sms_code_service extends service {

    
    
    public function _initialize() {
    	//实例化数据层
    	$this->sms_code_table = $this->load->table('order2/sms_code');
    }


    /**
     * 创建验证码
     * @param array   $data	      【必选】
     * @return mixed  false:创建失败；int：创建成功
     *
     */
    public function create($data = array()) {
        $data = filter_array($data, [
            'mobile' => 'required',
            'create_time' => 'required',
        ]);
        return $this->sms_code_table->add($data);

    }

    /**
     * 查单条
     * @param array   $where	      【必选】 条件
     * @return array  result
     *
     */
    public function find($where = array()) {
        return $this->sms_code_table->where($where)->order('create_time desc')->find();
    }

    /**
     * 修改
     * @param array   $where	      【必选】 条件
     * @param array   $data	            【必选】 参数
     * @return mixed  false:创建失败；int：创建成功
     *
     */
    public function save($where, $data = array()) {

        return $this->sms_code_table->where($where)->save($data);

    }

}
