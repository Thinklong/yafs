<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Handle.php
 * 公共基类静态方法
 * 
 * @author     yulong8@leju.com
 */

class Public_Handle
{
    /**
     * Post cURL
     *
     * @param string $url
     * @param string $host
     * @param array $data
     * @param array $options
     * @return string
     */
    public static function postInvoke($url, $host = '', $data = array(), array $options = array())
    {
        if (!empty($host)) {
            isset($options['headers']) or $options['headers'] = array();
            $options['headers'][] = 'Host: ' . $host;
        }
    
        return AppHttp::post($url, $data, $options);
    }
    
    /**
     * Get cURL
     *
     * @param string $url
     * @param string $host
     * @param array $data
     * @param array $options
     * @return string
     */
    public static function getInvoke($url, $host = '', $data = array(), array $options = array())
    {
        if (!empty($host)) {
            isset($options['headers']) or $options['headers'] = array();
            $options['headers'][] = 'Host: ' . $host;
        }
    
        return AppHttp::get($url, $data, $options);
    }
    
}