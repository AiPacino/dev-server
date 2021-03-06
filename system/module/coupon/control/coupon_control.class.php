<?php
/**
 * 优惠券
 * User: wangjinlin
 * Date: 2018/1/9
 * Time: 上午11:29
 */
use zuji\debug\Debug;
use zuji\coupon;
use zuji\Business;
use alipay;
use alipay\ZhimaDataSingleFeedback;
hd_core::load_class('base', 'order2');
class coupon_control extends base_control {
//class coupon_control extends control{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        //实例化 table
        $this->coupon_table = $this->load->table('coupon/coupon');
        $this->coupon_type_table = $this->load->table('coupon/coupon_type');
        $this->goods_spu_table = $this->load->table('goods/goods_spu');
        $this->channel_table = $this->load->table('channel/channel');
        //$this->category_table = $this->load->table('goods/goods_category');
        $this->brand_table = $this->load->table('goods/brand');
    }
    public function test2(){
//        $zhima = new ZhimaDataSingleFeedback('300001198');
//        $zhima->ZhimaDataSingleFeedback();
        echo '<br>----------------------<br>';
        $this->coupon_table->find(4);
        $this->coupon_table->startTrans();
        $this->coupon_table->find(4);
        $this->coupon_table->find(8);
        $this->coupon_table->commit();
//        $this->coupon_table->rollback();
//        be2404454b6aba62
//        d3e1cf6d0bea81f7
//        73f9ffcdbb032269
//        9个

    }
