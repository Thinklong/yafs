<?php
/**
 * Sign_MD5
 * MD5加密
 *
 */

class Sign_HMACMD5 extends Sign_Sign
{
    const ALGO = 'md5';
    
    /**
     * 创建HMACMD5签名
     * @see Sign_Sign::createSign()
     */
    public function createSign($params, $key)
    {
        $string = $this->toString($params);
        return hash_hmac(self::ALGO, $string, $key);
    }
    /**
     * 验证HMACMD5签名
     * @see Sign_Sign::verifySign()
     */
    public function verifySign($params, $key, $sign)
    {
        $string = $this->toString($params);
        $sign = (string) $sign;
        return $sign === hash_hmac(self::ALGO, $string, $key);
    }
}