<?php

/**
 * 		子商品数据层
 *      [wangjinlin]
 */
class goods_sku_service extends service {

    public function _initialize() {
        $this->sku_db = $this->load->table('api/goods_sku');
        $this->spu_db = $this->load->table('api/goods_spu');
        $this->index_db = $this->load->table('api/goods_index');
        $this->goodsattr_db = $this->load->table('api/goods_attribute');
        $this->cate_service = $this->load->service('api/goods_category');
    }

    /**
     * []
     */



    /**
     * [detail 查询子商品详情]
     * @param  [type]  $id    [子商品id]
     * @return [type]         [description]
     */
    public function detail($id) {
        if ((int) $id < 1) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $goods_info = array();
        $goods_info = $this->fetch_by_id($id, 'spu,price,brand,cat_name,show_index');
        if (!$goods_info['sku_id']) {
            $this->error = lang('goods_goods_not_exist', 'goods/language');
            return FALSE;
        }
        $sku_list = $this->sku_db->where(array('spu_id' => $goods_info['spu_id']))->select();
        if ($sku_list) {
            $sku = array();
            foreach ($sku_list AS $v) {
                $v['spec_array'] = $v['spec'];
                $sku[$v['sku_id']] = $v;
            }
        }
        if ($sku) {
            foreach ($sku as $k => $v) {
                $spec_md5 = '';
                $spec_md = array();
                foreach ($v['spec_array'] AS $value) {
                    $spec_md[] = md5($value['id'] . ':' . $value['value']);
                    $spec_md5 .= $value['id'] . ':' . $value['value'] . ';';
                }
                $sku[$k]['spec_md5'] = $spec_md5;
                $sku[$k]['spec_md'] = implode(";", $spec_md);
                $sku[$k]['url'] = url('goods/index/detail', array('sku_id' => $v['sku_id']));
                $sku_arr[$spec_md5] = $sku[$k];
            }
            $goods_info['sku_arr'] = $sku_arr;
        }
        $spec_str = '';
        foreach ($goods_info['spec'] AS $spec) {
            $spec_str .= $spec['id'] . ':' . $spec['value'] . ';';
            $spec_show .= $spec['name'] . ':' . $spec['value'] . '&nbsp;&nbsp;';
        }
        $goods_info['spec_str'] = $spec_str;
        $goods_info['spec_show'] = $spec_show;
        $goods_info['spec'] = json_encode($goods_info['spec']);
        $goods_info['attrs'] = $this->attrs_detail($id);
        runhook('after_sku_detail', $goods_info);
        return $goods_info;
    }

    /**
     * [fetch_by_id 获取一条子商品信息]
     * @param  [type]  $id    [description]
     * @param  boolean $field [description]
     * @return [type]         [description]
     */
    public function fetch_by_id($id = 0, $extra = '') {
        if ((int) $id < 1) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        $goods = $this->sku_db->detail($id);
        if (!($goods->result['sku'])) {
            $this->error = lang('goods_goods_not_exist', 'goods/language');
            return FALSE;
        }
        if ($extra) {
            $extra = explode(",", $extra);
            foreach ($extra AS $method) {
                if (method_exists($this->sku_db, $method)) {
                    $goods = $goods->$method();
                }
            }
        }
        $sku = $goods->output();
        runhook('after_sku_fetch_by_id', $sku);
        return $sku;
    }

    /**
     * [get_sku 根据主商品获取子商品]
     * @param  [type] $id [主商品id]
     * @return [type]     [description]
     */
    public function get_sku($id) {
        $sku_ids = $this->sku_db->where(array('spu_id' => $id, 'status' => array('NEQ', -1)))->order('sku_id ASC')->getField('sku_id', TRUE);
        $result = array();
        foreach ($sku_ids as $key => $sku_id) {
            $sku = $this->sku_db->detail($sku_id)->output();
            $specs = $sku['spec'];
            unset($sku['spec']);
            $spec_str = '';
            foreach ($specs AS $id => $spec) {
                $sku['spec'][md5($id . $spec['value'])] = $spec;
                $spec_str .= $spec['name'] . ':' . $spec['value'] . ' ';
                $spec_md5 = md5($spec_str);
            }
            $sku['spec_md5'] = $spec_md5;
            $sku['spec_str'] = $spec_str;
            $result[$sku['sku_id']] = $sku;
        }
        if (!$result) {
            $this->error = $this->sku_db->getError();
        }
        runhook('after_get_sku', $result);
        return $result;
    }


    /**
     * [sku_detail sku详情]
     * @param  [type] $sku_ids [支持数组]
     * @return [type]          [description]
     */
    public function sku_detail($sku_ids) {
        if (empty($sku_ids)) {
            $this->error = lang('_param_error_');
            return FALSE;
        }
        if (is_array($sku_ids)) {
            foreach ($sku_ids AS $sku_id) {
                $sku = $this->fetch_by_id($sku_id);
                $spec_str = '';
                foreach ($sku['spec'] AS $spec) {
                    $spec_str .= $spec['name'] . ':' . $spec['value'] . ' ';
                }
                $sku['spec'] = $spec_str;
                $result[] = $sku;
            }
        } else {
            $result = $this->fetch_by_id($sku_ids);
            ;
        }
        return $result;
    }


    /**
     * @param  string  获取的字段
     * @param  array 	sql条件
     * @return [type]
     */
    public function getField($field = '', $sqlmap = array()) {
        $exist = strpos($field, ',');
        if ($exist === false) {
            $result = $this->index_db->where($sqlmap)->getfield($field);
        } else {
            $result = $this->index_db->where($sqlmap)->field($field)->select();
        }
        if ($result === false) {
            $this->error = lang('_param_error_');
            return false;
        }
        return $result;
    }

    /**
     * @param  string  获取的字段
     * @param  array 	sql条件
     * @return [type]
     */
    public function getBySkuid() {
        return $this->sku_db->getBySkuid();
    }

}
