<?php
namespace zuji\certificate;
/**
 * Creater
 *
 * @author liuhongxing
 */
class Creater {
    
    /**
     * 创建一个证书
     * @return \certificate\Certificate
     * @throws \Exception
     */
    public static function create( $keyBits = 1024 ){
        try {
            $config = array(
                "digest_alg" => "sha1",
                "private_key_bits" => $keyBits,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            );
            // Create the keypair
            $res = openssl_pkey_new( $config );
            
            // Get private key
            $b = openssl_pkey_export($res, $privatekey);
            if( $b === false ){
                $msg = 'openssl_pkey_export() false: '. \openssl_error_string();
                //\basic\util\debug\Debug::error($msg);
                throw new \Exception( $msg );
            }
            // Get public key
            $publickey = openssl_pkey_get_details($res);
            if( !isset($publickey["key"]) ){
                $msg = 'openssl_pkey_get_details() false: '. \openssl_error_string();
                //\basic\util\debug\Debug::error($msg);
                throw new \Exception( $msg );
            }
            $publickey = $publickey["key"];
            
            // Free key
            openssl_pkey_free( $res );
            
            return new Certificate( $keyBits, $privatekey, $publickey );
            
        } catch (\Exception $exc) {
            //echo $exc->getTraceAsString();
            throw $exc;
        }
    }
    
}
