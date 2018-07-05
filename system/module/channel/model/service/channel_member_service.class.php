<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2017/12/28 0028-下午 5:14
 * @copyright (c) 2017, Huishoubao
 */

class channel_member_service extends service
{

    const TYPE_STORE = 1;
    const TYPE_CHANNEL = 2;

    public $enum_type = [
        self::TYPE_STORE => '门店',
        self::TYPE_CHANNEL => '渠道'
    ];

    protected $sqlmap = array();

    public function _initialize() {
        $this->model = $this->load->table('channel_member');
        $this->member_mobile_model = $this->load->table('channel_member_mobile');
    }
    /**
     * [获取所有团队成员]
     * @param array $sqlmap 数据
     * @return array
     */
    public function getAll($sqlmap = array(), $options = []) {
        $this->sqlmap = array_merge($this->sqlmap, $sqlmap);
        if(empty($options['page'])) $options['page'] = 1;
        return $this->model->where($this->sqlmap)->page($options['page'])->order('id desc')->limit(20)->select();
    }

    public function get_lists($sqlmap = array(), $options = []){
        $users = $this->getAll($sqlmap, $options);
        $lists = array();
        foreach ($users AS $user) {
            $lists[] = array(
                'id' => $user['id'],
                'username' => $user['username'],
                'type' => $this->enum_type[$user['type']],
                'relation_name' => isset($user['relation_name']) ? $user['relation_name'] : '-',
                'login_time' => $user['login_time'],
                'login_num' => $user['login_num'],
                'status' => $user['status']
            );
        }
        return $lists;
    }
    /**
     * [更新团队]
     * @param array $data 数据
     * @param bool $valid 是否M验证
     * @return bool
     */
    public function save($data, $valid = FALSE) {
        $data=array_filter($data);
        $data['encrypt'] = random(10);
        if(!empty($data['password']))
            $data['password'] = md5($data['password'].$data['encrypt']);
        else
            unset($data['password']);
        if($valid == TRUE){
            $data = $this->model->create($data);
            $result = $this->model->add($data);
        }else{
            $result = $this->model->update($data);
        }
        if($result === false) {
            $this->error = $this->model->getError();
            return false;
        }
        return TRUE;
    }
    /**
     * [删除]
     * @param array $ids 主键id
     */
    public function delete($ids) {
        if(empty($ids)) {
            $this->error = lang('_param_error_');
            return false;
        }
        $_map = array();
        if(is_array($ids)) {
            $_map['id'] = array("IN", $ids);
        } else {
            $_map['id'] = $ids;
        }
        $result = $this->model->where($_map)->delete();
        if($result === false) {
            $this->error = $this->model->getError();
            return false;
        }
        return true;
    }
    /*修改*/
    public function setField($data, $sqlmap = array()){
        if(empty($data)){
            $this->error = lang('_param_error_');
            return false;
        }
        $result = $this->model->where($sqlmap)->save($data);
        if($result === false){
            $this->error = $this->model->getError();
            return false;
        }
        return $result;
    }

    /**
     * 条数
     * @param  [arra]   sql条件
     * @return [type]
     */
    public function count($sqlmap = array()){
        $result = $this->model->where($sqlmap)->count('id');
        if($result === false){
            $this->error = $this->model->getError();
            return false;
        }
        return $result;
    }

