<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Error.php
 * 
 * @author     thinklong89@gmail.com
 */

class Public_Error
{
    /**
     * 基本错误类型
     */
    const SUCCESS = 1;    // 操作成功
    const FAIL = 0;    // 操作失败
    const NOT_MODIFY = 2;    // 没有修改
    const ERR_PARAM = -100; // 参数错误
    const ERR_SIGN = -101; // 签名错误
    const ERR_WHITE_LIST = -102;     // IP拒绝访问
    const ERR_CONTROLLER_NOT_FOUND = -103;     // 找不到控制器
    const ERR_ACTION_NOT_FOUND = -104;     // 找不到方法


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
     * 用户模块错误类型
     */
    const ERR_USER_NOT_EXISTED = -3000; //用户不存在
    const ERR_USER_DISABLED = -3001; //用户禁用
    const ERR_USER_WRONG_PASSWORD = -3002; //密码错误
    const ERR_USER_TIMES_OUT = -3003; //错误次数达到上限
    const ERR_USER_HAS_EXISTED = -3004; //用户名已存在
    const ERR_USER_NO_PRIVILEGE = -3005; //没有权限操作，请联系管理员开通
    const ERR_PASSWORD_FORMAT_ERROR = -3006; //用户密码格式错误
    const ERR_PASSWORD_SAME = -3007; //新旧密码一致
    const ERR_USER_HAVE_NO_SPECIAL_RIGHTS = -3008; //用户没有特殊权限


    /**
     * 角色模块错误类型
     */
    const ERR_ROLE_NOT_EXISTED = -4000; //角色不存在
    const ERR_ROLE_ID_EMPTY = -4001; //角色id不能为空
    const ERR_ROLE_HAS_EXISTED = -4002; //角色已存在


    /**
     * 组件模块错误类型
     */
    const ERR_COMPOSER_HAS_EXISTED = -5000; //组件已存在
    const ERR_COMPOSER_HAS_CONTROLLER = -5001; //组件下存在控制器,请先删除控制器

    /**
     * 缓存监控错误类型
     */
    const ERR_CACHE_KEY_NOT_EXISTED = -6000; //搜索的key不存在

    /**
     * ctl资源错误类型
     */

    const ERR_NOT_EXIST_CTL = -7000;
    const ERR_HAVE_NO_ACTIONS_IN_CTL = -7001;
    const ERR_HAVE_REPEAT_CTL_NAME = -7002;

    /**
     * 数据库操作错误类型
     */
    const ERR_SQL_NO_SELECT = -8000;
    const ERR_SQL_NO_LIMIT = -8001;
    const ERR_SQL_NOT_EXISTS = -8002;
    const ERR_EXEC_SQL_OUT = -8003;
    const ERR_EXEC_LOG_INSERT = -8004;



    /**
     * 错误消息响应
     */
    private static $_errMsg = [
        // 基础错误
        self::SUCCESS => ['成功', 'success'],
        self::FAIL => ['失败', 'fail'],
        self::NOT_MODIFY => ['数据没有修改', 'Data is not modified'],
        self::ERR_PARAM => ['参数错误', 'parameter is error'],
        self::ERR_SIGN => ['签名错误', 'sign is error'],
        self::ERR_WHITE_LIST => ['IP拒绝访问', 'ip deny'],
        self::ERR_CONTROLLER_NOT_FOUND => ['找不到控制器', 'controller not found'],
        self::ERR_ACTION_NOT_FOUND => ['找不到方法', 'action  not found'],

        // 实例错误信息
        self::ERR_INFO_NOT_EXISTS => ['信息不存在', 'info isn\'t exists'],

        //用户模块
        self::ERR_USER_NOT_EXISTED => ['用户不存在', 'user is not exists'],
        self::ERR_USER_DISABLED => ['当前用户不可用', 'user is denied'],
        self::ERR_USER_TIMES_OUT => ['错误次数达到上限', 'error times is out'],
        self::ERR_USER_WRONG_PASSWORD => ['用户密码错误', 'password is wrong'],
        self::ERR_USER_HAS_EXISTED => ['用户名已存在', 'username has existed'],
        self::ERR_USER_NO_PRIVILEGE => ['没有权限操作，请联系管理员开通', 'have no privilege'],
        self::ERR_PASSWORD_FORMAT_ERROR => ['密码格式错误', 'password format error'],
        self::ERR_PASSWORD_SAME => ['新密码和旧密码一致', 'password is same'],
        self::ERR_USER_HAVE_NO_SPECIAL_RIGHTS => ['用户没有特殊权限', 'user have no special rights'],

        //角色
        self::ERR_USER_WRONG_PASSWORD => ['角色不存在', 'wrong password'],
        self::ERR_ROLE_ID_EMPTY => ['角色id不能为空', 'role id is not empty'],
        self::ERR_ROLE_HAS_EXISTED => ['角色已存在', 'role has existed'],

        //组件
        self::ERR_COMPOSER_HAS_EXISTED => ['组件已存在', 'composer has existed'],
        self::ERR_COMPOSER_HAS_CONTROLLER => ['组件下存在控制器,请先删除控制器', 'composer has controller'],

        //缓存
        self::ERR_CACHE_KEY_NOT_EXISTED => ['搜索的key不存在', 'search key not existed'],

        // ctl资源管理

        self::ERR_NOT_EXIST_CTL => ['该控制器不存在', 'ctl is not existed'],
        self::ERR_HAVE_NO_ACTIONS_IN_CTL => ['该控制器内没有任何方法', 'There is no actions in this ctl'],
        self::ERR_HAVE_REPEAT_CTL_NAME => ['已经存在同名的方法', 'A method with the same name already exists in this ctl'],

        //数据库操作
        self::ERR_SQL_NO_SELECT => ['请填写正确的sql查询语句', 'wrong sql'],
        self::ERR_SQL_NO_LIMIT => ['请填写查询条数', 'no limit'],
        self::ERR_SQL_NOT_EXISTS => ['数据库或数据表不存在', 'db or table not exists'],

        self::ERR_EXEC_SQL_OUT => ['超出sql执行范围', 'out of permission'],
        self::ERR_EXEC_LOG_INSERT => ['写入执行log失败', 'Write execute log failed'],
        //其他业务请在下面对照写中英文错误信息


    ];

    public static function msg($errno, $lang = 'zh')
    {
        $lang = 'zh' === $lang ? 0 : 1;
        return self::$_errMsg[$errno][$lang];
    }
}
