<?php
/**
 *  电子合同签署服务层
 *  @author limin <limin@huishoubao.com.cn>
 */
class contract_service extends service {
    /**
     * 租机电子合同签署
     * @param  $data [array]
     * [
     * 'order_no' =>''  //【必须】订单号
     * chengse' =>''  //【必须】品类
     * machine_no' =>''  //【必须】机型型号
     * imei' =>''  //【必须】IMEI号
     * zuqi' =>''  //【必须】租期
     * 'zujin' =>''  //【必须】租金
     * 'mianyajin' =>''  //【必须】免押金
     * 'yiwaixian' =>''  //【必须】意外险
     * 'email' =>''  //【可选】邮箱地址
     * 'user_id' =>''  //【必须】会员id
     * 'name' =>''  //【必须】姓名
     * 'id_cards'=>'' //【必须】身份证号
     * 'mobile'=>'' //【必须】手机号
     * 'address' =>''  //【必须】通讯地址
     * ]
     * @return [Array]         [boolean]
     */
    public function contract_sign($contract_id,$data){
        \zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '电子合同', $data);
        $data = filter_array($data, [
            'order_no' => 'required',
            'chengse' => 'required',
            'machine_no' => 'required',
            'imei' => 'required',
            'zuqi' => 'required',
            'zujin' => 'required',
            'mianyajin' => 'required',
            'yiwaixian' => 'required',
            'market_price'=>'required',
            'user_id' => 'required',
            'name' => 'required',
            'id_cards' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'delivery_time'=>'required'
        ]);
        if( count($data)<14 ){
            set_error('参数不完整！');
            return false;
        }
        //验证订单是否已生成合同
        $this->service = \hd_load::getInstance()->table("order2/order2_contract");
        $conract_info = $this->service->where(['order_no'=>$data['order_no']])->find();
        if($conract_info){
            return false;
        }
        //查询电子合同模板
        $this->contract = $this->load->table("contract/contract");
        $contract_info = $this->contract->where(['id'=>$contract_id])->find();
        if(!$contract_info){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '电子合同模板不存在', $this->contract->getlastsql());
            return false;
        }

        //请求参数
        $array = [
            //姓名
            'name' => $data['name'],
            //身份证号
            'id_cards' => $data['id_cards'],
            //手机号
            'mobile' => $data['mobile'],
            //邮箱(可选)
            //'email' => $data['email'],
            //合同模版ID
            'template_id' => $contract_info['template_id'],
            //合同编号
            'contract_id' => $data['order_no'],
            //文档标题
            'doc_title' => '用户租机协议',
            //交易号
            'transaction_id' => $data['order_no'],
            //签名关键字
            'sign_word' => '用户签字',
            'paramters' => '',
        ];
        //选择合同模板
        if($contract_id == 1){
            $array['paramters'] = $this->mobile($data);
        }
        elseif($contract_id == 2){
            $array['paramters'] = $this->uav($data);
        }
        $params = json_encode($array);
        //电子合同签署
        $url = config("Contract_Sign_Url");
        $json = zuji\Curl::post($url,$params,['Content-Type: application/json']);
        $result = json_decode($json,true);
        if($result['result']=="success" || $result['code'] == 1000){
            $save = [
                'order_no' => $data['order_no'],
                'user_id' => $data['user_id'],
                'template_id' => $array['template_id'],
                'contract_id' => $array['contract_id'],
                'status' => 0,
                'transaction_id' => $array['transaction_id'],
                'download_url' => $result['download_url'],
                'viewpdf_url' => $result['viewpdf_url'],
                'create_time' => time(),
            ];
            $contract_id = $this->service->add($save);
            if(!$contract_id){
                \zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '电子合同入库失败', $save);
            }
            return $result;
        }
        \zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '电子合同生成失败', ['input'=>json_decode($params,true),'result'=>$json]);
        return false;
    }
    //无人机模板请求参数
    function uav($data){
        $array = [
                //协议编号
                "{protocol_no}" => "".$data['order_no'],
                //承租方姓名
                "{user_name}" => $data['name'],
                //承租方身份证号码
                "{cert_no}" => "".$data['id_cards'],
                //承租方通讯地址
                "{address}" => $data['address'],
                //承租方联系方式
                "{mobile}" => "".$data['mobile'],
                //租金
                "{zujin}" => "".$data['zujin'],
                //租金大写
                "{dx_zujin}" => cny($data['zujin']),
                //免押金
                "{mianyajin}" => "".$data['mianyajin'],
                //免押金大写
                "{dx_mianyajin}" => cny($data['mianyajin']),
                //市场价
                "{market_price}" => $data['market_price'],
            ];
        return $array;
    }
    //手机模板请求参数
    public function mobile($data){
        $array = [
            //协议编号
            "{protocol_no}" => "".$data['order_no'],
            //承租方姓名
            "{user_name}" => $data['name'],
            //承租方身份证号码
            "{cert_no}" => "".$data['id_cards'],
            //承租方通讯地址
            "{address}" => $data['address'],
            //承租方联系方式
            "{mobile}" => "".$data['mobile'],
            //品类
            "{chengse}" => $data['chengse'],
            //订单号
            "{order_no}" => "".$data['order_no'],
            //机器型号
            "{machine_no}" => "".$data['machine_no'],
            //IMEI
            "{imei}" => "".$data['imei'],
            //租期
            "{zuqi}" => "".$data['zuqi'],
            //租金
            "{zujin}" => "".$data['zujin'],
            //租金大写
            "{dx_zujin}" => cny($data['zujin']),
            //免押金
            "{mianyajin}" => "".$data['mianyajin'],
            //免押金大写
            "{dx_mianyajin}" => cny($data['mianyajin']),
            //意外险
            "{yiwaixian}" => "".$data['yiwaixian'],
            //意外险
            "{delivery_time}" => $data['delivery_time'],
        ];
        return $array;
    }
}