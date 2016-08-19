<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * local.php
 * 
 * @author     thinklong89@gmail.com
 */

// 时区
date_default_timezone_set('PRC');
define("START_TIME", microtime(true));
define('TIMESTAMP', substr(START_TIME, 0, 10));
define('CURRENT_DATETIME', date("Y-m-d H:i:s", TIMESTAMP));

// 项目环境
/**
 * 项目环境 默认采用预定义变量 设置当前项目环境$_SERVER['SERVER_ROLE'] 分为 develop、beta、product。
 * 如果使用预定义变量 必须严格使用以上变量名及值。
 * 
 * 由于服务器架构不同肯能你无法在web服务器上设置预定义变量，那么你可以使用另一种方法
 * 域名中解析当前项目环境 
 * 例如：dev.thinklong89.com、beta.thinklong89.com、thinklong89.com
 * 分别为develop、beta、product环境。
 * 
 */
isset($_SERVER['SERVER_ROLE']) && $_SERVER['SERVER_ROLE'] == 'develop' && define('APP_ENVRION', 'develop');
isset($_SERVER['SERVER_ROLE']) && $_SERVER['SERVER_ROLE'] == 'beta' && define('APP_ENVRION', 'beta');
isset($_SERVER['SERVER_ROLE']) && $_SERVER['SERVER_ROLE'] == 'product' && define('APP_ENVRION', 'product');

if (!isset($_SERVER['SERVER_ROLE']))
{
    strpos($_SERVER['HTTP_HOST'], 'dev') !== false && define('APP_ENVRION', 'develop');
    strpos($_SERVER['HTTP_HOST'], 'beta') !== false && define('APP_ENVRION', 'beta');
    strpos($_SERVER['HTTP_HOST'], 'dev') === false && strpos($_SERVER['HTTP_HOST'], 'beta') === false && define('APP_ENVRION', 'product');

}

// 错误级别
('product' === APP_ENVRION) && (false !== ini_set('display_errors', 'Off')) && error_reporting(0);
('product' === APP_ENVRION) || ((false !== ini_set('display_errors', 'On')) && error_reporting(E_ALL));

// 目录分隔符别名
define('DS', DIRECTORY_SEPARATOR);

$frameworkPath = ini_get("yaf.library");
//$frameworkPath or die("The library path config is missing in php.ini.");

// 自定义 framework/library 路径
file_exists(APP_PATH . 'framework/library/') and define('PATH_CUSTOM_FW_LIBRARY', APP_PATH . 'framework/library/');

// 设置 framework 基础路径
define("PATH_FW_LIBRARY", defined("PATH_CUSTOM_FW_LIBRARY") ? PATH_CUSTOM_FW_LIBRARY : (rtrim($frameworkPath, '/\/') . DS));
define("PATH_FW_CONFIG", dirname(rtrim(PATH_FW_LIBRARY, '/\/')) . DS . 'configs' . DS);

// 设置tools目录路径
define('PATH_FW_TOOLS', APP_PATH . 'framework/tools/');

// 项目内配置
defined("APP_CONFIG") or define('APP_CONFIG', dir(__FILE__) . DS);

// 日志记录路径
define("PATH_APP_LOG", empty($_SERVER['PATH_LOG']) ? '/tmp/applog' : (rtrim($_SERVER['PATH_LOG'], '//\/') . DS . 'applog'));

// 默认分页
define("DEFAULT_PAGE", 1);
define("DEFAULT_PAGE_LIMIT", 50);

// 开关类设置
define("SWITCH_SQL_LOG", true); // 返回SQL日志开关
define("SWITCH_APP_LOG", true); // AppLog 日志类开关

// KEY
define("KEY_REDIS_MEM_SERVICE", "finance");

// GLOBAL DEFINES
define("VAR_STATE_VALID",1);
define("VAR_STATE_INVALID",0);
