<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Test.php
 * 
 * @author     thinklong89@gmail.com
 */

abstract class Base_Test
{
    /**
     * 公钥
     * 
     * @var string 
     */
    protected $pubKey = 'T001';
    
    /**
     * 模块名称
     * 
     * @var string 
     */
    protected $module = null;
    
    /**
     * 配置组角色
     */
    protected $appRole = APP_ENVRION;

    /**
     * 
     * @param string $api
     * @param array $data
     * @param string $method
     * @param mixed $result
     * @param string $response
     * @return string
     */
    protected function invoke($api, array $data, $method = "POST", &$result = null, $host = null, $response = 'json')
    {
        if (empty($this->pubKey) || empty($this->module)) {
            die("pubkey or module is null" . PHP_EOL);
        }
        
        empty($data['pub_key']) and $data['pub_key'] = $this->pubKey;
        empty($data['sign_type']) and $data['sign_type'] = 'MD5';
        empty($data['sign']) and $data['sign'] = Handle_Sign::createSign($data, $data['sign_type']);

        if (preg_match("/^(http[s]?:\/\/\S+)/i", $api) && null !== $host) {
            $url = $api;
        } else {
            $ip = Handle_Hosts::getIp($this->module);
            $host = Handle_Hosts::getHost($this->module);
            
            echo "ip: ", $ip, PHP_EOL, "host: ", $host, PHP_EOL;
            
            if (empty($ip) && empty($host)) {
                die("the module hosts is null..." . PHP_EOL);
            }
        
            if (!preg_match("/^(http[s]?:\/\/\S+)/i", $ip)) {
                $ip = "http://{$ip}";
            } else {
                $ip = rtrim($ip, "/");
            }

            $url = $ip . "/{$this->module}/" . $api . "." . trim($response, ".");
        }
        
        echo "url: ", $url, PHP_EOL;
        echo "request: ", http_build_query($data), PHP_EOL, PHP_EOL;
        
        $method = strtoupper($method);
        if ('POST' === strtoupper($method)) {
            $response = Premium_Handle::postInvoke($url, $host, $data);
        } else {
            $response = Premium_Handle::getInvoke($url, $host, $data);
        }

        $result = ($tmp = json_decode($response)) ? $tmp : $response;

        return $result;
    }
}
