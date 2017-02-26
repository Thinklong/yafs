<?php
/**
 * Config
 * 获取配置工具类
 *
 * 使用说明：
 *      如果在application.ini 中有如下配置
 *          domain.gateway.host = 'yulong.m.leju.com';
 *      获取此配置可以通过Config::get('config.domain.gateway.host') 或者Config::get('domain.gateway.host');
 *
 */
class Config
{

    private static $_config;
    private function __construct()
    {
        self::$_config = null;
    }
    public static function get($key)
    {
        if (!self::$_config)
        {
            self::$_config = self::getConfig();
        }
        
        return self::$_config->get($key);
    }
    
    public static function getConfig()
    {
        return new Config_Yaf();
    }
}
