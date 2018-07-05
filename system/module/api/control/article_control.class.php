<?php

hd_core::load_class('api', 'api');
/**
 * 活动查询接口
 * @access public 
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class article_control extends api_control {
    
    public function _initialize() {
        parent::_initialize();
    }
    //订单分期期数列表查询
    public function query(){
        $params   = $this->params;
        $params = filter_array($params, [
            'article_id' => 'required',	//【必须】int:文章id
        ]);
        if(empty($params['article_id'])){
            api_resopnse( ['sub_msg'=>'文章ID不能为空'], ApiStatus::CODE_20001,'参数必须', ApiSubCode::Params_Error,'文章ID不能为空');
            return;
        }
        /**************依赖服务************/
        $this->article_table = $this->load->table("misc/article");
        $article_info = $this->article_table->where(['display'=>1])->find($params['article_id']);
        if(!$article_info){
            api_resopnse( [], ApiStatus::CODE_50007,"文章不存在");
            return;
        }

        $result =[
            'article_id'=>$article_info['id'],//文章ID
            'title'=>$article_info['title'],//文章标题
            'content'=>$article_info['content'],//文章内容
            'thumb'=>$article_info['thumb'],//文章图片
            'recomment'=>$article_info['recomment'],//是否推荐 0 否 1是
            'url'=>$article_info['url'],//外链
            'keywords'=>$article_info['keywords'],//关键字
            'hits'=>$article_info['article_id'],//阅读量
        ];
        api_resopnse( $result, ApiStatus::CODE_0);
        return;
    }



}
