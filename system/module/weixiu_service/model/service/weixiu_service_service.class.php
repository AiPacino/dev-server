<?php
/**
 * 		维修服务服务层
 */
class weixiu_service_service extends  service {
    public function _initialize() {
        $this->weixiu_service_table = $this->load->table('weixiu_service/weixiu_service');
    }
    /**
     * 添加维修服务
     * @param array $data   【必选】维修服务名称
     * array(
     *	   'service_name' => '',    //【必须】string;维修服务名
     *     'reason_name'=>''            //【必须】string 维修原因
     * )
     * @return mixed	false：失败； int：维修原因ID
     * 当创建失败时返回false；当创建成功时返回维修原因ID
     * @author chenchun <chenchun@huishoubao.com.cn>
     */
    public function create($data){
        $data = filter_array($data,[
            'service_name' => 'required',
            'reason_name' => 'required',
        ]);
        if(count($data) != 2){return false;}
        $parent = array(
            'service_name' => $data['service_name'],
            'pid' => 0,
            'create_time'  => time(),
        );
        $where['service_name'] = $data['service_name'];
        $server = $this->weixiu_service_table->get_info($where);
        try {
            // 开启事务
            $this->weixiu_service_table->startTrans();
            if($server === false) {
                $pid = $this->weixiu_service_table->create_table($parent);
                if (!$pid) {
                    $this->weixiu_service_table->rollback();
                    return false;
                }
                $reason = array(
                    'pid' => $pid,
                    'reason_name' => $data['reason_name'],
                    'create_time' => time(),
                );
                $result = $this->weixiu_service_table->create_table($reason);
                if (!$result) {
                    $this->weixiu_service_table->rollback();
                    return false;
                }
            } else {
                $reason = array(
                    'pid' => $server['id'],
                    'reason_name' => $data['reason_name'],
                    'create_time' => time(),
                );
                $result = $this->weixiu_service_table->create_table($reason);
                if (!$result) {
                    $this->weixiu_service_table->rollback();
                    return false;
                }
            }
            // 提交事务
            $this->weixiu_service_table->commit();
            return true;
        } catch (\Exception $exc) {
            // 关闭事务
            $this->weixiu_service_table->rollback();
            return false;
        }
    }
    /**
     * 获取维修服务名信息
     * @return mixed	false：失败；
     * 成功时返回array()
     * @author chenchun <chenchun@huishoubao.com.cn>
     * array(
     *	   'id' =>   '',    //【必须】string;维修服务名
     *     'service_name'  =>   ''            //【必须】string 维修原因
     *     'pid'  =>    '',             //【必须】string 父类id
     *     'reason_name'  =>   '',      //【必须】string 维修原因
     *     'create_time'   =>   '',     //【必须】string 创建时间
     * )
     */
    public function get_parent_list(){
        $where['pid'] = 0;
        $result = $this->weixiu_service_table->get_list($where);
        return $result;
    }
    /**
     * 获取维修服务名信息
     * @return mixed	false：失败；
     * @param array $where   【必选】维修服务ID
     * 成功时返回array()
     * @author chenchun <chenchun@huishoubao.com.cn>
     * array(
     *	   'id' =>   '',    //【必须】string;维修服务名
     *     'service_name'  =>   ''            //【必须】string 维修原因
     *     'pid'  =>    '',             //【必须】string 父类id
     *     'reason_name'  =>   '',      //【必须】string 维修原因
     *     'create_time'   =>   '',     //【必须】string 创建时间
     * )
     */
    public function get_reason_list($pid){
        if(empty($pid)){return false;}
        $where['pid'] = $pid;
        $result = $this->weixiu_service_table->get_list($where);
        return $result;
    }
    /**
     * 修改维修服务单条
     * @return mixed	false：失败；
     * @param array $data   【必选】维修服务ID
     * 成功时返回array()
     * @author chenchun <chenchun@huishoubao.com.cn>
     * array(
     *	   'id' =>   '',    //【必须】string;维修服务名
     *     'service_name'  =>   ''            //【必须】string 维修原因
     *     'pid'  =>    '',             //【必须】string 父类id
     *     'reason_name'  =>   '',      //【必须】string 维修原因
     *     'create_time'   =>   '',     //【必须】string 创建时间
     * )
     */
    public function update_parent($data){
        $data = filter_array($data,[
            'id' => 'required|is_id',
            'service_name' => 'required',
        ]);
        if(count($data) != 2){return false;}
        $result = $this->weixiu_service_table->update_table($data);
        return $result;
    }
    /**
     * 修改维修服务单条
     * @return mixed	false：失败；
     * @param array $data   【必选】维修服务ID
     * 成功时返回array()
     * @author chenchun <chenchun@huishoubao.com.cn>
     * array(
     *	   'id' =>   '',    //【必须】string;维修服务名
     *     'service_name'  =>   ''            //【必须】string 维修原因
     *     'pid'  =>    '',             //【必须】string 父类id
     *     'reason_name'  =>   '',      //【必须】string 维修原因
     *     'create_time'   =>   '',     //【必须】string 创建时间
     * )
     */
    public function update_reason($data){
        $data = filter_array($data,[
            'id' => 'required|is_id',
            'reason_name' => 'required',
        ]);
        if(count($data) != 2){return false;}
        $result = $this->weixiu_service_table->update_table($data);
        return $result;
    }
}