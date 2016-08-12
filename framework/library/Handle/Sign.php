<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Sign.php
 * 
 * @author     thinklong89@gmail.com
 */

defined("APP_ENVRION") or die("Envrion error.");

class Handle_Sign
{
    const CACHE_KEY = '_secret_config';
    
    const PUB_KEY = 'pubkey';
    
    const PRI_KEY = 'prikey';
    
    const WHITE_KEY = 'whitelist';
    
    const BLACK_KEY = 'blacklist';
    
    const MODULES_KEY = 'modules';
    
    const SECRET_FILE = 'secret.php';
    
    private static $_config = null;
    
    private static function _loadSecretConfig()
    {
        if (null !== self::$_config) {
            return self::$_config;
        }
        
        if ('product' === APP_ENVRION) {
            $filename = APP_ENVRION . DIRECTORY_SEPARATOR . self::SECRET_FILE;
        } else {
            $filename = self::SECRET_FILE;
        }
        
        self::$_config = Handle_Config::load($filename);

        return self::$_config;
    }
    
    /**
     * createSign
     * 生成签名串
     * 
     * @param array $data
     * @param string $algo
     * @param string $pubkey
     * @return string|boolean
     */
    public static function createSign(array $data, $algo = '', $pubkey = '', $prikey = '')
    {
        if (empty($data['pub_key']) && !$pubkey) {
            return false;
        }
        
        $pubkey or ($pubkey = $data['pub_key']);
        $algo or ($algo = $data['sign_type']);
        
        $config = self::_loadSecretConfig();
        if (!$prikey) {
            if (!$pubkey || !$algo || !isset($config[$pubkey]) || !($secret = $config[$pubkey])) {
                return false;
            }

            if (!isset($secret[self::PRI_KEY]) || !($prikey = $secret[self::PRI_KEY])) {
                return false;
            }
        }

        $data['sign_type'] = $algo;
        unset($data['sign']);
        ksort($data);
        reset($data);
        
        $string = $glue = '';
        foreach ($data as $key => $value) {
            if ('0' !== (string)$value && empty($value)) {
                continue;
            }
            $string .= "{$glue}{$key}={$value}";
            $glue = '&';
        }

        return hash_hmac($algo, $string, $prikey);
    }
    
    /**
     * verifySign
     * 验证签名串
     * 
     * @param array $data
     * @param string $algo
     * @param string $sign
     * @param string $secret
     * @return boolean
     */
    public static function verifySign(array $data, $algo, $sign, $seckey)
    {
        if (!$seckey || !$data || !$algo || !$sign) {
            return false;
        }
        
        unset($data['sign']);
        
        ksort($data);
        reset($data);
        
        $string = $glue = '';
        foreach ($data as $key => $value) {
            if ('0' !== (string)$value && empty($value)) {
                continue;
            }

            $string .= "{$glue}{$key}={$value}";
            $glue = '&';
        }

        $sign = (string) $sign;

        return $sign === hash_hmac($algo, $string, $seckey);
    }
    
    public static function getPubKey($pubkey)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !isset($config[$pubkey]) || !($secret = $config[$pubkey])) {
            return false;
        }
        
        if (!isset($secret[self::PUB_KEY]) || !($pubkey = $secret[self::PUB_KEY])) {
            return false;
        }
        
        return $pubkey;
    }
    
    /**
     * 根据公钥获取私钥
     * 
     * @param string $pubkey
     * @return string
     */
    public static function getPriKey($pubkey, $defaul = null)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !isset($config[$pubkey]) || !($secret = $config[$pubkey])) {
            return $defaul;
        }
        
        if (!isset($secret[self::PRI_KEY]) || !($prikey = $secret[self::PRI_KEY])) {
            return $defaul;
        }
        
        return $prikey;
    }
    
    public static function getWhiteList($pubkey, $default = null)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !isset($config[$pubkey]) || !($secret = $config[$pubkey])) {
            return $default;
        }
        
        if (!isset($secret[self::WHITE_KEY]) || !($whitelist = $secret[self::WHITE_KEY])) {
            return $default;
        }
        
        return $whitelist;
    }
    
    public static function getBlackList($pubkey, $default = null)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !isset($config[$pubkey]) || !($secret = $config[$pubkey])) {
            return $default;
        }
        
        if (!isset($secret[self::BLACK_KEY]) || !($blacklist = $secret[self::BLACK_KEY])) {
            return $default;
        }
        
        return $blacklist;
    }
    
    /**
     * 验证 IP
     * 
     * @param string $pubkey
     * @return boolean
     */
    public static function verifyIp($pubkey)
    {
        $result = !self::checkIsBlacklist($pubkey) && self::checkIsWhitelist($pubkey);
        
        return $result;
    }
    
    /**
     * 验证 IP 白名单
     * 
     * @param string $pubkey
     * @return boolean
     */
    public static function checkIsWhitelist($pubkey)
    {
        $whiteList = self::getWhiteList($pubkey);

        if (!$whiteList || 'allow' === $whiteList) {
            $result = true;
        } else if ('deny' === $whiteList) {
            $result = false;
        } else if (!preg_match("/[\-\*]/", $whiteList)) {
            $clientIp = AppRequest::instance()->clientIp();
            $result = in_array($clientIp, explode(',', $whiteList));
        } else {
            $whiteList = explode(',', $whiteList);
            $clientIp = AppRequest::instance()->clientIp();
            foreach ($whiteList as $posIp) {
                $result = self::_checkIp($clientIp, $posIp);
                if (true === $result) {
                    break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 验证 IP 黑名单
     * 
     * @param string $pubkey
     * @return boolean
     */
    public static function checkIsBlacklist($pubkey)
    {
        $blackList = self::getBlackList($pubkey);

        if (!$blackList || 'none' === $blackList) {
            $result = false;
        } else if (!preg_match("/[\-\*]/", $blackList)) {
            $clientIp = AppRequest::instance()->clientIp();
            $result = in_array($clientIp, explode(',', $blackList));
        } else {
            $blackList = explode(',', $blackList);
            $clientIp = AppRequest::instance()->clientIp();
            foreach ($blackList as $posIp) {
                $result = self::_checkIp($clientIp, $posIp);
                if (true === $result) {
                    break;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 检测 IP 段
     * 
     * @param string $clientIp
     * @param string $posIp
     * @return boolean
     */
    private static function _checkIp($clientIp, $posIp)
    {
        if (3 !== substr_count($posIp, '.')) {
            return false;
        }

        // 检测 IP 范围段，如 192.168.100.1-100
        if (preg_match("/\d{1,3}\-\d{1,3}/", $posIp)) {
            $parse1 = explode('.', $clientIp);
            $parse2 = explode('.', $posIp);
            foreach ($parse2 as $key => &$value) {
                if (false === strpos($value, '-')) {
                    continue;
                }
                
                $range = explode('-', $value);
                $start = min($range);
                $end = max($range);
                $result = ($parse1[$key] >= $start && $parse1[$key] <= $end);
                $value = $result ? $parse1[$key] : 0;
            }
            
            $posIp = implode('.', $parse2);
        }
        
        // 检测 IP 任意段，如 192.168.1.*
        if (false !== strpos($posIp, '*')) {
            $posIp = preg_replace("/\*/", "\d{1,3}", $posIp);
        }
        
        $pattern = preg_replace('/\./', '\.', $posIp);
        $result = !!preg_match("/{$pattern}/", $clientIp);
        
        return $result;
    }
}

?>
