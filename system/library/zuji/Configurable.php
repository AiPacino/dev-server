<?php
namespace zuji;

/**
 * Configurable 可配置类
 * 非private属性才可以配置
 * 【注意：】
 *      继承规则：
 *      1）子类属性访问控制权限最小为 protected，（子类的private属性，在父类方法中无法设置）
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Configurable {
    
    /**
     * 构造函数
     * @param array $data 属性数组
     * @see config()
     */
    public function __construct(Array $data=[]) {
	if( is_array($data) ){
	    $this->config($data);
	}
    }
    
    /**
     * 配置对象的属性
     * @param array $params
     * <p><b>要求：</b></p>
     * <ul>
     * <li> 数组必须是关联数组</li>
     * <li> 数组的键：必须是类中存在的属性</li>
     * <li> 数组的值：将作为类的属性值</li>
     * </ul>
     * @param mixed $throwException  异常处理标识 默认为：true
     * <p>true:   抛出异常</p>
     * <p>其他情况： 通过参数的引用特性，将异常赋予实参</p>
     */
    public function config( $params, &$throwException=true ){
        $temp = true;
        $exc = &$throwException;
        foreach( $params as $property=>$v ){
            if( $throwException === true ){// 抛出异常时
                $temp = true;// 还原标识为 抛出异常
            }else{
                $temp = false;
            }
            if( !$this->setProperty($property, $v, $temp)  ){
                $exc = $temp;
            }
        }
        $throwException = $exc;
        $this->init();// 配置成功后的初始化工作
        return true;
    }
    /**
     * 配置成功后的初始化工作
     * config()成功配置结束时触发
     */
    protected function init(){
        
    }


    /**
     * 设置属性值（非private属性）
     * @param string $property	    属性名称
     * @param mixed $value	    属性值
     * @param mixed $throwException 异常处理标识
     * @return boolean		    是否设置成功
     * @throws PropertyException
     */
    public function setProperty( $property, $value, &$throwException=true ){
        // 确认属性是否存在
        if( !$this->_ensure_property($property, $throwException) ){
            return false;
        }
        // 属性赋值
        $this->$property = $value;
        
        return true;
    }
    
    /**
     * 获取属性值（非private属性）
     * @param string $property
     * @param mixed $throwException
     * @return mixed 属性存在，返回属性值；属性不存在，如果指定了$throwException，则返回NULL，如果没有指定$throwException或$throwException===true，则抛出异常
     * @throws PropertyException
     */
    public function getProperty( $property, &$throwException=true ){
        // 检查属性是否存在
        if( !$this->_ensure_property($property, $throwException) ){
            return NULL;
        }
        // 返回属性值
        return $this->$property;
    }
    
    /**
     * 判断实现是否定义
     * @param string $property
     * @return boolean true：属性存在；false：属性不存在
     */
    public function propertyExists( $property ){
        // 检查属性是否存在
        if( !property_exists($this, $property) ){
            return false;
        }
        return true;
    }
    
    /**
     * 确定属性符合可配置性(1.存在，2，非private修饰)
     * @param string $property
     * @param mixed $throwException 抛出异常标识
     *  true:   抛出异常
     *  其他情况： 通过参数的引用特性，将异常赋予实参
     * @return boolean true：符合；false：不符合
     * @throws PropertyException
     * 
     * 例如：假设当前对象没有非private的t1属性，可以用以下两种方式处理
     * ->_ensure_property('t1','123');      // 抛出异常
     * ->_ensure_property('t1','123',$exc); // 返回false，并将当前的异常赋予实参$exc
     * 
     * 例如：假设当前对象存在非private的t2属性，
     * ->_ensure_property('t1','123');      // 返回 true
     * ->_ensure_property('t1','123',$exc); // 返回 true，并将NULL赋予实参$exc
     * 
     */
    private function _ensure_property( $property, &$throwException=true ){
        // 异常或错误标识
        $flagExc = false;
        // 提示内容
        $msg = '属性异常';
        try {
            $class = get_class($this);
            // 属性反射
            $reflection = new \ReflectionProperty( $class, $property );// 可能抛出“类不存在该属性”的异常
            // 判断属性访问修饰符，必须是非private属性，否则不符合可配置性
            if( $reflection->isPrivate() ){
                $flagExc = true;
                $msg = 'Cannot access private property '. $class .':$'.$property;
            }
        } catch (\ReflectionException $exc) {
            $flagExc = true;
            $msg = $exc->getMessage();
        }
        // 存在错误或异常
        if( $flagExc ){
            // 创建异常
            $temp = new PropertyException($msg);
            // 判断是否可以抛出，只有（$throwException === true）才抛出异常
            if( $throwException === true ){// 必须是全等
                throw $temp;
            }
            // 不抛出异常，通过引用实参$throwException，传递当前异常
            $throwException = $temp;
            // 返回结果，不符合
            return false;
        }
        // 不存在错误或异常，引用实参$throwException赋值为NULL
        $throwException = NULL;
        // 返回结果，符合
        return true;
    }
    
}