<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * AppHttp.php
 * 
 * @author     yulong8@leju.com
 */

class AppHttp
{
    /**
     * Default params
     *
     * @var array
     */
    public static $defaultParams = array(
        'headers' => array(),
        'timeout' => 15,
        'ssl'     => false,
        'opts'    => array(),
    );

    /**
     * Curl Http Info
     *
     * @var array
     */
    public static $info = array();

    /**
     * HTTP GET
     *
     * @param string $url
     * @param array $data
     * @param array $params
     * @return string
     */
    public static function get($url, $data = array(), $params = array())
    {
        if ($data) {
            $queryStr = http_build_query($data);
            $url .= (false !== strpos($url, '?') ? '&' : '?') . "{$queryStr}";
        }

        return self::request($url, $params);
    }

    /**
     * HTTP POST
     *
     * @param string $url
     * @param array $data
     * @param array $params
     * @return string
     */
    public static function post($url, $data, $params = array())
    {
        $params['opts'][CURLOPT_POST]       = true;
        $params['opts'][CURLOPT_POSTFIELDS] = isset($params['json']) && $params['json'] ? json_encode($data) : http_build_query($data);
        return self::request($url, $params);
    }

    /**
     * HTTP request
     *
     * @param string $uri
     * @param array $params
     * @return string or throw Exception
     */
    public static function request($url, $params)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Can not find curl extension');
        }

        $curl = curl_init();
        $opts = self::initOpts($url, $params);
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);

        $errno = curl_errno($curl);
        $error = curl_error($curl);

        self::$info = curl_getinfo($curl) + array('errno' => $errno, 'error' => $error);

        if (0 !== $errno) {
            throw new Exception($error, $errno);
        }

        curl_close ($curl);
        return $response;
    }

    /**
     * Init curl opts
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public static function initOpts($url, $params)
    {
        $params += self::$defaultParams;
        $opts = $params['opts'] + array(
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => $params['timeout'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $params['ssl'],
            CURLOPT_USERAGENT      => 'm.leju.com PHP5 Client ver: ' . phpversion(),
        );

        if ($params['headers']) {
            $opts[CURLOPT_HTTPHEADER] = $params['headers'];
        }

        return $opts;
    }
}