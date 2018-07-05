<?php
namespace oms\operation;
use oms\Order;
use oms\state\State;
use zuji\Config;
use zuji\order\EvaluationStatus;

/**
 * 异常处理
 * @param int order_id                【必须】订单ID
 * @param int unqualified_result      【必须】状态
 * @author maxiaoyu<maxiaoyu@huishoubao.com.cn>
 *
 */
class AbnormalOperation implements OrderOperation
{

    private $unqualified_result       = 0;
    private $order_id               = 0;
    private $evaluation_id          = 0;
    private $unqualified_remark     = "";
    private $admin_id               = 0;
    private $Order =null;

    public function __construct( $data ,Order $order){

        $this->unqualified_result     = $data['unqualified_result'];
        $this->order_id             = $data['order_id'];
        $this->evaluation_id        = $data['evaluation_id'];
        $this->unqualified_remark   = $data['unqualified_remark'];
        $this->admin_id             = $data['admin_id'];
        $this->Order =$order;
    }
    
    public function update(){  
       
        $data['unqualified_result']       = $this->unqualified_result;
        $data['order_id']               = $this->order_id;
        $data['evaluation_id']          = $this->evaluation_id;
        $data['unqualified_remark']     = $this->unqualified_remark;
        $data['admin_id']               = $this->admin_id;


        $data = filter_array($data, [
            'unqualified_result' => 'required|is_id',
            'order_id' => 'required|is_id',
            'evaluation_id' => 'required|is_id',
            'unqualified_remark' => 'required',
            'admin_id'=>'required',
        ]);

        if( count($data)<5 ){
            set_error("参数错误");
            return false;
        }

         //检测异常结果入库
        $unqualified_data = [
            'unqualified_result' => $this->unqualified_result,
            'unqualified_remark' => $this->unqualified_remark,
            'unqualified_admin_id' => $data['admin_id'],
        ];

        // 修改状态
        $load = \hd_load::getInstance();
        $evaluation_table = $load->table('order2/order2_evaluation');
        $b =$evaluation_table->where(['evaluation_id'=>$this->evaluation_id])->save($unqualified_data);
        if(!$b){
            set_error("更新检测单信息失败");
            return false;
        }
        if($this->Order->get_payment_type_id() == Config::MiniAlipay && $this->unqualified_result == EvaluationStatus::UnqualifiedAccepted){
            //更新订单
            $order_table = $load->table('order2/order2');
            $order_data = [
                'status' =>State::OrderRefunding,
                'update_time'=> time(),//更新时间
            ];
            $b =$order_table->where(['order_id'=>$this->order_id])->save($order_data);
            if(!$b){
                set_error("更新订单状态失败");
                return false;
            }
        }

        return true;
    }
}