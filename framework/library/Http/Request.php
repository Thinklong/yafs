<?php
/**
 * Http_Request
 * http 请求类
 *
 */

class Http_Request {

    private $_method = 'GET';
    private $_url;
    private $_header;
    private $_connection_time = 5;
    private $_timeout = 2;
    private $_ms = false;
    private $_error ;

    public function __construct() {
        $this->_header = null;
        $this->_error = null;
    }

    public function setUrl($url) {
        $this->_url = $url;
    }

    public function setHeader($header = null) {
        if ($header) {
            $this->_header = array('Host: ' . $header);
        }
    }

    public function setTime($connectionTime = 5, $timeout = 2, $ms = false) {
        $this->_connection_time = $connectionTime;
        $this->_timeout = $timeout;
        $this->_ms = $ms;
    }

    public function request($url = '', array $get = array(), array $post = array(), array $opt = array()) {

        if ($url) {
            $this->_url = $url;
        }

        if (empty($this->_url)) {
            throw new Exception('not set url');
        }

        $postString = null;
        $getString = null;
        if (!empty($post)) {
            $this->_method = 'POST';
            foreach ($post as $key => $val) {
                $postParams[] = $key . '=' . rawurlencode($val);
            }
            $postString = implode('&', $postParams);
        }

        if (!empty($get)) {
            foreach ($get as $k => $v) {
                $getParams[] = $k . '=' . rawurlencode($v);
            }
            $getString = implode('&', $getParams);
            if (strpos('?', $url) !== false) {
                $this->_url .= '&' . $getString;
            } else {
                $this->_url .= '?' . $getString;
            }
        }
        $body = $this->send($postString, $opt);

        return $body;
    }

    public function send($postString = '', array $opt = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        if ($this->_header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_header);
        }
        if ($this->_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_connection_time);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'm.leju.com PHP5 Client ver: ' . phpversion());
        if ($this->_ms) {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
        }

        if (!empty($opt)) {
           foreach ($opt as $key => $val) {
               curl_setopt($ch, $key, $val);
           }
        }

        $result = curl_exec($ch);
        if (false == $result) {
            $this->_error = curl_error($ch);
        }
        curl_close($ch);

        return $result;
    }

    public function getError() {
        return $this->_error;
    }
}