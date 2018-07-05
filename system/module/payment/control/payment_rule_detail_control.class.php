<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/2/2 0002-下午 6:31
 * @copyright (c) 2017, Huishoubao
 */
hd_core::load_class('init', 'admin');
class payment_rule_detail_control extends init_control
{


    public function rule_popup(){

        $params = $_GET;

        $this->load->librarys('View')
            ->assign('params', $params)
            ->display('rule_detail_edit');
    }
}