<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Config.php
 * 
 * @author     yulong8@leju.com
 */

class Handle_Config
{
    private static $_config = null;
    
    private static $_staticConfig = [];
    
    private static $_load = [];

    private function __construct()
    {
        ;
    }
    
    public static function load($filename, $reload = false)
    {
        if (!is_file($filename)) {
            $filename = ('.php' !== strrchr($filename, '.')) ? ($filename . '.php') : $filename;
            $filename = PATH_FW_CONFIG . $filename;//basename($filename, '.php') . '.php';
        }

        $key = md5($filename);
        if (true !== $reload && !empty(self::$_load[$key])) {
            return self::$_load[$key];
        }

        self::$_load[$key] = null;
        if (file_exists($filename)) {
            self::$_load[$key] = require_once $filename;
        }
        
        return self::$_load[$key];
    }

    private static function _loadConfig()
    {
        if (null !== self::$_config) {
            return self::$_config;
        }
        
        if ('product' === APP_ENVRION) {
            $configFile = isset($_SERVER['PATH_CONFIG']) ? trim($_SERVER['PATH_CONFIG']) : '';
        } else {
            $configFile = PATH_FW_CONFIG . 'config.ini';
        }

        $appEnv = APP_ENVRION;
        file_exists($configFile) or die("Global configure[{$appEnv}] is missing...");
        
        $iniObject = new Yaf_Config_Ini($configFile);
        self::$_config = $iniObject->toArray();
        unset($iniObject);

        return self::$_config;
    }
    
    public static function get($key, $explode = false, $separator = ',')
    {
        if (empty($key)) {
            return null;
        }
        
        if (isset(self::$_staticConfig[$key])) {
            $value = self::$_staticConfig[$key];
            if (true === $explode) {
                $value = self::explode($value, $separator);
            }
            return $value;
        }
        
        $keys = explode(".", trim($key));
        $value = self::_loadConfig();
        
        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                $value = null;
            }
            
            if (null === $value) {
                break;
            }
        }
        
        self::$_staticConfig[$key] = $value;

        if (true === $explode) {
            $value = self::explode($value, $separator);
        }
        
        return $value;
    }

    public static function explode($string, $separator = ',')
    {
        if (empty($separator) || false === strpos($string, $separator)) {
            return $string;
        }

        $array = explode($separator, $string);
        shuffle($array);

        return trim(current($array));
    }
}