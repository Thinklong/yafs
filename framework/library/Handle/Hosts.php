<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Hosts.php
 * 
 * @author     yulong8@leju.com
 */

class Handle_Hosts
{
    const HOST_KEY = 'host';
    
    const IP_KEY = 'ip';
    
    const HOSTS_FILE = 'hosts.php';
    
    private static $_role = APP_ENVRION;
    
    private static $_config = null;

    private static function _loadHostsConfig()
    {
        if (null !== self::$_config) {
            return self::$_config;
        }
        
        self::$_config = Handle_Config::load(self::HOSTS_FILE);

        return self::$_config;
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
        
        self::_loadHostsConfig();
        if (!isset(self::$_config[$module]) || !($hosts = self::$_config[$module])) {
            return $default;
        }
        if (!isset($hosts[self::HOST_KEY]) || !($host = $hosts[self::HOST_KEY])) {
            return $default;
        }
        
        return $host;
    }
    
    public static function getIp($module, $default = null)
    {
        if (!$module) {
            return $default;
        }
        
        self::_loadHostsConfig();
        if (!isset(self::$_config[$module]) || !($hosts = self::$_config[$module])) {
            return $default;
        }

        if (!isset($hosts[self::IP_KEY]) || !($ip = $hosts[self::IP_KEY])) {
            return $default;
        }
        
        return $ip;
    }
}