<?php
/**
 * Sign_AES
 * AES-CBC加密
 *
 */

class Sign_AES extends Sign_Sign
{
    private $iv = 'kLn298sS7yHjeDhw';
    
    public function createSign($params, $key)
    {
        $string = $this->toString($params);
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $this->iv);
        return base64_encode($encrypted);
    }
    
    public function verifySign($params, $key, $sign)
    {
    }
    
    public function decrypt($sign, $key)
    {
        $encrypted = base64_decode($sign);
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $encrypted, MCRYPT_MODE_CBC, $this->iv);
    }
}