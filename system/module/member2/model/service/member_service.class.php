<?php

/** 会员服务层
 *   @author limin<limin@huishoubao.com.cn>
 */
class member_service extends service {

    protected $result;

    public function _initialize() {
        $this->model = $this->load->table('member2/member');
        $this->member_login = $this->load->table('member2/member_login');
    }
        
    /**
     * 根据用户的id或手机号，获取用户信息
     * @param array $data  查询条件：id 或者手机号，两者二选一
     * @param array    $where	【可选】
     * [
     *      'id' => '',	//【可选】int；用户ID
     *      'mobile'=>'',	//【可选】string；商品名称
     * ]
     * @return mixed	false：查询失败或用户不存在；array：用户基本信息
     * [
     *	    'id' => '',	    //【必须】int；用户ID
     *	    'mobile' => '', //【必须】string；手机号
     * ]
     */
    public function get_info($where)
    {
        // 参数过滤
        $where = filter_array($where, [
            'id'       => 'required|is_id',
            'mobile'  => 'required|is_mobile',
        ]);
        // 都没有通过过滤器（都被过滤掉了）
        if( count($where)==0 ){
            return false;
        }
		
	    $fields = 'id,username,password,encrypt,mobile,cert_no,realname,certified,face,certified_platform,credit,credit_time,islock,block,login_ip,login_num,order_remark,withholding_no';
	    $info = $this->model->where($where)->field($fields)->find();
		if( $info ){
			$info['certified_platform_name'] = zuji\certification\Certification::getPlatformName($info['certified_platform']);
			return $info;
		}
		return false;
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
    public function get_list($where=[],$additional=[]){
        $where = filter_array($where, [
            'user_id' => 'required',
            'register_time'=>'required',
            'login_time'=>'required',
        ]);
        if( count($where)==0 ){
            return [];
        }
        if(isset($where['user_id'])){
            if( is_string($where['user_id']) ){
                $where['user_id'] = explode(',', $where['user_id']);
            }
            if( count($where['user_id'])==1 ){
                $where['address_id'] = $where['address_id'][0];
            }
            if( count($where['user_id'])==0 ){
                return [];
            }
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }
        $additional['size'] = min( $additional['size'], 20 );

        return $this->model->get_list($where,$additional);
    }
    public function get_count($where=[]){
        return $this->model->get_count($where);
    }
    /**
     * 手机号注册
     * @return $user
     */
    public function register_info($data){
        $data =  filter_array($data, [
            'mobile' => 'required|is_mobile',
            'appid' => 'required|is_int',
        ]);
        if(empty($data['mobile'])){
	        set_error('注册用户失败，手机号错误');
            return false;
        }
		if( empty($data['appid']) ) {
	        set_error('注册用户失败，渠道号(appid)错误');
            return false;
		}
        $data['username']  = $data['mobile'];
        $data['login_ip']  = $_SERVER['REMOTE_ADDR'];
        $data['login_num'] = 0;
        $data['login_time'] = time();
        $data['register_time'] = time();
        //注册
        $id = $this->model->register($data);
        if($id){
            $this->member_login->create_login(['member_id'=>$id,'login_time'=>$data['login_time'],'login_ip'=>$data['login_ip']]);
            return $id;
        }
        return false;
    }
    /**
     * 更新登录信息
     * @return
     */
    public function update_login_info($where){
        $where = filter_array($where, [
            'id' => 'required|is_id'
        ]);
        if( empty($where['id']) ){
            return false;
        }
        $where['login_num']  = ['exp', 'login_num+1'];
        $where['login_ip']      = $_SERVER['REMOTE_ADDR'];
        $where['login_time']=time();
        $result = $this->model->update_table($where);
        if($result){
            $this->member_login->create_login(['member_id'=>$where['id'],'login_time'=>$where['login_time'],'login_ip'=>$where['login_ip']]);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 支付宝小程序认证后的注册
     * @param $data
     */
    public function mini_register($data){
        $data =  filter_array($data, [
            'mobile' => 'required|is_mobile',
            'appid' => 'required|is_int',
            'certified' => 'required|is_int',
            'certified_platform' => 'required|is_int',
            'face' => 'required|is_int',
            'risk' => 'required|is_int',
            'cert_no' => 'required',
            'realname' => 'required'
        ]);
        $data['username']  = $data['mobile'];
        $data['login_ip']  = $_SERVER['REMOTE_ADDR'];
        $data['login_num'] = 0;
        $data['credit_time'] = time();
        $data['login_time'] = time();
        $data['register_time'] = time();
        //注册
        $id = $this->model->register($data);
        if($id){
            $this->member_login->create_login(['member_id'=>$id,'login_time'=>$data['login_time'],'login_ip'=>$data['login_ip']]);
            return $id;
        }
        return false;
    }

    /**
     *
     * @param $user_id
     */
    public function bind_third_member($third_user_info, $user_id){
        // 支付宝授权
        if( $third_user_info['__third_platform__'] == 'ALIPAY' ){
            $member_alipay = $this->load->service('member2/member_alipay');
            // 先查询是否已经存在
            $_info = $member_alipay->get_info( $third_user_info['user_id'] );
            // 不存在时，才可以进行绑定
            if( !$_info ){
                // 保存第三方授权信息，关联本地用户
                $_id = $member_alipay->create($third_user_info,$user_id);
                if( $_id === false ){
                    return false;
                }
            }
        }
        // 情况当前会话绑定数据
        session('__THIRD_USER_INFO__',null);

        return true;
    }
}
