<?php
/**
 * Sign_MD5
 * MD5加密
 *
 */

class Sign_HMACMD5 extends Sign
{

    protected function doSign($signStr, $privateKey)
    {
        $sign = hash_hmac('md5', $signStr, $privateKey);
        return $sign;
    }
} 