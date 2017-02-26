<?php


/**
 * 加密Cookie类
 * 
 *
 */
class Cookie
{
    
    private static $sign;
    private static $key = 'K2Ho89h(j3e';
    
    private function __construct()
    {
        ;
    }
    
    /**
     * 获取并解密Cookie
     * @param unknown $name
     * @return boolean|string
     */
    public static function get($name)
    {
        if (!isset($_COOKIE[$name]))
        {
            return false;
        }
        $value = $_COOKIE[$name];
        self::$sign = Sign::factory(Sign::TYPE_AES);
        return self::$sign->decrypt($value, self::$key);
    }
    
    /**
     * 设置加密Cookie
     * @param unknown $name
     * @param string $value
     * @param number $expire
     * @param string $path
     * @param string $domain
     * @param string $secure
     * @param string $httponly
     */
    public static function set($name, $value = null, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        
        self::$sign = Sign::factory(Sign::TYPE_AES);
        $v = self::$sign->createSign($value, self::$key);
        
        setcookie($name, $v, $expire, $path, $domain, $secure, $httponly);
    }
    
}