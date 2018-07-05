<?php

/** 授权验证码服务层
 *   @author limin<limin@huishoubao.com.cn>
 */
class auth_code_service extends service {

    protected $result;

    public function _initialize() {
        $this->model = $this->load->table('member2/auth_code');
    }
        
    /**
     * 根据code获取单条信息
     * @param array    $where	【必选】
     * [
     *      'code' => '',	//【必选】string；code
     * ]
     * @return mixed	false：数据不存在；array：基本信息
     * [
     *	    'code' => '',	    //【必须】string；code
     *	    'token' => '', //【必须】string；token
     *      'appid' => '', //【必须】int；授权appid
     *      'username' => '', //【必须】string；用户名称
     *      'system' => '', //【必须】string；授权系统
     *      'create_time' => '', //【必须】int；创建时间
     *      'status' => '', //【必须】int；状态
     * ]
     */
    public function get_info($where)
    {
        // 参数过滤
        $where = filter_array($where, [
            'code'   => 'required',
        ]);
        // 都没有通过过滤器（都被过滤掉了）
        if( empty($where['code']) ){
            return false;
        }
        $info = $this->model->get_info($where);
        if( $info ){
            return $info;
        }
        return false;
    }
    /**
     * 新增一条数据
     * @param array    $datqa	【必选】
     * [
     *	    'code' => '',	 //【必须】string；code
     *	    'token' => '', //【必须】string；token
     *      'appid' => '', //【必须】int；授权appid
     *      'username' => '', //【必须】string；用户名称
     *      'system' => '', //【必须】string；授权系统
     *      'create_time' => '', //【必须】int；创建时间
     *      'status' => '', //【必须】int；状态
     * ]
     * @return mixed	true：成功；false：失败
     */
    public function create($data){
        $data = filter_array($data, [
            'code' => 'required',
            'token' => 'required',
            'appid' => 'required',
            'username' => 'required',
            'create_time'=> 'required',
            'status'=> 'required'
        ]);
        if(count($data)!=6){
            return false;
        }
        $result = $this->model->add($data);
        if($result){
            return true;
        }
        return false;

    }
    /**
     * 更新状态
     * @param array    $where	【必选】
     * [
     *      'code' => '',	//【必选】string；code
     * ]
     * @return mixed	true：成功；false：失败
     */
    public function update_status($where){
        $where = filter_array($where, [
            'code' => 'required',
            'status'=> 'required'
        ]);
        if( empty($where['code']) ){
            return false;
        }
        if( empty($where['status']) ){
            $where['status'] = 0;
        }
        $result = $this->model->update_table($where);
        if($result){
            return true;
        }
        return false;
    }
}
