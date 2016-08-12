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
        $arr = explode('.', $key);

        $config = Yaf_Registry::get('config');

        if (strtolower($arr[0]) == 'config') {
            array_shift($arr);
        }

        foreach ($arr as $val) {
            $config = $config->get($val);

            if (!$config)
                return null;
        }

        return $config;
    }

}
