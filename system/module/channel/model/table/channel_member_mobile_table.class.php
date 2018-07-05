<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/2 0002-下午 5:00
 * @copyright (c) 2017, Huishoubao
 */

class channel_member_mobile_table extends table
{

    protected $_validate = array(
        array('mobile','mobile','{channel/mobile_format}',table::VALUE_VALIDATE,'regex',table:: MODEL_BOTH),
        array('user_id', 'require', '{admin/username_not_exist}', table::MUST_VALIDATE),
    );

    /**
     * [绑定手机号]
     * @param array $data 数据
     * @return bool
     */
    public function bind($data) {
        $params = [];
        $mobile = str_replace('，', ',', $data['mobile']);
        $mobile_arr = explode(',', $mobile);
        $trans = $this->startTrans();
        if(!$trans) {
            $this->error = $this->getError();
            return false;
        }
        foreach ($mobile_arr as $item){
            $result = true;
            $params['mobile'] = trim($item);
            $params['user_id'] = $data['id'];
            $info = $this->where($params)->find();
            if(empty($info))
                $result = $this->update($params);
            if($result === false) {
                $this->rollback();
                $this->error = $this->getError();
                return false;
            }
        }
        $this->commit();
        return TRUE;
    }
}