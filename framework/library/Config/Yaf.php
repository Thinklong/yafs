<?php

/**
 * Config_Yaf
 * 通过yaf框架获取配置
 *
 */
class Config_Yaf
{

    public static function get($key)
    {
        $keys = explode('.', trim($key));

        $configFile = APP_CONFIG . 'application.ini';
        file_exists($configFile) or die("application configure is missing...");
        
        $iniObject = new Yaf_Config_Ini($configFile, APP_ENVRION);
        $config = $iniObject->toArray();
        unset($iniObject);
        foreach ($keys as $key)
        {
            if (isset($config[$key]))
            {
                $config = $config[$key];
            } else {
                $config = null;
            }
            
            if (null === $config)
            {
                break;
            }
        }
        return $config;
    }

}
