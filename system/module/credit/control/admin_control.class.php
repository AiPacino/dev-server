<?php


hd_core::load_class('init', 'admin');

class admin_control extends init_control
{
    public function _initialize() {
        parent::_initialize();

        $this->credit = $this->load->service('credit/credit');
    }

    /**
     * [index 信用]
     * @return [type] [description]
     */
    public function index()
    {
        $params = $_GET;
        $params['limit'] = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $info = $this->credit->getList($params);

        $this->load->librarys('View')
            ->assign('info', $info)
            ->display('index');
    }
}