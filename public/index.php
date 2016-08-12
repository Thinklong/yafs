<?php

/**
 *
 * index.php
 *
 * @author     thinklong89@gmail.com
 */
/**
 * 根目录
 */
define('PATH_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);

/**
 * 项目名称
*/
define('APP_NAME', 'yafs');

/**
 * 项目路径
*/
define('APP_PATH', dirname(PATH_ROOT)  . DIRECTORY_SEPARATOR);

/**
 * 项目配置
*/
define('APP_CONFIG', APP_PATH . 'configs' . DIRECTORY_SEPARATOR);

/**
 * 加载项目本地配置
*/
file_exists(APP_CONFIG . 'local.php') or die("Error: the app local config is missing...");
require_once APP_CONFIG . 'local.php';

try {
    /**
     * 默认的, Yaf_Application将会读取配置文件中在php.ini中设置的ap.environ的配置节
     * 另外在配置文件中, 可以替换PHP的常量, 比如此处的APPLICATION_PATH
     */
    $application = new Yaf_Application(APP_CONFIG . 'application.ini', APP_ENVRION);
    //defined("PATH_CUSTOM_FW_LIBRARY") and Yaf_Loader::getInstance()->setLibraryPath(PATH_CUSTOM_FW_LIBRARY, true);
    //Yaf_Dispatcher::getInstance()->catchException(false);
    //Yaf_Dispatcher::getInstance()->throwException(false);
    //Yaf_Dispatcher::getInstance()->setErrorHandler(['AppResponse', 'log']);
    /* 如果没有关闭自动response(通过Yaf_Dispatcher::getInstance()->returnResponse(TRUE)),
     * 则$response会被自动输出, 此处也不需要再次输出Response
    */
    $application->bootstrap()->run();
} catch (Exception $e) {
    echo $e;
}
