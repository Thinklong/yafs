<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Domain.php
 * 
 * @author     yulong8@leju.com
 */

class Handle_Domain
{
    const HOST_KEY = 'host';
    
    const IP_KEY = 'ip';
    
    const DOMAIN_FILE = 'domain.ini';
    
    const CACHE_KEY = '_domain_config';
    
    private static $_role = APP_ENVRION;
    
    private static function _loadDomainConfig()
    {
        if (Yaf_Registry::has(self::CACHE_KEY)) {
            return Yaf_Registry::get(self::CACHE_KEY);
        }
        
        $arrConfig = new Yaf_Config_Ini(PATH_FW_CONFIG . self::DOMAIN_FILE);
        Yaf_Registry::set(self::CACHE_KEY, $arrConfig->get(self::$_role));
        
        return $arrConfig->get(self::$_role);
    }
    
    public static function setAppRole($role = APP_ENVRION)
    {
        $role or $role = APP_ENVRION;
        self::$_role = $role;
        return __CLASS__;
    }
    
    public static function getHost($module, $default = null)
    {
        if (!$module) {
            return $default;
        }

        $config = self::_loadDomainConfig();
        if (!(($config = $config->get($module)) && ($host = $config->get(self::HOST_KEY)))) {
            return $default;
        }

        return $host;
    }
    
    public static function getIp($module, $default = null)
    {
        if (!$module) {
            return $default;
        }
        
        $config = self::_loadDomainConfig();
        if (!(($config = $config->get($module)) && ($host = $config->get(self::IP_KEY)))) {
            return $default;
        }
        
        return $host;
    }
}