//    public function test(){
//        $order_row = [
//               'user_id'=>2,
//                'only_id'=>'70c5cd984eb0cd7c04dbcc3d3f636691',
//           ];
//        $list = coupon\Coupon::set_coupon_user($order_row);
//        var_dump($list);
//    }

    /*
     * 优惠券类型列表
     */
    public function coupon_type_list(){
        // 查询条件
        $where = [];

        $limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $coupon_type_list = $this->coupon_type_table
            ->field($this->coupon_type_table->fields)
            ->where($where)
            ->page($_GET['page'])
            ->limit($limit)
            ->order('id')
            ->select();

        $count  = $this->coupon_type_table->where($where)->count('id');
        $pages  = $this->admin_pages($count, $limit);

        $lists = array(
            'th' => array(
                'only_id' => array('length' => 16,'title' => '优惠券对外ID'),
                'coupon_name' => array('length' => 8,'title' => '优惠券名称'),
                'coupon_type' => array('length' => 8,'title' => '优惠类型'),
                'coupon_value' => array('length' => 8,'title' => '优惠类型值'),
                'range' => array('length' => 8,'title' => '优惠范围'),
                'range_value' => array('length' => 8,'title' => '优惠范围值'),
                'mode' => array('length' => 8,'title' => '优惠方式'),
                'use_restrictions' => array('length' => 8,'title' => '使用限制'),
                'describe' => array('length' => 18,'title' => '优惠券描述'),

            ),
            'lists' => $coupon_type_list,
            'pages' => $pages,
        );

        $this->load->librarys('View')
            ->assign('lists',$lists)
            ->assign('pages',$pages)
            ->display('coupon_type_list');
    }

    /*
     * 优惠券列表
     */
    public function coupon_list(){
        // 查询条件
        $where = [];
        if($_GET['keywords']){
            $where['coupon_no'] = $_GET['keywords'];
        }
        if($_GET['start_time']){
            $where['start_time'] = ['EGT',strtotime($_GET['start_time'])];
        }
        if($_GET['end_time']){
            $where['end_time'] = ['ELT',strtotime($_GET['end_time'])];
        }
        if($_GET['coupon_type_id']){
            $where['coupon_type_id'] = $_GET['coupon_type_id'];
        }


        $limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $coupon_list = $this->coupon_table
            ->field($this->coupon_table->fields)
            ->where($where)
            ->page($_GET['page'])
            ->limit($limit)
            ->order('id')
            ->select();

        $coupon_type_list = $this->coupon_type_table->field('id,coupon_name')->select();
        $coupon_date[0]='全部';
        foreach ($coupon_type_list as $k=>$val){
            $coupon_date[$val['id']]=$val['coupon_name'];
        }

        $count  = $this->coupon_table->where($where)->count('id');
        $pages  = $this->admin_pages($count, $limit);

        foreach ($coupon_list as $k=>$item) {
            $coupon_list[$k]['coupon_type_id'] = $coupon_date[$item['coupon_type_id']];
            $coupon_list[$k]['status'] = coupon\CouponStatus::get_coupon_status_name($item['status']);
            $coupon_list[$k]['start_time'] = date('Y-m-d H:i:s',$item['start_time']);
            $coupon_list[$k]['end_time'] = date('Y-m-d H:i:s',$item['end_time']);
        }
        $lists = array(
            'th' => array(
                'id' => array('length' => 10,'title' => '优惠ID'),
                'coupon_type_id' => array('length' => 10,'title' => '优惠券类型ID'),
                'coupon_no' => array('length' => 10,'title' => '优惠码'),
                'status' => array('length' => 10,'title' => '状态'),
                'start_time' => array('length' => 10,'title' => '开始时间'),
                'end_time' => array('length' => 10,'title' => '结束时间'),
                'user_id' => array('length' => 10,'title' => '用户ID')
            ),
            'lists' => $coupon_list,
            'pages' => $pages,
        );

        $this->load->librarys('View')
            ->assign('lists',$lists)
            ->assign('coupon_date',$coupon_date)
            ->assign('pages',$pages)
            ->display('coupon_list');
    }

    /*
     * 添加优惠券类型
     */
    public function coupon_type_add(){
        //'&coupon_name=test&coupon_type=1&coupon_value=100&range=0&range_value=0&mode=1&describe=1元优惠券test&use_restrictions=0';
        if(checksubmit('dosubmit')){
            $validate = ['coupon_name','coupon_type','coupon_value','range','range_value','mode','describe','use_restrictions'];
            foreach ($validate as $v){
                if(trim($_GET[$v])==''){exit('字段:'.$v.' 不能为空');}
            }
            $data = [
                'coupon_name'=>$_GET['coupon_name'],//优惠券名称
                'coupon_type'=>$_GET['coupon_type'],//优惠类型1固定金额2租金百分比
                'coupon_value'=>$_GET['coupon_value'],//根据优惠类型填写的值。如果是固定金额单位是分
                'range'=>$_GET['range'],//优惠范围0全场，1商品spuID，2新机，3二手机，4手机类别，5渠道
                'range_value'=>$_GET['range_value'],//优惠范围值全场默认0。指定商品填写spu ID多个用','分割
                'mode'=>$_GET['mode'],//优惠方式1直减减总额2返现付款后
                'describe'=>$_GET['describe'],//优惠券描述
                'use_restrictions'=>$_GET['use_restrictions'],//使用限制单位分0不限制
                'only_id'=>coupon\CouponFunction::get_uuid()
            ];
            if($this->coupon_type_table->add($data)){
                showmessage(lang('_operation_success_'),url('coupon_type_list'));
            }else{
//                echo '操作失败:'.$this->coupon_type_table->getDbError();
                showmessage('生成优惠券类型失败');
            }
        }else{
            $this->load->librarys('View')->display('coupon_type_add');
        }
    }

    /*
     * 修改优惠券类型
     */
    public function coupon_type_set(){
        if(checksubmit('dosubmit')){
            $validate = ['id','coupon_name','coupon_type','coupon_value','range','range_value','mode','describe','use_restrictions'];
            foreach ($validate as $v){
                if(trim($_POST[$v])==''){exit('字段:'.$v.' 不能为空');}
            }
            $data = [
                'coupon_name'=>$_POST['coupon_name'],//优惠券名称
                'coupon_type'=>$_POST['coupon_type'],//优惠类型1固定金额2租金百分比
                'coupon_value'=>$_POST['coupon_value'],//根据优惠类型填写的值。如果是固定金额单位是分
                'range'=>$_POST['range'],//优惠范围0全场，1商品spuID，2新机，3二手机，4手机类别，5渠道
                'range_value'=>$_POST['range_value'],//优惠范围值全场默认0。指定商品填写spu ID多个用','分割
                'mode'=>$_POST['mode'],//优惠方式1直减减总额2返现付款后
                'describe'=>$_POST['describe'],//优惠券描述
                'use_restrictions'=>$_POST['use_restrictions'],//使用限制单位分0不限制
            ];
            if($this->coupon_type_table->where(['id'=>$_POST['id']])->save($data)){
                showmessage(lang('_operation_success_'),url('coupon_type_list'));
            }else{
//                echo '操作失败:'.$this->coupon_type_table->getDbError();
                showmessage('修改优惠券类型失败');
            }
        }else{
            if(!$_GET['id']){
                showmessage('修改优惠券类型参数错误');
            }
            $coupon_type_row = $this->coupon_type_table->field($this->coupon_type_table->fields)->find($_GET['id']);
            $this->load->librarys('View')->assign('row',$coupon_type_row)->display('coupon_type_set');
        }
    }

    /*
     * 生成指定优惠劵类型和要生成的条数
     */
    public function coupon_add(){
        if(checksubmit('dosubmit')){
            foreach ($_POST as $k=>$item){
                if (!$item) { showmessage('参数:'.$k.'不能为空'); }
            }
            $coupon_type_id = $_POST['coupon_type_id'];
            $num = $_POST['num'];
            $start_time = strtotime($_POST['start_time']);
            $end_time = strtotime($_POST['end_time']);

            $trans = $this->coupon_table->startTrans();
            if (!$trans) {
                showmessage('服务器繁忙');
            }
            try{
                for ($i=0;$i<$num;$i++){
                    $row = [
                        'coupon_type_id'    =>$coupon_type_id,
                        'coupon_no'       =>coupon\CouponFunction::md5_16(),
                        'status'            =>0,
                        'start_time'        => $start_time,
                        'end_time'          => $end_time,
                        'user_id'           =>0
                    ];
                    if(!$this->coupon_table->add($row)){
                        $this->coupon_table->rollback();
                        showmessage('系统生成优惠券失败');
                        //exit('系统自动生成优惠券失败,coupon_code:'.$row['coupon_code']);
                    }
                }
                $this->coupon_table->commit();
            }catch (\Exception $exc){
                $this->coupon_table->rollback();
                showmessage('系统生成优惠券失败');
            }
            showmessage(lang('_operation_success_'),url('coupon_list'));

        }else{
            $coupon_type_list = $this->coupon_type_table->field('id,coupon_name')->select();
            $coupon_date=[];
            foreach ($coupon_type_list as $k=>$val){
                $coupon_date[$val['id']]=$val['coupon_name'];
            }
            $this->load->librarys('View')
                ->assign('coupon_date',$coupon_date)
                ->display('coupon_add');
        }
    }

}