    /**
     * [change_status 改变状态]
     * @param  [int] $id [id]
     * @return [boolean]     [返回更改结果]
     */
    public function change_status($id){
        if((int)$id < 1){
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $data = array();
        $data['status']=array('exp',' 1-status ');
        $result = $this->model->where(array('id' => $id))->save($data);
        if(!$result){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    public function get_user(){
        $member = session('__MEMBER_INFO__');
        if( $member ){
            $member = $this->fetch_by_id($member['id']);
            $this->set_user($member);
        }
        return $member?$member:false;
    }

    public function set_user($info){
        session('__MEMBER_INFO__',$info);
    }

    public function login($params = [], $type){
        //验证参数
        $params = filter_array($params,[
            'mobile' => 'required|is_mobile',
            'sm_code' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);

        //手机号验证码登录
        if(isset($params['mobile']) && isset($params['sm_code'])){
            // 从 session 中获取 验证码
            $data = filter_array($_SESSION, [
                '_login_mobile_' => 'required|is_mobile',
                '_login_expiry_' => 'required',
            ]);

            // session中不存在手机和验证码，则返回错误
            if( count($data)!=2 ){
                $this->error = ['code'=>ApiStatus::CODE_40004, 'msg'=>'登录失败', 'subCode'=>ApiSubCode::Login_Error_Illegal, 'subMsg'=>'参数错误'];
                return false;
            }
            // 提交信息与session中的不匹配
            if( $params['mobile']!=$data['_login_mobile_'] ){
                $this->error = ['code'=>ApiStatus::CODE_40004, 'msg'=>'登录失败', 'subCode'=>ApiSubCode::Login_Error_Illegal, 'subMsg'=>'登录异常'];
                return false;
            }

            // 接口校验验证码
            $sms = new \zuji\sms\HsbSms();
            $b = $sms->verify_sm_code($params['mobile'],'SMS_113450943',$params['sm_code']);

            if( !$b ){
                $this->error = ['code'=>ApiStatus::CODE_40004, 'msg'=>'登录失败', 'subCode'=>ApiSubCode::Login_Error_Sm_code, 'subMsg'=>'验证码错误'];
                return false;
            }

            $member = config("DB_PREFIX") . 'channel_member';
            $where['mobile'] = $params['mobile'];
            $where['type'] = $type;
            $user = $this->member_mobile_model->field($member.'.id, username, email, type, encrypt, relation_id')->join($member . ' on ' . $member. '.id = user_id')->where($where)->find();
            if(!$user) {
                $this->error = ['code'=>ApiStatus::CODE_40004, 'msg'=>'登录失败', 'subCode'=>ApiSubCode::Login_Error_Illegal, 'subMsg'=>'手机号不存在'];
                return false;
            }
        }
        //用户名密码登录
        elseif (isset($params['username']) && isset($params['password'])){
            $where['username'] = $params['username'];
            $where['type'] = $type;
            $user = $this->fetch_by_condition($where);
            if(!$user) {
                $this->error = ['code'=>ApiStatus::CODE_40004, 'msg'=>'登录失败', 'subCode'=>ApiSubCode::Login_Error_Username, 'subMsg'=>lang('admin_user_not_exist','admin/language')];
                return false;
            }
            if($user['password'] !== md5($params['password'].$user['encrypt'])) {
                $this->error = ['code'=>ApiStatus::CODE_40004, 'msg'=>'登录失败', 'subCode'=>ApiSubCode::Login_Error_Password, 'subMsg'=>lang('password_checked_error','admin/language')];
                return false;
            }

        }else {
            $this->error = ['code'=>ApiStatus::CODE_20001, 'msg'=>'参数错误', 'subCode'=>ApiSubCode::Login_Error_Illegal, 'subMsg'=>''];
            return false;
        }

        $this->user = $user;
        return $this->_dologin($user['id']);
    }

    private function _dologin($id) {

        if( empty($id) ){
            return false;
        }
        $where['login_num']  = ['exp', 'login_num+1'];
        $where['login_ip']      = $_SERVER['REMOTE_ADDR'];
        $where['login_time']=time();
        $result = $this->model->where(array('id'=>$id))->save($where);
        if($result)
            return true;
        else
            return false;
    }

    public function fetch_by_condition($where) {
        return $this->model->where($where)->find();
    }

    public function fetch_by_id($id){
        return $this->model->where(array("id" => $id))->find();
    }

    /**
     * 修改密码
     */
    public function edit_password($params = []) {
        $member_model = $this->fetch_by_id($params['id']);
        if(!$member_model){
            $this->error = ['code'=>ApiStatus::CODE_50001, 'msg'=>'密码修改失败', 'subCode'=>ApiSubCode::User_Edit_Password_Error, 'subMsg'=>'用户不存在'];
            return false;
        }
        if($member_model['password'] != md5($params['old_password'].$member_model['encrypt'])) {
            $this->error = ['code'=>ApiStatus::CODE_50001, 'msg'=>'密码修改失败', 'subCode'=>ApiSubCode::User_Edit_Password_Error, 'subMsg'=>'原密码错误'];
            return false;
        }
        if($params['new_password'] != $params['verify_password']){
            $this->error = ['code'=>ApiStatus::CODE_50001, 'msg'=>'密码修改失败', 'subCode'=>ApiSubCode::User_Edit_Password_Error, 'subMsg'=>'密码不一致'];
            return false;
        }

        $new_password = md5($params['new_password'].$member_model['encrypt']);
        $result = $this->model->update(['id' => $params['id'], 'password' => $new_password]);
        if($result === false){
            $this->error = ['code'=>ApiStatus::CODE_50001, 'msg'=>'密码修改失败', 'subCode'=>ApiSubCode::User_Edit_Password_Error, 'subMsg'=>'更新数据失败'];
            return false;
        }
        return true;
    }
}