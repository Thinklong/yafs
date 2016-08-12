<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * AppLog.php
 * 
 * @author     yulong8@leju.com
 */

define("APP_LOG_MODE_DEBUG", 1);
define("APP_LOG_MODE_WARN", 2);
define("APP_LOG_MODE_ERROR", 3);
defined("PATH_APP_LOG") or define("PATH_APP_LOG", "/tmp/applog");

/**
 *
 * 调用方式：
 * PLog::debug($incsowjd, '',);
 * PLog::warn($incsowjd, '');
 * PLog::error($incsowjd, '');
 */
class AppLog
{

    private static $_regenerate_id = null;
    
    private function __construct()
    {
        ;
    }

    /**
     * 调试日志
     * 
     * @param string $value1 数据1
     * @param string $value2 数据2
     * @return boolean
     */
    public static function debug($value1, $value2 = '')
    {
        $logInfo = self::_getLogInfo();
        return self::_writeLog(APP_LOG_MODE_DEBUG, $logInfo->file, $logInfo->line, $value1, $value2, $logInfo->func);
    }

    /**
     * 警告日志
     * 
     * @param string $value1 数据1
     * @param string $value2 数据2
     * @return boolean
     */
    public static function warn($value1, $value2 = '')
    {
        $logInfo = self::_getLogInfo();
        return self::_writeLog(APP_LOG_MODE_WARN, $logInfo->file, $logInfo->line, $value1, $value2, $logInfo->func);
    }

    /**
     * 错误日志
     * 
     * @param string $value1 数据1
     * @param string $value2 数据2
     * @return boolean
     */
    public static function error($value1, $value2 = '')
    {
        $logInfo = self::_getLogInfo();
        return self::_writeLog(APP_LOG_MODE_ERROR, $logInfo->file, $logInfo->line, $value1, $value2, $logInfo->func);
    }

    /**
     * 写LOG的函数
     *
     * @param string $mode 日志类型
     * @param string $file 文件名
     * @param string $lineNo 行
     * @param string $value1
     * @param string $value2
     * @param string $func 函数名
     */
    private static function _writeLog($mode, $file, $lineNo, $value1, $value2 = '', $func = 'MAIN')
    {
        $logFile = self::_getLogFileName($file, $mode);
        $logMemo = self::_getLogMemo($lineNo, $value1, $value2, $func);

        return self::_writeFile($logFile, $logMemo);
    }

    /**
     * 返回 文件名、行号和函数名
     * @param $skipLevel
     */
    private static function _getLogInfo($skipLevel = 1)
    {
        $traceInfo = debug_backtrace();
        if (($skipLevel = intval($skipLevel))) {
            $traceInfo = array_slice($traceInfo, $skipLevel);
        }

        if (empty($traceInfo)) {
            return false;
        }

        $firstLevel = array_shift($traceInfo);
        $secondLevel = empty($traceInfo) ? ["function" => "MAIN"] : array_shift($traceInfo);

        $function = isset($secondLevel['class']) ? ($secondLevel['class'] . $secondLevel['type'] . $secondLevel['function']) : $secondLevel['function'];
        unset($traceInfo, $secondLevel);

        $std = new stdClass();
        $std->line = $firstLevel['line'];
        $std->file = $firstLevel['file'];
        $std->func = $function;

        return $std;
    }

    /**
     * 获取LOG文件名称（带路径）
     *
     * @param string $file 文件名
     * @param string $mode 日志类型
     * @return string LOG文件名称（带路径）
     */
    private static function _getLogFileName($file, $mode)
    {
        $mode = intval($mode);
        switch ($mode) {
            case APP_LOG_MODE_DEBUG:
                $mode = 'DEBUG';
                break;
            case APP_LOG_MODE_WARN:
                $mode = 'WARN';
                break;
            case APP_LOG_MODE_ERROR:
                $mode = 'ERROR';
                break;
            default:
                $mode = 'OTHER';
                break;
        }

        $date = date("Ymd");
        $ip = ($ip = filter_input(INPUT_SERVER, "SERVER_ADDR")) ? str_replace(".", "_", $ip) : "0_0_0_0";

        $logpath = self::_getLogPath($file);

        if (empty($file)) {
            $logfile = "unknown_{$ip}_{$date}";
        } else {
            $basename = ($basename = str_replace(".", "_", basename($file, ".php"))) ? $basename : "unknown";
            $basename = strtolower($basename) . "_{$ip}_{$date}";
            $logfile = $date . DIRECTORY_SEPARATOR . $mode . DIRECTORY_SEPARATOR . $logpath . $basename . '_log.txt';
        }

        return $logfile;
    }
    
