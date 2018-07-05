<?php
hd_core::load_class('api', 'api');

class goods_control extends api_control
{
    private $spu_serve;
    private $sku_serve;
    private $brand_serve;
    public function _initialize()
    {
        parent::_initialize();
        // $this->spu_serve     = $this->load->service('goods2/goods_spu');
        // $this->sku_serve     = $this->load->service('goods2/goods_sku');
        // $this->brand_serve  = $this->load->service('goods2/brand');
        // $this->spec_serve    = $this->load->service('goods2/spec');
        // $this->category = $this->load->service("goods/goods_category");
        $this->order_service = $this->load->service('order2/order');
    }


    public function test(){
        $additional = [
            'size' => 1
        ];

        $order_list = $this->order_service->get_order_list([],$additional);
       
        $this->admin['id'] = 1;
        $this->admin['username'] = 'maxiaoyu';
        // 当前 操作员
        $admin = [
            'id' =>$this->admin['id'],
            'username' =>$this->admin['username'],
        ];
        $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
        
        $i=0;
        foreach( $order_list as $item ){
            $item['status'] = ++$i;
            $Order = new oms\Order($item);

            p($Order->huanhuo());
            
        }

    }


    public function images(){
        
        $this->image_service = $this->load->service('order2/order_image');
        
        $a = $this->image_service->get_one(2147483647);
        p($a);

    }

    public function imei(){
        $array = array(
            'status'        => 2,
            'order_id'      => 111111,
            'order_no'      => 1234567890,
            'amount'        => 100,
            'goods_name'    => '测试项目'
        );
        
        $admin = [
            'id'            => 1,
            'username'      => 'admin'
         
        ];

        $Operator = new oms\operator\Admin($admin['id'], $admin['username']);
        
        
        $Order = new oms\Order($array);
        p($Order->get_imei());
    }
    /**
     * 
    */
    public function log(){
        $array = array(
            'status'        => 2,
            'order_id'      => 111111,
            'order_no'      => 1234567890,
            'amount'        => 100,
            'goods_name'    => '测试项目'
        );
        
        $admin = [
            'id'            => 1,
            'username'      => 'admin'
         
        ];

        $Operator = new oms\operator\Admin($admin['id'], $admin['username']);
        

        $Order = new oms\Order($array);

        // 订单 观察者主题
        $OrderObservable = $Order->get_observable();

        // 订单 观察者  日志
        $LogObserver = new oms\observer\LogObserver( $OrderObservable , "测试成功", "测试备注信息");
        $LogObserver->set_operator($Operator);


        $b = $Order->get_observable();
        

        $a = $LogObserver->update();
        p($a);


    
    }

    /**
     * [lists 接口商品列表]
     * @return [json][response]
     */
    public function queryall()
    {
        $additional = [
            'size' => 1
        ];
        $order_list = $this->order_service->get_order_list([],$additional);
        // p($order_list);
        $this->admin['id'] = 1;
        $this->admin['username'] = 'maxiaoyu';
        // 当前 操作员
        $admin = [
            'id' =>$this->admin['id'],
            'username' =>$this->admin['username'],
        ];
        $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
        $i=0;
        foreach( $order_list as $item ){
            $item['status'] = ++$i;
            $Order = new oms\Order($item);
//          var_dump( $Order->get_state() );
//          // 订单 观察者主题
//          $OrderObservable = $Order->get_observable();      
//          // 订单 观察者  日志
//          $LogObserver = new oms\observer\LogObserver( $OrderObservable );
//          $LogObserver->set_operator($Operator);
        
            var_dump('--------------------------------------------------------');
            var_dump('order_id: '.$item['order_id'].' status: '.$item['status']);
            var_dump('是否允许取消：'. ($Order->allow_to_cancel_order()?'True':'False'));
            try { $Order->cancel_order(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }
            var_dump('是否允许 门店审核：'. ($Order->allow_to_store_check_order()?'True':'False'));
            try { $Order->store_check_order(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }



            var_dump('是否允许 检测合格：'. ($Order->allow_to_testing_fail()?'True':'False'));
            try { $Order->testing_fail(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }

            var_dump('是否允许 检测不合格：'. ($Order->allow_to_testing_qualified()?'True':'False'));
            try { $Order->testing_qualified(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }

            var_dump('是否允许 换货：'. ($Order->allow_to_exchange_goods()?'True':'False'));
            try { $Order->exchange_goods(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }

            var_dump('是否允许 回寄：'. ($Order->allow_to_huiji()?'True':'False'));
            try { $Order->huiji(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }

            var_dump('是否允许 买断中：'. ($Order->allow_to_apply_for_buyout()?'True':'False'));
            try { $Order->apply_for_buyout(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }

            var_dump('是否允许 是否已买断：'. ($Order->allow_to_buyout()?'True':'False'));
            try { $Order->buyout(); } catch (\Exception $exc) { var_dump($exc->getMessage()); }
            
            
        }exit;
        
die;
        

        $where['status'] = 1;
        $goods = $this->spu_serve->api_get_list($where);
        foreach($goods as $key=>$val){
            $goods[$key]['sku_total'] = $val['sku_total']>0?$val['sku_total']:0;
            $goods[$key]['yiwaixian'] = $val['yiwaixian']>0?zuji\order\Order::priceFormat($val['yiwaixian']):0;
            $goods[$key]['min_price'] = $val['min_price']>0?zuji\order\Order::priceFormat($val['min_price']):0;
            $goods[$key]['max_month'] = $val['max_month']>0?$val['max_month']:0;
            $goods[$key]['min_month'] = $val['min_month']>0?$val['min_month']:0;
            $goods[$key]['flag']  = "spu";
            $goods[$key]['imgs'] = $val['imgs']?json_decode($val['imgs'],true):"";
        }
        $brand = $this->brand_serve->api_get_list();
        $category = $this->category->category_lists();
        foreach($category as &$item){
            unset($item['status']);
            unset($item['level']);
        }

        api_resopnse( array('category_list'=>$category,'brand_list'=>$brand,'spu_list'=>$goods),ApiStatus::CODE_0 );
    }

}