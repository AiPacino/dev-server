<?php

/**
 * 设备损耗列表服务层
 * @access public （访问修饰符）
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class sunhao_service extends service {


    public function get_lists($type){

        return  array(

                        array(
                            'id'    => "1001",
                            'title' => "设备损耗一"
                        ),
                        array(
                            'id'    => "1002",
                            'title' => "设备损耗二"
                        ),
                        array(
                            'id'    => "1003",
                            'title' => "设备损耗三"
                        ),
                        array(
                            'id'    => "1004",
                            'title' => "设备损耗四"
                        ),
                );
        
    }
}
