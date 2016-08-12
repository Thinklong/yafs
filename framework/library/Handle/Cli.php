<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Cli.php
 * 
 * @author     yulong8@leju.com
 */

class Handle_Cli
{
    public static function initialize()
    {
        /**
         * 检测是否为 cli 模式
         */
        if (empty($_SERVER['argv']) || empty($_SERVER['argc'])) {
            return;
        }
        
        self::parseFastCGIParams();
    }
    
    /**
     * 解析服务器配置变量
     * @return null
     */
    private static function parseFastCGIParams()
    {
        $filename = 'develop' == APP_ENVRION ? '/etc/nginx/fastcgi_params' : '';
        if (!self::_fileExists($filename)) {
            return;
        }

        $handle = fopen($filename, 'r');
        while (($line = fgets($handle))) {
            if (!($line = rtrim(trim($line), ';')) || false !== strpos($line, '$') || '#' === $line[0]) {
                continue;
            }
            $matches = null;
            preg_match("/^(\S+)\s+(\S+)\s+(\S+)$/", $line, $matches);
            if (!isset($matches[2]) || !isset($matches[3])) {
                continue;
            }
            $_SERVER[strtoupper($matches[2])] = trim($matches[3], "'\"");
        }
        fclose($handle);
    }
    
    /**
     * 检测文件是否存在
     * @param type $filename
     * @return type
     */
    private static function _fileExists($filename)
    {
        return $filename && file_exists($filename) && is_readable($filename);
    }
}