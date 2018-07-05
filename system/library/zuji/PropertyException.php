<?php
namespace zuji;
/**
 * PropertyException
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class PropertyException extends \Exception{
    
    /**
     * 发生异常的类名称
     * @var string
     */
    private $className = null;
    /**
     * 发生异常的属性名称
     * @var string
     */
    private $propertyName = null;
    
    public function __construct($message, $code=0, $previous=null) {
	parent::__construct($message, $code, $previous);
    }
    
    /**
     * 设置发生异常的类名称
     * @param string $className
     * @return \zuji\PropertyException
     */
    public function setClassName($className) {
	$this->className = $className;
	return $this;
    }
    /**
     * 获取异常的类名称
     * @return string
     */
    public function getClassName(){
	return $this->className;
    }
    

    /**
     * 设置发生异常的属性名称
     * @param string $propertyName
     * @return \zuji\PropertyException
     */
    public function setPropertyName($propertyName) {
	$this->propertyName = $propertyName;
	return $this;
    }
    /**
     * 获取异常的属性名称
     * @return string
     */
    public function getPropertyName(){
	return $this->propertyName;
    }
    
}
