<?php

class auth_code_table extends table {

    private  $filed = [
        'code',
        'token',
        'appid',
        'username',
        'create_time',
        'status',
    ];
    /**
     * 查询用户基本
     * @param array    $where	【可选】
     * [
     *      'id' => '',	//【可选】int；用户ID
     *      'mobile'=>'',	//【可选】string；商品名称
     * ]
     * @return mixed	false：查询失败或用户不存在；array：用户基本信息
     * [
     * 	    'id' => '',	    //【必须】int；用户ID
     * 	    'mobile' => '', //【必须】string；手机号
     * 	    'id' => '',	//【必须】int；用户ID
     * ]
     */
    public function get_info(array $where,$fields="") {
        $fields = $fields?$fields:$this->field;
	    $rs = $this->where($where)->field($fields)->find();
	    return $rs ? $rs : false;
    }
    /*新增
    * @param array    $data	【必选】
    * */
    public function insert($data){
        return $this->add($data);
    }
    /**
     * 更新
     * @return bolean true 成功 false失败
     */
    public function update_table($data){
        $result = $this->update($data);
        if($result)
            return true;
        else
            return false;
    }
}
