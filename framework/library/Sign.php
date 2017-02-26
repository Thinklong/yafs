<?php
/**
 * Sign
 * 签名验证工厂类
 *
 *
 */

class Sign
{

    const TYPE_RSA = 1;
    const TYPE_HMAC_MD5 = 2;
    const TYPE_NORMAL_MD5 = 3;
    const TYPE_AES = 4;

    public static function factory($type = Sign::TYPE_HMAC_MD5)
    {

        switch($type)
        {
            case self::TYPE_HMAC_MD5:
                return new Sign_HMACMD5();
            case self::TYPE_RSA:
                return new Sign_RSA();
            case self::TYPE_AES:
                return new Sign_AES();
            case self::TYPE_NORMAL_MD5:
                return new Sign_MD5();
            default:
                throw new Exception('unkown sign type');
        }

    }
    
}
