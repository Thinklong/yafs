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
     * 获取签名类型
     * @param unknown $signType
     * @return number
     */
    final private static function getSignType($signType)
    {
        switch ($signType)
        {
            case 'HMAC_MD5':
                return Sign::TYPE_HMAC_MD5;
            case 'MD5':
                return Sign::TYPE_NORMAL_MD5;
            case 'RSA':
                return Sign::TYPE_RSA;
            default:
                return Sign::TYPE_HMAC_MD5;
        }
    }
    
    /**
     * 创建签名
     * @param string $sginType
     * @param array $params
     * @param string $pubKey
     * @param string $priKey
     * @return boolean|string
     */
    public static function cSign($signType, array $params, $pubKey = '', $priKey = '')
    {
        if (empty($params['pub_key']) && !$pubKey)
        {
            return false;
        }
        
        $pubKey or ($pubKey = $params['pub_key']);
        $config = self::_loadSecretConfig();
        if (!$priKey)
        {
            if (!$pubKey || !isset($config[$pubKey]) || !($secret = $config[$pubKey]))
            {
                return false;
            }
        
            if (!isset($secret[self::PRI_KEY]) || !($priKey = $secret[self::PRI_KEY]))
            {
                return false;
            }
        }
        
        $sign_obj = Sign::factory(self::getSignType($signType));
        return $sign_obj->createSign($params, $priKey);
    }
    
    
    
    /**
     * 验证签名
     * @param string $sginType
     * @param array $params
     * @param unknown $sign
     * @param unknown $secKey
     * @return boolean
     */
    public static function vSign($signType, array $params, $sign, $secKey)
    {
        if (!$secKey || !$params || !$sign)
        {
            return false;
        }
        $sign_obj = Sign::factory(self::getSignType($signType));
        return $sign_obj->verifySign($params, $secKey, $sign);
    }
    
    
    
    
    
    /**
     * createSign
     * 生成签名串
     * 
     * @param array $params
     * @param string $pubkey
     * @return string|boolean
     */
    public static function createSign(array $params, $pubkey = '', $prikey = '')
    {
        if (empty($params['pub_key']) && !$pubkey)
        {
            return false;
        }
        
        $pubkey or ($pubkey = $params['pub_key']);
        
        $config = self::_loadSecretConfig();
        if (!$prikey)
        {
            if (!$pubkey || !isset($config[$pubkey]) || !($secret = $config[$pubkey]))
            {
                return false;
            }

            if (!isset($secret[self::PRI_KEY]) || !($prikey = $secret[self::PRI_KEY]))
            {
                return false;
            }
        }
        
        $sign = Sign::factory(Sign::TYPE_HMAC_MD5);
        return $sign->createSign($params, $prikey);
    }
    
    /**
     * verifySign
     * 验证签名串
     * 
     * @param array $params
     * @param string $sign
     * @param string $secret
     * @return boolean
     */
    public static function verifySign(array $params, $sign, $seckey)
    {
        if (!$seckey || !$params || !$sign)
        {
            return false;
        }
        $sign = Sign::factory(Sign::TYPE_HMAC_MD5);
        return $sign->verifySign($params, $seckey, $sign);
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
