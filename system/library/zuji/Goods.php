<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace zuji;

/**
 * Description of Goods
 *
 * @author Administrator
 */
class Goods {
    
    
    /**
     * 商品必选个规格ID列表
     * @return array
     */
    public static function getMustSpecIdList(){
	return [Config::Sku_Spec_Chengse_Id,  Config::Sku_Spec_Zuqi_Id];
    }
    
}
