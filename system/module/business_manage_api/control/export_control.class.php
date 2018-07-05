<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/2/1 0001-下午 4:57
 * @copyright (c) 2017, Huishoubao
 */

class export_control extends control
{
    protected $member = array();
    protected $member_service = '';

    public function _initialize(){
        parent::_initialize();
        echo session_id();

        $b = session_id($_GET['token']);
        var_dump( $b );
        $b = session_start();
        var_dump( $b );

        echo session_id();
        var_dump( $_SESSION );
        exit;
        $this->service = $this->load->service('statistics/count_channel');
        $this->member_service = $this->load->service('channel/channel_member');
        $this->member = $this->member_service->get_user();
        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
        /*if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            showmessage("权限拒绝，请登录","null",0);
        }*/
    }

    public function appid_export(){

        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
        if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            echo '当前用户权限拒绝导出';
            exit;
        }

        require_once APP_PATH . 'library/PHPExcel/PHPExcel.php';
        $objPHPExcel = new PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");


        $params = $_GET;
        if(empty($params['appid'])){
            showmessage("参数错误！","null",0);
        }

        // 查询条件
        $where = [];
        if($params['begin_time']!='' ){
            $where['begin_time'] = strtotime($params['begin_time']);
        }
        if( $params['end_time']!='' ){
            $where['end_time'] = strtotime($params['end_time']);
        }
        if($params['appid']>'0' ){
            $where['appid'] = intval($params['appid']);
        }

        // 结束时间（可选），默认为为当前时间
        if( !isset($where['end_time']) ){
            $where['end_time'] = time();
        }

        if( isset($where['begin_time'])){
            if( $where['begin_time']>$where['end_time'] ){
                return false;
            }
            $where['unix_timestamp(dateline)'] = ['between',[$where['begin_time'], $where['end_time']]];
        }else{
            $where['unix_timestamp(dateline)'] = ['LT',$where['end_time']];
        }

        unset($where['begin_time']);
        unset($where['end_time']);

        $options['order'] = 'id desc';
        $result = $this->service->arrListByPage(0, 0, $where, $options);

        $appid_service = $this->load->service('channel/channel_appid');
        $appid_info = $appid_service->get_info($params['appid']);

