<?php
/**
 * Sign_MD5
 * MD5加密
 *
 */

class Sign_MD5 extends Sign_Sign
{
    
    /**
     * 创建MD5签名
     * @see Sign_Sign::createSign()
     */
    public function createSign($params, $key)
    {
        $string = $this->toString($params);
        return md5($string . $key);
    }
    
    /**
     * 验证MD5签名
     * @see Sign_Sign::verifySign()
     */
    public function verifySign($params, $key, $sign)
    {
        $string = $this->toString($params);
        $sign = (string) $sign;
        return $sign === md5($string . $key);
    }

} 