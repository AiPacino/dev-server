<?php
namespace oms\operation;

interface OrderOperation{
    
    /**
     * 
     * @return boolean  true：成功；false：失败
     */
    public function update();
}