        $sheet_title = $appid_info['appid']['name'].'-'.date('Y-m-d');

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '成交单数')
            ->setCellValue('C1', '下单单数')
            ->setCellValue('D1', '当日用户数')
            ->setCellValue('E1', '手机总租金');

        $objPHPExcel->getActiveSheet()->setTitle($sheet_title);

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        $objPHPExcel->getActiveSheet()->freezePane('A2');
        $i = 2;
        foreach($result['rows'] as $data){
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $data['dateline'])->getStyle('A'.$i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $data['complete_order_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $data['order_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $data['pv']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $data['service_expire_num']);
            $i++ ;
        }

        $filename = $sheet_title;

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function channel_export(){
        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------
        if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            echo '当前用户权限拒绝导出';
            exit;
        }

        require_once APP_PATH . 'library/PHPExcel/PHPExcel.php';
        $objPHPExcel = new PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $channel_id = api_request()->getAppid();
        $channel_info = $this->load->service('channel/channel')->get_info($channel_id);
        $appid_list = $this->load->service('channel/channel_appid')->get_list(['channel_id' => $channel_id, 'status' => 1]);
        $appid_id_arr = array_column($appid_list, 'id');
        $where['appid'] = ['IN', $appid_id_arr];

        $options['field'] = 'sum(order_num) as order_num, sum(complete_order_num) as complete_order_num, sum(pv) as pv, sum(all_amount) as all_amount, sum(service_expire_num) as service_expire_num';
        $options['order'] = 'id desc';
        $options['group'] = 'appid';
        $result = $this->service->arrListByPage(0, 0, $where, $options);

        $sheet_title = $channel_info['name'].'-'.date('Y-m-d');

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '日期')
            ->setCellValue('B1', '成交单数')
            ->setCellValue('C1', '下单单数')
            ->setCellValue('D1', '当日用户数')
            ->setCellValue('E1', '手机总租金');

        $objPHPExcel->getActiveSheet()->setTitle($sheet_title);

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        $objPHPExcel->getActiveSheet()->freezePane('A2');
        $i = 2;
        foreach($result['rows'] as $data){
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $data['dateline'])->getStyle('A'.$i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $data['complete_order_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $data['order_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $data['pv']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $data['service_expire_num']);
            $i++ ;
        }

        $filename = $sheet_title;

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function orderList_export(){
        //-+--------------------------------------------------------------------
        // | 用户校验
        //-+--------------------------------------------------------------------+
        print_r($this->member);
        if( !$this->member || $this->member['id']<1 ){// 游客时，不允许操作
            echo '当前用户权限拒绝导出';
            exit;
        }
        // 不限制超时时间
        @set_time_limit(0);
        // 内存2M
        @ini_set('memory_limit', 20*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');
        //输出头部信息
        $header = array(
            "订单编号",
            "商品名称",
            "IMEI号",
            "总租金",
            "租期",
            "下单时间",
            "订单状态",
            "下单门店",
            "所属渠道",
        );
        $this->export_csv_wirter_row($handle,$header);
        //-+-------------------------------------------------------------------
        //-| 获取查询参数
        //-+-------------------------------------------------------------------
        $params   = $this->params;
        //过滤参数
        $params = filter_array($params,[
            "brand_id"  => "required",
            "order_status"  => "required",
            "begin_time"  => "required",
            "end_time"  => "required",
            "search_type"  => "required",
            "content"  => "required",
            "page"  => "required",
            "show_count"  => "required",
        ]);
        //订单表and订单商品表关系建立
        $where[] = "a.order_id=b.order_id";
        //品牌
        if($params['brand_id']){
            $where[] = "b.brand_id=".$params['brand_id'];
        }
        //订单状态
        if($params['order_status']){
            $where[] = "a.status=".$params['order_status'];
        }
        //时间区间
        if($params['begin_time']||$params['end_time']){
            $begin_time = $params['begin_time']?strtotime($params['begin_time']):strtotime(date("Y-m-d")." 00:00:00");
            $end_time = $params['end_time']?strtotime($params['end_time']." 23:59:59"):time();
            $where[] = "(a.create_time>".$begin_time." and a.create_time<".$end_time.")";
        }
        //搜索类型 1:订单号,2:手机号
        if($params['search_type'] && $params['content']){
            if($params['search_type']==1){
                $where[] = "a.order_no='".$params['content']."'";
            }
            elseif($params['search_type']==2){
                $where[] = "a.mobile='". $params['content']."''";
            }
        }
        //门店
        $request = api_request();
        $channel_id = (int)$request->getAppid();
        $shop = model('channel/channel_appid')->where(['channel_id' => '3'])->select();
        $shop = $this->arrayKey($shop,"id");
        $shop_id = array_column($shop,"id");
        $where[] = "a.appid in(".implode(',',$shop_id).")";
        //条件组合
        $where = implode(" and ",$where);
        $field = [
            'a.order_id',
            'a.order_no',
            'a.goods_id',
            'a.goods_name',
            'a.user_id',
            'a.realname',
            'a.appid',
            'a.mobile',
            'a.cert_no',
            'a.zuqi',
            'a.zujin',
            'a.yajin',
            'a.mianyajin',
            'a.yiwaixian',
            'a.amount',
            'a.discount_amount',
            'a.all_amount',
            'a.status',
            'a.create_time',
            'b.brand_id',
            'b.category_id',
            'b.thumb',
            'b.imei1',
            'b.serial_number',
        ];
        $sql = "select count(a.order_id) as num from `zuji_order2` as a,`zuji_order2_goods` as b where ".$where;
        //获取订单数
        $count = model()->query($sql);
        if(!$count){
            api_resopnse( [], ApiStatus::CODE_0 ,'订单获取失败');
            return;
        }
        //-+-------------------------------------------------------------------
        //-| 业务处理：查询总数和分页数据
        //-+-------------------------------------------------------------------
        $order_list = [];
        //获取数据
        $limit = 100;
        $offset = 0;
        // 循环获取内容
        $i = 1;
        while ( $offset < $count ){
            //获取订单数据列表
            $sql = "select ".implode(',',$field)." from `zuji_order2` as a, `zuji_order2_goods` as b where ".$where." limit ".$offset.",".$limit;
            $order_list = model()->query($sql);
            $offset += $limit;
            if( !$order_list ){
                break;
            }
            foreach ($order_list as $key => &$val) {
                $Order = new Order($val);
                //获取前端订单状态
                $status_name =  $Order->get_client_name();
                $_row = array(
                    "\t" . $val['order_no'], //订单编号
                    "\t" . $val['goods_name'], //商品名称
                    "\t" . $val['imei1'], //IMEI号
                    "\t" . $val['zujin'], //总租金
                    "\t" . $val['zuqi'], //租期
                    "\t" . $val['create_time'], //下单时间
                    "\t" . $status_name, //订单状态
                    "\t" . $shop[$val['appid']]['name'], //下单门店
                    "\t" . $this->member['channel_name'], //所属渠道
                );
                $this->export_csv_wirter_row($handle,$_row);
                if( $i%100 == 0 ){
                    ob_flush();
                    flush();
                }
                ++$i;
            }
        }
        if($order_list){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
        }
        fclose($handle);
        die;
    }

    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            $row[$key] = iconv('utf-8', 'gbk', $value);
        }
        fputcsv($handle, $row);
    }
}