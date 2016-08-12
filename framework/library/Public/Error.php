<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Error.php
 * 
 * @author     yulong8@leju.com
 */

class Public_Error
{
    /**
     * 基本错误类型
     */
    const SUCCESS = 1;    // 操作成功
    const FAIL = 0;    // 操作失败
    const ERR_PARAM = -100; // 参数错误
    const ERR_SIGN = -101; // 签名错误
    const ERR_WHITE_LIST = -102;     // IP拒绝访问
    

    /**
     * 业务错误类型
     * 错错误码长度为 4 位
     * 不同业务请增加1000位
     * 如： -1001 和 -2001
     */
    const ERR_INFO_NOT_EXISTS = -1001;    // 信息不存在


    
    
    /*
     * 短息验证相关错误码
     */
    const ERR_CHECK_VCODE_FAILED = -2501;    // 短信校验失败
    const ERR_OVER_CHECK_VCODE_TIMES = -2502;    // 短信校验失败
    const ERR_GET_VCODE_FAILED = -2503; //获取用户验证码失败，请重新验证

    /**
     * 错误消息响应
     */
    private static $_errMsg = [
        // 基础错误
        self::SUCCESS => ['成功', 'success'],
        self::FAIL => ['失败', 'fail'],
        self::ERR_PARAM => ['参数错误', 'parameter is error'],
        self::ERR_SIGN => ['签名错误', 'sign is error'],
        self::ERR_WHITE_LIST => ['IP拒绝访问', 'ip deny'],
        
        // 实例错误信息
        self::ERR_INFO_NOT_EXISTS => ['信息不存在', 'info isn\'t exists'],
        
        //其他业务请在下面对照写中英文错误信息

    ];    
    
    public static function msg($errno, $lang = 'en')
    {
        $lang = 'zh' === $lang ? 0 : 1;
        return self::$_errMsg[$errno][$lang];
    }
}
