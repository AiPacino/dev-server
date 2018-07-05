<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/3/21 0021-下午 4:57
 * @copyright (c) 2017, Huishoubao
 */
use oms\state\State;
class data_count_control extends api_control
{

    protected $yidun_score = [
        '0-0' => '0',
        '1-59' => '60以下',
        '60-69' => '60-69',
        '70-79' => '70-79',
        '80-89' => '80-89',
        '90-100' => '90-100'
    ];

    protected $zm_score = [
        '599-649' => '599-649',
        '650-699' => '650-699',
        '700-749' => '700-749',
        '750-799' => '750-799',
        '800-1000' => '800以上'
    ];


    public function get_yidun_branch(){


    }

    public function get_order_status_list(){
        $this->order = $this->load->table('order2/order2');
        $list = $this->order->group('status')->getField('status', true);
        $data = [];
        foreach ($list as $item){
            $data[$item] = State::getStatusAllName($item);
        }
        api_resopnse( $data, ApiStatus::CODE_0  );
    }

    public function get_chengse_list(){
        $chengse = array('100'=>'全新','99'=>'99成新','95'=>'95成新','90'=>'9成新','80'=>'8成新','70'=>'7成新',);
        $this->order = $this->load->table('order2/order2');
        $list = $this->order->group('chengse')->getField('chengse', true);
        $data = [];
        foreach ($list as $item){
            $data[$item] = $chengse[$item];
        }
        api_resopnse( $data, ApiStatus::CODE_0  );

    }
}