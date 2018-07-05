<?php
/**
 * 2小时后自动取消未支付订单
 * User: wangjinlin
 * Date: 2017/12/18
 * Time: 上午11:13
 */
include dirname(__FILE__).'/config/database.php';

try {
    $pdo = new PDO("mysql:host=".$database['db_host'].";dbname=".$database['db_name'], $database['db_user'], $database['db_pwd']);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();exit;
}
$pdo->query('set names utf8;');
//$sql = "SELECT order_id,step_status FROM zuji_order2 WHERE order_status=1 AND step_status=2 AND payment_status<5 AND create_time<1513682543";
$sql = "SELECT order_id,step_status FROM zuji_order2 WHERE order_status=1 AND step_status=2 AND payment_status<5 AND create_time<".(time()-7200);
$result = $pdo->query($sql);
$rows = $result->fetchAll();

foreach ($rows as $row) {
    $log ='订单ID'.$row['order_id'].'自动取消未支付订单开始.';
    $update_time = time();
    //更新订单 状态
    $update = "UPDATE zuji_order2 SET order_status=3, payment_status=4, update_time=".$update_time." WHERE order_id=".$row['order_id'];
    $pdo->exec($update);
    $log .= '修改zuji_order2.';
    //更新支付单 状态
    $update2 = "UPDATE zuji_order2_payment SET payment_status=4, update_time=".$update_time." WHERE order_id=".$row['order_id'];
    $pdo->exec($update2);
    $log .= '修改zuji_order2_payment.';
    //创建订单流 zuji_order2_follow
    $follow_insert = "INSERT INTO zuji_order2_follow (order_id,step_status,follow_status,order_status,create_time,admin_id) VALUE (".$row['order_id'].",".$row['step_status'].",".$row['step_status'].",3,".$update_time.",0)";
    $pdo->exec($follow_insert);
    $log .= '创建zuji_order2_follow;';
    $log .='订单ID'.$row['order_id'].'自动取消未支付订单完毕';
    //创建日志
    $follow_insert = "INSERT INTO zuji_crontab_log (type,log,create_time) VALUE (1,'".$log."',$update_time)";
    $pdo->exec($follow_insert);
}
