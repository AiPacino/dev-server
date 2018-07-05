<?php
/**
 * 		维修服务控制器
 */

// 加载 goods 模块中的 init_control
hd_core::load_class('init','goods');
class weixiu_service extends init_control{
    public function _initialize() {
        parent::_initialize();
        $this->weixiu_service = $this->load->service('weixiu_service/weixiu_service');
    }
    /**
     * 维修服务添加数据
     * @access public
     * @params array $params	维修单信息
     * [
     *     'service_name'   => '',        //【必须】string;订单编号
     *     'reason_name'   => '',          //【必须】int;用户id
     * ]
     * @return $data
     * @author Chenchun
     * @return mixed	false：失败； true: 成功；
     */
    public function create(){
        $params = $this->params;
        $data = filter_array($params,[
            'service_name' => 'required',
            'reason_name' => 'required',
        ]);
        if(count($data) != 2){return false;}
        $result = $this->weixiu_service->create($data);
        return $result;
    }

}