<?php
namespace zuji\email;

/**
 * 
 *
 * @author 
 */
class EmailConfig {

    /**
     * @var string 系统账号
     */
    const System_Username = "zujiji@huishoubao.com.cn";
    /**
     * @var string 系统账号密码
     */
    const System_Password ="Zuji123456";
    /**
     * @var string 系统昵称
     */
    const System_Nickname ="机市";

    /**
     * @var string 客服组账号
     */
    const Service_Username="zjkf@huishoubao.com.cn";
    /**
     * @var string 商品组账号
     */
    const Goods_Username="zjsp@huishoubao.com.cn";
    /**
     * @var string 财务组账号
     */
    const Finance_Username="zjcw@huishoubao.com.cn";

/**    
 * 系统发送邮件
 * @param array $data  二维数组
 * [
 *      'subject'=>'' //【必须】 标题内容
 *      'body'=>'' //【必须】文件内容
 *      'address'=>array(
 *          [  //【必须】收件人地址  可以多个收件人  
 *              'address'=>''  //【必须】string 收件地址
 *              'name'=>'' //【可选】 string 收件人姓名/昵称
 *          ],
 *      )
 * ]
 */

    public static function system_send_email($data){
        $sendEmail =new SendEmailApi();
        $sendEmail->setFromName(EmailConfig::System_Nickname);//设置发件人昵称
        $sendEmail->setUsername(EmailConfig::System_Username);//设置发件人smtp登录账号
        $sendEmail->setPassword(EmailConfig::System_Password);//设置发件人smtp登录密码
        $sendEmail->setFromAdd(EmailConfig::System_Username);//设置发件人邮箱地址

        $sendEmail->setSendAdd( $data['address'] );//设置收件人邮箱地址
        $sendEmail->setSubject( $data['subject'] );//设置邮件标题
        $sendEmail->setBody( $data['body']);//设置邮件内容
        $result = $sendEmail->send();
        return $result;
    }

}
