<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * AppRequest.php
 * 请求对象基类
 * 
 * @author     yulong8@leju.com
 */

class AppRequest
{
    private static $_instance = null;
    
    private $_http = null;
    
    private $_params = array();

    private function __construct()
    {
        $this->_http = new Yaf_Request_Http();
        $this->parseParams();
    }
    
    public static function instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public function __call($name, $arguments)
    {
        if (method_exists($this->_http, $name)) {
            return call_user_func_array(array($this->_http, $name), $arguments);
        }
        
        return null;
    }
    
    public function getParam($name, $default = null)
    {
        if ('GET' === strtoupper($this->_http->getMethod())) {
            // get
            $value = $this->_http->getQuery($name, $default);
        } else {
            // post
            $value = $this->_http->getPost($name, $default);
            // get
            $value or ($this->_http->getQuery($name, $default));
        }
        
        return $value;
    }
    
    public function getParams()
    {
        return $this->_params;
    }
    
    public function filterParams(array $filter, $method = '')
    {
        $method = strtoupper($method);
        
        $values = [];
        foreach ($filter as $key) {
            if ("POST" == $method) {
                $values[$key] = $this->getPost($key);
            } else if ("GET" == $method) {
                $values[$key] = $this->getQuery($key);
            } else {
                $values[$key] = isset($this->_params[$key]) ? $this->_params[$key] : null;
            }
        }
        
        return $values;
    }
    
    private function parseParams()
    {
        // post + get
        $params = array_merge($this->_http->getQuery(), $this->_http->getPost());
        $this->_params = $params;
    }

    public static function isAjax()
    {
        return ('XMLHttpRequest' == self::header('X_REQUESTED_WITH'));
    }

    public static function isFlashRequest()
    {
        return ('Shockwave Flash' == self::header('USER_AGENT'));
    }

    public static function isSecure()
    {
        return ('https' === self::scheme());
    }
    
    public static function isSpider($ua = null)
    {
        is_null($ua) && $ua = $_SERVER['HTTP_USER_AGENT'];
        $ua = strtolower($ua);
        $spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
        foreach ($spiders as $spider) {
            if (false !== strpos($ua, $spider)) return true;
        }
        return false;
    }

    public static function scheme()
    {
        return ('on' == self::server('HTTPS')) ? 'https' : 'http';
    }

    public static function clientIp($default = '0.0.0.0')
    {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) continue;
		    $ips = explode(',', $_SERVER[$key], 1);
		    $ip = $ips[0];
		    $l  = ip2long($ip);
		    if ((false !== $l) && ($ip === long2ip($l))) return $ip;
		}

        return $default;
    }
}
