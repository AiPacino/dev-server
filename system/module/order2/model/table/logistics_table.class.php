<?php
/**
 * 		物流表
 */
class logistics_table extends table {

    protected $_validate = array(
        /* array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间]), */

       /* array('order_id', 'require', '{order/order_id_not_empty}', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('goods_id', 'require', '{order/goods_id_not_null}', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('address_id', 'require', '{order/address_id_not_null}', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('admin_id', 'require', '{order/admin_id_not_null}', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),*/
    );


  /**
   * 根据条件 获取相应字段
   * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
   */
  public function getFields($field = '',$sqlmap = array()) {
     return $this->where($sqlmap)->getfield($field);
  }

    /**
     * 根据条件 获取相应字段
     * @author limin <limin@huishoubao.com.cn>
     */
    public function get_api_list($field = '',$where = array()) {
        return $this->where($where)->field($field)->select();
    }
}