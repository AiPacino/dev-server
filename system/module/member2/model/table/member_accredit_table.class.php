<?php
class member_accredit_table extends table
{
    //查询一条支付宝授权信息
    public function get_info($user_id) {
        $fields = 'id,user_id,avatar,province,city,nick_name,is_student_certified,user_type,user_status,is_certified,gender';
        $rs = $this->where(array('user_id"=>$user_id'))->field($fields)->order("id desc")->find();
        return $rs ? $rs : false;
    }
    //添加用户支付宝授权信息
    public function add_accredit(array $data){
         return $this->add($data);
    }
    //修改用户支付宝授权信息
    public function set_accredit($id,array $data){
        return $this->where(array("id"=>$id))->update($data);
    }

}