<?php
/**
 * 发货7天后系统自动签收
 * User: wangjinlin
 * Date: 2017/12/18
 * Time: 上午11:13
 */
date_default_timezone_set("Asia/Shanghai");

include dirname(__FILE__).'/config/database.php';
try {
    $pdo = new PDO("mysql:host=".$database['db_host'].";dbname=".$database['db_name'], $database['db_user'], $database['db_pwd']);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();exit;
}

$pdo->query('set names utf8;');
$sql = "SELECT d.delivery_id,o.order_id,o.order_no,o.mobile,o.user_id,o.business_key,o.zuqi,o.step_status FROM zuji_order2_delivery as d INNER JOIN zuji_order2 as o ON d.order_id=o.order_id WHERE d.delivery_status=4 AND delivery_time<".(time()-604800);
$result = $pdo->query($sql);
$rows = $result->fetchAll();
foreach ($rows as $order_info) {
    $log ='订单ID'.$row['order_id'].'自动签收开始.';
    $update_time = time();
    //更新发货单
    $update = "UPDATE zuji_order2_delivery SET delivery_status=5, confirm_remark='发货7天后系统自动签收', update_time=".$update_time.", confirm_time=".$update_time." WHERE delivery_id=".$order_info['delivery_id'];
    $pdo->exec($update);
    $log .= '修改zuji_order2_delivery完成.';

    //租机服务  生成服务单  同步到订单
    if($order_info['business_key']==1){
        //创建服务单
        $begin_time = get_tomorrow_time();
        $end_time = 86400*30*$order_info['zuqi'] + $begin_time;
        $service_insert = "INSERT INTO zuji_order2_service (order_id,order_no,mobile,user_id,business_key,begin_time,end_time,create_time,remark) VALUE (".$order_info['order_id'].",'".$order_info['order_no']."','".$order_info['mobile']."',".$order_info['user_id'].",".$order_info['business_key'].",".$begin_time.",".$end_time.",".$update_time.",'发货7天后系统自动签收并生成服务')";
//        $service_insert = "UPDATE zuji_order2_service SET service_status=2, update_time=".$update_time." WHERE order_id=".$order_info['order_id'];
        $service_id = $pdo->exec($service_insert);
        $log .= '创建zuji_order2_service完成.';

        //更新订单 阶段状态,发货状态,服务状态,服务ID,更新时间
        $update2 = "UPDATE zuji_order2 SET step_status=8, delivery_status=5, service_status=2, service_id=".$service_id.", update_time=".$update_time." WHERE order_id=".$order_info['order_id'];
        $pdo->exec($update2);
        $log .= '修改zuji_order2完成.';

        //创建订单流 zuji_order2_follow
        $follow_insert = "INSERT INTO zuji_order2_follow (order_id,step_status,follow_status,order_status,create_time,admin_id) VALUE (".$order_info['order_id'].",8,".$order_info['step_status'].",1,".$update_time.",0)";
        $pdo->exec($follow_insert);
        $log .= '创建zuji_order2_follow完成;';
    }
    //如果是买断服务 确认收货时  订单关闭
    elseif ($order_info['business_key']==3){
        //更新订单 状态,阶段状态,发货状态,更新时间
        $update2 = "UPDATE zuji_order2 SET order_status=4, step_status=8, delivery_status=5, update_time=".$update_time." WHERE order_id=".$order_info['order_id'];
        $pdo->exec($update2);
        $log .= '修改zuji_order2完成.';

        //创建订单流 zuji_order2_follow
        $follow_insert = "INSERT INTO zuji_order2_follow (order_id,step_status,follow_status,order_status,create_time,admin_id) VALUE (".$order_info['order_id'].",8,".$order_info['step_status'].",4,".$update_time.",0)";
        $pdo->exec($follow_insert);
        $log .= '创建zuji_order2_follow完成;';
    }
    $log .='订单ID'.$row['order_id'].'自动签收完毕';
    //创建日志
    $follow_insert = "INSERT INTO zuji_crontab_log (type,log,create_time) VALUE (2,'".$log."',$update_time)";
    $pdo->exec($follow_insert);
}

//获取明天时间
function get_tomorrow_time(){
    //获取当天的年份
    $y = date("Y");
    //获取当天的月份
    $m = date("m");
    //获取当天的日数
    $d = date("d");
    //将今天开始的年月日时分秒，转换成unix时间戳(开始示例：2015-10-12 00:00:00)
    $todayTime= mktime(0,0,0,$m,$d,$y);
    return $todayTime+86400;
}