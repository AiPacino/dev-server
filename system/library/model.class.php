<?php
/**
 * 模型层基类
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/17 0017-上午 11:23
 * @copyright (c) 2017, Huishoubao
 */

class model extends table
{
    protected $redis = null;
    public function __construct() {
        parent::__construct();
        $this->redis = \zuji\cache\Redis::getInstans();
    }

    public function getModelName() {
        if(empty($this->name))
            $this->name =   substr(get_class($this),0,-8);
        return $this->name;
    }

    protected function _after_insert($data,$options) {
        parent::_after_insert($data,$options);
        $key = $this->trueTableName.':id:'.$data['id'];
        $this->redis->set($key, serialize($data));
    }

    protected function _after_update($data,$options) {
        parent::_after_update($data,$options);
        $info = $this->find($data['id']);
        $key = $this->trueTableName.':id:'.$data['id'];
        $this->redis->set($key, serialize($info));
    }

    protected function _after_delete($data,$options) {
        parent::_after_delete($data,$options);
        foreach ($data['id'][1] as $id){
            $key = $this->trueTableName.':id:'.$id;
            $this->redis->del($key);
        }
    }

    /**
     * 启用、禁用某条记录
     */
    public function enable($id){
        //查询
        $model =  $this->modelId($id);
        if(!$model){
            $this->error = '信息不存在';
            return false;
        }

        $data = [];
        $data['status']=array('exp',' 1-status ');
        $result = $this->where(array('id' => $id))->save($data);
        if(!$result){
            $this->error = '操作失败';
        }
        return $result;
    }

    /**
     * 修改一条数据的信息
     * @param $id
     * @param $params
     * @return bool
     */
    public function change_info($id, $params){
        if((int)$id < 1){
            $this->error = lang('_param_error_');
            return false;
        }
        $result = $this->where(array('id'=>$id))->save($params);
        if($result === false){
            $this->error = lang('_operation_fail_');
        }
        return $result;
    }

    /**
     * 获取一条记录
     * @param $id
     * @return mixed|null
     */
    public function modelId($id){
        $id = intval($id);
        if($id){
            $key = $this->trueTableName.':id:'.$id;
            $data = $this->redis->get($key);
            if($data !== false){
                return unserialize($data);
            }

            return $this->find($id);
        }
        else{
            return NULL;
        }
    }


    /**
     * 分页获取数据, $page,pageSize 小于1时 返回所有数据
     * @param $page
     * @param $pageSize
     * @param $where
     * @param array $options
     * @return array
     */
    public function arrListByPage($page, $pageSize, $where, array $options = []){
        $return = ['total'=>0,'rows'=>[]];
        if ($page < 1 || $pageSize < 1){
            $query = $this->where($where)->field($options['field'])->order($options['order'])->select();
            if (empty($query)){
                $return['total'] = 0;
                $return['rows'] = [];
            }
            else{
                $return['total'] = count($query);
                $return['rows'] = $query;
            }
        }
        else{
            $count = $this->where($where)->count();
            if ($count < 1){
                $return = ['total'=>0,'rows'=>[]];
            }
            else{
                $query = $this->where($where)
                    ->field($options['field'])
                    ->page($page)
                    ->limit($pageSize)
                    ->order($options['order'])
                    ->select();
                $return['total'] = $count;
                $return['rows'] = $query;
            }
        }
        return $return;
    }

    /**
     * 添加、修改
     * @param $id
     * @param $params
     * @return bool|mixed
     */
    public function edit_params($id, $params){
        if(empty($id)){
            $data = $this->create($params);
            $result = $this->add($data);
        }else{
            $result = $this->update($params);
        }
        if($result === false){
            $this->error = $this->getError();
            return false;
        }
        return $result;
    }


}