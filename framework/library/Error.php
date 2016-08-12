<?php
class Error
{
    
    /**
     * 错误类型
     * @var unknown
     */
    public static $type = [
        1 => "E_ERROR",
        2 => "E_WARNING",
        4 => "E_PARSE",
        8 => "E_NOTICE",
        16 => "E_CORE_ERROR",
        32 => "E_CORE_WARNING",
        64 => "E_COMPILE_ERROR",
        128 => "E_COMPILE_WARNING",
        256 => "E_USER_ERROR",
        512 => "E_USER_WARNING",
        1024 => "E_USER_NOTICE",
        2048 => "E_STRICT",
        4096 => "E_RECOVERABLE_ERROR",
        8192 => "E_DEPRECATED",
        16384 => "E_USER_DEPRECATED",
        30719 => "E_ALL",
    ];
    
    
    
    
    
    
    /**
     * 获取错误类型
     * @param unknown $type
     * @return Ambigous <string, unknown>
     */
    public static function getErrorType($type)
    {
        return isset(self::$type[$type]) ? self::$type[$type] : 'Unknown error'; 
    }
    
    /**
     * 错误处理
     * @param unknown $errNo
     * @param unknown $errStr
     * @param unknown $errFile
     * @param unknown $errLine
     * @return void|boolean
     */
    public static function errorHandler($errNo, $errStr, $errFile, $errLine)
    {
        if (!(error_reporting() & $errNo))
        {
            // This error code is not included in error_reporting
            return;
        }
        
        switch ($errNo)
        {
            case E_USER_ERROR:
                $errorinfo = "[ERROR] [$errNo] $errStr ";
                $errorinfo .= "  Fatal error on line $errLine in file $errFile";
                $errorinfo .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ") ";
                $errorinfo .= "Aborting...";
                break;
        
            case E_USER_WARNING:
                $errorinfo = "[WARNING] [$errNo] $errStr. Warning error on line $errLine in file $errFile ";
                break;
        
            case E_USER_NOTICE:
                $errorinfo = "[NOTICE] [$errNo] $errStr. Notice error on line $errLine in file $errFile";
                break;
        
            default:
                $type = self::getErrorType($errNo);
                $errorinfo = "[$type] [$errNo] $errStr. $type error on line $errLine in file $errFile";
                break;
        }
        if (APP_ENVRION == 'develop' || APP_ENVRION == 'beta')
        {
            echo $errorinfo . " <br/> \r\n";
        }
        AppLog::error($errorinfo);
        return true;
    }
    
    /**
     * 致命错误处理
     */
    public static function shutdownErrorHandler()
    {
        // 获取最后一条错误信息
        $e = error_get_last();
        if ($e && in_array($e['type'], array(1, 4, 16, 64, 256, 4096, E_ALL)))
        {
            $errorinfo = "[ERROR] [{$e['type']}] {$e['message']}.";
            $errorinfo .= " Fatal error on line {$e['line']} in file {$e['file']}";
            $errorinfo .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ") ";
            $errorinfo .= "Aborting...";
            AppLog::error($errorinfo);
        }
    }
    
}