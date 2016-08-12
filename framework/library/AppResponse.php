<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * AppResponse.php
 * 
 * @author     thinklong89@gmail.com
 */

class AppResponse
{
    static protected $statusTexts = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => '(Unused)',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );

    /**
    * Sets response status code.
    *
    * @param string $code  HTTP status code
    * @param string $name  HTTP status text
    *
    */
    public static function statusCode($code, $text = null)
    {
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        $text = (null === $text) ? self::$statusTexts[$code] : $text;
        $status = "$protocol $code $text";
        header($status);
    }

    /**
     * Set session
     * @param string $name
     * @param string $value
     * @return boolean
     */
    public static function session($name, $value = null)
    {
        isset($_SESSION) || session_start();
        if ($name) {
            $_SESSION[$name] = $value;
            if (is_null($value)) {
                unset($_SESSION['name']);
            }
        }

        return true;
    }

    /**
     * Set cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param string $secure
     * @param boolean $httpOnly
     * @return boolean
     */
    public static function cookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = false, $httpOnly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Set response charset
     *
     * @param string $enc
     * @param string $type
     */
    public static function charset($enc = 'UTF-8', $type = 'text/html')
    {
        header("Content-Type:$type;charset=$enc");
    }

    /**
     * Redirect to other url
     *
     * @param string $url
     */
    public static function redirect($url, $code = 302)
    {
        header("Location:$url", true, $code);
        exit();
    }

    /**
     * Alert
     *
     * @param string $text
     * @param string $url
     */
    public static function alert($text, $url = null)
    {
        $text = addslashes($text);
        echo "\n<script type=\"text/javascript\">\nalert(\"$text\");\n";
        if ($url) {
            echo "window.location.href=\"$url\";\n";
        }
        echo "</script>\n";
        if ($url) exit();
    }

    /**
     * Forces the user's browser not to cache the results of the current request.
     *
     * @return void
     * @access protected
     * @link http://book.cakephp.org/view/431/disableCache
     */
    public static function disableBrowserCache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Etag
     *
     * Set or check etag
     * @param string $etag
     * @param boolean $notModifiedExit
     */
    public static function etag($etag, $notModifiedExit = true)
    {
        if ($notModifiedExit && isset($_SERVER['HTTP_IF_NONE_MATCH']) && $etag == $_SERVER['HTTP_IF_NONE_MATCH']) {
            self::statusCode('304');
            exit();
        }
        header("Etag: $etag");
    }

    /**
     * Last modified
     *
     * @param int $modifiedTime
     * @param boolean $notModifiedExit
     */
    public static function lastModified($modifiedTime, $notModifiedExit = true)
    {
        $modifiedTime = date('D, d M Y H:i:s \G\M\T', $modifiedTime);
        if ($notModifiedExit && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $modifiedTime == $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
            self::statusCode('304');
            exit();
        }
        header("Last-Modified: $modifiedTime");
    }

    /**
     * Expires
     *
     * @param int $seconds
     */
    public static function expires($seconds = 1800)
    {
        $time = date('D, d M Y H:i:s', time() + $seconds) . ' GMT';
        header("Expires: $time");
    }
    
    public static function response($errno, $result = null, $params = null)
    {
        $start = defined("START_TIME") ? START_TIME : AppRequest::instance()->getServer("REQUEST_TIME");
        $response = new stdClass();
        if (null === $errno || is_array($errno))
        {
            $response = null === $errno ? (empty($result) ? [] : (object) $result) : (object) $errno;
        }
        else
        {
            $response->error = $errno;
            $response->cost = microtime(true) - $start;
            $response->msg = Public_Error::msg($errno);
            if (null !== $result)
            {
                $response->result = $result;
            }
        }
        
        if (!empty($params) && (is_array($params) || is_object($params)))
        {
            foreach ($params as $key => $value)
            {
                $response->$key = $value;
            }
        }
        
        self::log($errno, $response);
        
        switch (strtoupper(Yaf_Registry::get("responseFormat")))
        {
            case 'XML':
                self::response_xml($response);
                break;
            case 'JSONP':
                self::response_jsonp($response);
                break;
            default :
                self::response_json($response);
        }
        exit;
    }
    
    private static function response_json($response)
    {
        header("Content-Type: application/json");
        echo json_encode($response);
    }
    
    private static function response_xml($response)
    {
        header('Content-Type: text/xml');
        echo self::array2xml($response, 'response');
    }
    
    private static function response_jsonp($response)
    {
        header("Content-Type: application/json");
        $func = AppRequest::instance()->getParam('jsoncallback');
        $func or $func = 'jsoncallback';
        echo $func, '(', json_encode($response), ')';
    }

    private static function array2xml($response, $rootNodeName = null)
    {
        if (isset($rootNodeName)) {
            $str = '<?xml version="1.0" encoding="utf-8"?><' . $rootNodeName . '>';
        } else {
            $str = '';
        }
        foreach ($response as $k => $v) {
            if (is_numeric($k)) {
                $stag = 'item id="' . $k . '"';
                $etag = 'item';
            } else {
                $stag = $etag = $k;
            }

            if (is_array($v)) {
                $str .= '<' . $stag . '>' . self::array2xml($v) . '</' . $etag . '>';
            } else {
                $str .= '<' . $stag . '>' . strtr($v, array('<' => '&lt;', '>' => '&gt;', '&' => '$amp;', '\'' => '&apos;', '"' => '&quot;')) . '</' . $etag . '>';
            }
        }
        if (isset($rootNodeName)) {
            $str .= '</' . $rootNodeName . '>';
        }

        return $str;
    }
    
    public static function log($errno, $response)
    {
        //var_dump($errno, $response, time());
        //sleep(1);
        if ((int) $errno > 0) {
            return false;
        }
        
        $debug = debug_backtrace();
        $class = $debug[2]['class'];
        $function = $debug[2]['function'];
        $line = $debug[1]['line'];
        unset($debug);
        
        $date = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ? $_SERVER['REQUEST_TIME'] : TIMESTAMP);
        $request = $_SERVER["REQUEST_URI"];
        $response = $response ? json_encode($response) : 'null';
        $method = $_SERVER['REQUEST_METHOD'];
        $content = "[ERROR]\t[{$date}]\t{$class}::{$function} on line {$line}\terror:{$errno}\t{$method} request:{$request}\tresponse:{$response}";
        AppLog::error($content, 'POST' === $method ? $_POST : ('GET' === $method ? $_GET : ($_POST + $_GET)));
    }
    
}
