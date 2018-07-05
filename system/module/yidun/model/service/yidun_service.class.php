<?php
class yidun_service extends service {

    public function _initialize() {
        $this->table = $this->load->table('yidun/yidun');
    }

    /**
     * 创建蚁盾请求记录
     * @param array   $data	      【必选】
     * array(
        'sms_no',

     *      )
     * @author
     * @return mixed    false:创建失败；int：创建成功，返回debug编码
     *
     */
    public function create($data){
        $data = filter_array($data, [
            'event_id' => 'required',
            'event_code' => 'required',
            'decision' => 'required',
            'verifyId' => 'required',
            'verifyUri' => 'required',
            'score' => 'required',
            'strategies' => 'required',
            'level' => 'required',
            'user_name' => 'required',
            'user_id' => 'required',
            'mobile' => 'required',
            'email' => 'required',
            'ip' => 'required',
            'platform' => 'required',
            'user_agent' => 'required',
            'cert_no' => 'required',
            'address_id' => 'required',
            'create_time' => 'required',
        ]);

        $b = $this->table->add($data);
        if(!$b){
            return false;
        }
        return $b;
    }

}