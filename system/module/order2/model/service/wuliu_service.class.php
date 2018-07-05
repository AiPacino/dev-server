<?php
use zuji\order\EvaluationStatus;
/**
 * 检测单生成服务
 * @access public
 * @author yaodongxu <yaodongxu@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 *
 */
class wuliu_service extends service {
    /**
     * @param string $wuliu_no 物流单号
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>
     */
    public function get_wuliu_info( $wuliu_no ) {
        $code  = strval($wuliu_no);
        $userip =  $_SERVER['REMOTE_ADDR'];
        $userip =  $userip!="127.0.0.1"?$userip:"223.104.3.197";


//        $url  =  \zuji\Config::Api_Logistics_Url. $code . '/' . $userip;
        $url = config("Api_Logistics_Url") . $code . '/' . $userip;
        //发起GET请求
        $result    = \zuji\Curl::get($url);
        $arrNum = json_decode($result,true);

        //验证返回结果
        if(!$arrNum){
            set_error('未查询到该物流单号物流信息');
            return false;
        }
        if($arrNum['ret'][0] != "SUCCESS::调用成功"){
            set_error('未查询到该物流单号物流信息');
            return false;
        }

        $statusList = $arrNum['data']['transitList'];
        $newarry    = array();

        for($i=count($statusList)-1;$i>=0;$i--)
        {
            $array['createTime']  = $statusList[$i]['time'];
            $array['message']     = $statusList[$i]['message'];
            $array['sectionIcon'] = $statusList[$i]['sectionIcon'];

            $newarry[]  =  $array;
        }

        $info['logisticsCode']  = $arrNum["data"]['mailNo'];
        $info['companyName']    = $arrNum["data"]['cpCompanyInfo']['companyName'];
        $info['companyImg']     = $arrNum["data"]['cpCompanyInfo']['iconUrl100x100'];
        $info['serviceTel']     = $arrNum["data"]['cpCompanyInfo']['serviceTel'];
        $info['logisticStatus'] = $arrNum["data"]['logisticStatusDesc'];

        $data['wuliu_info']['info']           = $info;
        $data['wuliu_info']['logStatusList']  = $newarry;

        return $data;
    }
}