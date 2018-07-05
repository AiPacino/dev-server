<?php
/**
 *	    文章数据层
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */

class article_table extends table {
    /**
     * 发货单表的数据库字段
     */
    protected $_field = [
        'id',
        'title',
        'content',
        'category_id',
        'thumb',
        'display',
        'recommend',
        'url',
        'dataline',
        'sort',
        'keywords',
        'hits',
        'channel_id'
    ];
    protected $_validate = array(
        array('title','require','{misc/article_name_require}',0),
		array('category_id','require','{misc/article_classify_require}',0),
		array('sort','number','{misc/sort_require}',2),
    );
    protected $_auto = array(
    	array('dataline','time',1,'function'),
    );
    /**
     * 查询列表
     * @return array
     */
    public function get_list($where=[],$additional=[]) {
        $article_list = $this->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->field($this->_field)->where($where)->select();
        if($article_list){
            return $article_list;
        }
        return [];
    }
}