<?php
namespace zuji\certificate;
/**
 * Certificate
 * 证书
 * 保存 公钥和私钥
 *
 * @author liuhongxing
 */
class Certificate {
    
    private $keyBits = 1024;    // 秘钥长度（位）
    
    // 私钥（已经格式化的值）
    private $privateKey = '';
    private $privateKeyResource = null;
    
    // 公钥（已经格式化的值）
    private $publicKey = '';
    private $publicKeyResource = null;
    
    public function __construct( $keyBits=0, $privateKey='', $publicKey='' ) {
        $this->keyBits = intval($keyBits);
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }
    
    public function getKeyBits(){
        return $this->keyBits;
    }
    public function setKeyBits( $keyBits ){
        $this->keyBits = intval($keyBits);
    }
    
    //-+------------------------------------------------------------------------
    // | 私钥
    //-+------------------------------------------------------------------------
    public function getPrivateKeyResource(){
        if( $this->privateKeyResource == null ){
            $this->privateKeyResource = \openssl_pkey_get_private( $this->privateKey );
        }
        return $this->privateKeyResource;
    }
    public function getPrivateKey(){
        return $this->privateKey;
    }
    public function setPrivateKey( $privateKey ){
        return $this->privateKey = $privateKey;
    }
    
    //-+------------------------------------------------------------------------
    // | 公钥
    //-+------------------------------------------------------------------------
    public function getPublicKeyResource(){
        if( $this->publicKeyResource == null ){
            $this->publicKeyResource = \openssl_pkey_get_public( $this->publicKey );
        }
        return $this->publicKeyResource;
    }
    public function getPublicKey(){
        return $this->publicKey;
    }
    public function setPublicKey( $publicKey ){
        return $this->publicKey = $publicKey;
    }
    
    
}
