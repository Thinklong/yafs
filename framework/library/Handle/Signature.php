<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Signature.php
 * 
 * @author     yulong8@leju.com
 */

defined("APP_ENVRION") or die("Envrion error.");

class Handle_Signature
{
    const CACHE_KEY = '_secret_config';
    
    const PUB_KEY = 'pubkey';
    
    const PRI_KEY = 'prikey';
    
    const WHITE_KEY = 'whitelist';
    
    const BLACK_KEY = 'blacklist';
    
    const MODULES_KEY = 'modules';
    
    const SECRET_FILE = 'secret.ini';
    
    private static function _loadSecretConfig()
    {
        if (Yaf_Registry::has(self::CACHE_KEY)) {
            return Yaf_Registry::get(self::CACHE_KEY);
        }
        
        $arrConfig = new Yaf_Config_Ini(PATH_FW_CONFIG . self::SECRET_FILE);
        $arrConfig = $arrConfig->get(APP_ENVRION);
        Yaf_Registry::set(self::CACHE_KEY, $arrConfig);
        
        return $arrConfig;
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
    public static function createSign(array $data, $algo, $pubkey = '')
    {
        if (empty($data['pub_key']) && !$pubkey) {
            return false;
        }
        
        $pubkey or ($pubkey = $data['pub_key']);
        
        $config = self::_loadSecretConfig();
        if (!($secret = $config->get($pubkey)) || !($seckey = $secret->get(self::PRI_KEY))) {
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

        return hash_hmac($algo, $string, $seckey);
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
    
    /**
     * 根据公钥获取私钥
     * 
     * @param string $pubkey
     * @return string
     */
    public static function getPriKey($pubkey, $defaul = null)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !($secret = $config->get($pubkey)) || !($prikey = $secret->get(self::PRI_KEY))) {
            return $defaul;
        }
        
        return $prikey;
    }
    
    public static function getWhiteList($pubkey, $defaul = null)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !($secret = $config->get($pubkey)) || !($whitelist = $secret->get(self::WHITE_KEY))) {
            return $defaul;
        }
        
        return $whitelist;
    }
    
    public static function getBlackList($pubkey, $default = null)
    {
        $config = self::_loadSecretConfig();
        if (!$pubkey || !($secret = $config->get($pubkey)) || !($blacklist = $secret->get(self::BLACK_KEY))) {
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

        if (!$whiteList) {
            $result = true;
        } else if ('allow' === $whiteList) {
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

        if (!$blackList) {
            $result = false;
        } else if ('none' === $blackList) {
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