    /**
     * 获取日志基础目录
     * 
     * @param string $file 文件
     * @return string
     */
    private static function _getLogPath($file)
    {
        $path = dirname($file);
        if (0 === strpos($path, APP_PATH)) {
            $base = APP_NAME . DIRECTORY_SEPARATOR;
            $path = substr($path, strlen(APP_PATH));
        } else if (0 === strpos($path, dirname(PATH_FW_LIBRARY))) {
            $base = 'libs' . DIRECTORY_SEPARATOR;
            $path = substr($path, strlen(dirname(PATH_FW_LIBRARY)));
        } else {
            $base = 'unknown' . DIRECTORY_SEPARATOR;
        }
        $path = $base . strtr(trim($path, "\\/"), ["." => "_", "\/" => "_", "/" => "_"]);
        return strtolower($path) . '_';
    }

    /**
     * 获取每一行的Log字符串
     * 每行的分隔符是：\t\t\n
     * Head的分隔符是：空格
     * Head和Log之间的分隔符是：\t
     * Log1和Log2之间的分隔符是：" \t "(注意前后的空格)
     *
     * 如果Log中有回车，回记录两次，其中一次是替换回车的(用于grep)，另外一次是记录的原始信息
     */
    private static function _getLogMemo($lineNo, $value1, $value2 = '', $func = 'MAIN')
    {
        if (null === self::$_regenerate_id) {
            self::$_regenerate_id = self::_regenerateId();
            $session = self::$_regenerate_id;
        } else {
            $session = self::$_regenerate_id;
        }

        $session = substr($session, 0, 6);
        $time = microtime(true);
        list($sec, $usec) = explode(".", $time);
        $date = date("H:i:s", $sec) . "." . str_pad($usec, 4, "0", STR_PAD_RIGHT);

        $logMemo = $date . " [{$session} " . getmypid() . "]\t[{$func}][LN:{$lineNo}]";

        $ret1 = self::_formatValue($logMemo, $value1);
        $ret2 = self::_formatValue($logMemo, $value2);

        $logMemo .= "\t";
        $ret1 and ($logMemo .= "\n" . $value1);
        $ret2 and ($logMemo .= "\n" . $value2);

        return $logMemo;
    }

    private static function _formatValue(&$subject, $string)
    {
        if (empty($string)) {
            $ret = false;
        } else if (is_string($string)) {
            $string = trim($string);
            $subject .= "\t" . (($ret = (false !== strpos($string, "\n"))) ? strtr($string, ["\r\n" => "\t", "\n" => "\t"]) : $string);
        } else {
            $ret = false;
            $subject .= "\t" . serialize($string);
        }

        return $ret;
    }

    private static function _writeFile($filename, $content)
    {
        if (!defined("PATH_APP_LOG")) {
            die("The applog path constant is not defined.");
        }
        
        try {
            $logfile = rtrim(PATH_APP_LOG, "\//") . DIRECTORY_SEPARATOR . $filename;
            self::makeDirs(dirname($logfile));
            $result = file_put_contents($logfile, $content . "\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }
    
    private static function _regenerateId($length = 6)
    {
        return substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 3)), 0, $length);
    }

    private static function makeDirs($dir, $mode = 0777)
    {
        if (!is_dir($dir)) {
            self::makeDirs(dirname($dir), $mode);
            $result = mkdir($dir, $mode);
            //$result and $result = chown($dir, "nobody");
            //$result and $result = chgrp($dir, "nobody");
            return $result;
        }
        return true;
    }

}