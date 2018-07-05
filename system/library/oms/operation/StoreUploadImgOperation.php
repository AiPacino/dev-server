<?php
namespace oms\operation;

use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\operator\Operator;
use zuji\order\OrderStatus;
use zuji\order\ServiceStatus;

/**
 * 上传图片IMEI 
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn> 
 *
 */
class StoreUploadImgOperation implements OrderOperation
{

    private $order_id         = 0;
    private $imei1            = "";
    private $serial_number    = "";
    private $card_hand        = "";
    private $card_positive    = "";
    private $card_negative    = "";
    private $goods_delivery   = "";
    
    
    public function __construct( $data ){
       
        $this->order_id         = $data['order_id'];
        $this->imei1            = $data['imei1'];
        $this->serial_number    = $data['serial_number'];
        $this->card_hand        = $data['card_hand'];
        $this->card_positive    = $data['card_positive'];
        $this->card_negative    = $data['card_negative'];
        $this->goods_delivery   = $data['goods_delivery'];
    }
    
     /**
     * 上传图片 IMEI号
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'imei1'=>'',        // 【必须】IMEI号
     *      'serial_number'=>'',// 【必须】序列号
     *      'card_hand'         // 【可选】手持身份证相片
     *      'card_positive'     // 【可选】身份证正面照片
     *      'card_negative'     // 【可选】身份证反面相片
     *      'goods_delivery'    // 【必须】商品交付相片
     * ]
     * @author maixaoyu <maxiaoyu@huishoubao.com.cn>
     */

    public function update(){
        $data['order_id']       = $this->order_id;
        $data['imei1']          = $this->imei1;
        $data['serial_number']  = $this->serial_number;
        $data['card_hand']      = $this->card_hand;
        $data['card_positive']  = $this->card_positive;
        $data['card_negative']  = $this->card_negative;
        $data['goods_delivery'] = $this->goods_delivery;

        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'imei1' => 'required',
            'serial_number' => 'required',
            'card_hand' => 'required',
            'card_positive' => 'required',
            'card_negative' => 'required',
            'goods_delivery'=>'required'
        ]);

        if(count($data) < 4 ){
            set_error("参数错误");
            return false;
        }

        $order_id       = $data['order_id'];

        // 图片不能少于一张
        if($data['goods_delivery'] ==  ''){
            set_error("参数错误");
            return false;
        }
        $load = \hd_load::getInstance();
       
        $image_table      = $load->table('order2/order2_image');
        $order_service    = $load->service('order2/order');
        $goods_table      = $load->table('order2/order2_goods');
        $service_service  = $load->service('order2/service');
        $delivery_table   = $load->table('order2/order2_delivery');

        //查询订单信息
        $where = array(
            'order_id'=>$order_id
        );
        $order_info  =  $order_service->get_order_info($where);
        if(!$order_info){
              set_error("生成服务单失败");
              return false;
          }
        // 商品信息
        $goods_info  =  $order_service->get_goods_info($order_info['goods_id']);

        // 修改imei
        $goods_data['imei1']          = $data['imei1'];
        $goods_data['serial_number']  = $data['serial_number'];

        $goods_table->where(['goods_id'=>$order_info['goods_id']])->save($goods_data);

        //上传相片
        $datas['order_id']          = $order_info['order_id'];
        $datas['card_hand']         = isset($data['card_hand']) ? $data['card_hand'] : "";
        $datas['card_positive']     = isset($data['card_positive']) ? $data['card_positive'] : "";
        $datas['card_negative']     = isset($data['card_negative']) ? $data['card_negative'] : "";
        $datas['goods_delivery']    = $data['goods_delivery'];   

        $image_result = $image_table->create($datas);
        if(!$image_result){
          set_error("图片上传失败");
          return false;
        }
        
          // 开启服务
          $service_data['order_id']     = (int)$order_info['order_id'];
          $service_data['order_no']     = (int)$order_info['order_no'];
          $service_data['mobile']       = (int)$order_info['mobile'];
          $service_data['user_id']      = (int)$order_info['user_id'];
          $service_data['business_key'] = (int)$order_info['business_key'];
          $service_data['begin_time']   = time();
          $service_data['zuqi']         = (int)$order_info['zuqi'];

          $service_id = $service_service->create($service_data);

          if(!$service_id){
              set_error("生成服务单失败");
              return false;
          }

            //更新订单
            $order_data = [
                'status' =>State::OrderInService,
                'update_time'=> time(),//更新时间
                'service_status'=>ServiceStatus::ServiceOpen,
                'service_id'=>$service_id,
            ];
            $order_table = $load->table('order2/order2');

            $b =$order_table->where(['order_id'=>$this->order_id])->save($order_data);
            if(!$b){
                set_error("更新订单状态失败");
                return false;
            }


       return true;
        
    }
}