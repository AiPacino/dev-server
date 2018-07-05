<?php
namespace zuji\certificate;

/**
 * Encrypter
 * 加密器
 * @author liuhongxing
 */
interface Encrypter {
    
    /**
     * 加密字符串
     * @param string    $text       待加密字符串
     * @return string   加密结果字符串
     */
    public function encrypt( $text );
    
    /**
     * 加密签名
     * @param string $text 加密字符串
     * @return string   签名结果字符串
     */
    public function sign( $text );
}
