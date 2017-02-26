<?php
$app = null;
date_default_timezone_set('PRC');
define('APP_NAME', 'task'); // 项目名称
define('APP_PATH', realpath(dirname(__DIR__)) . '/'); //项目基础目录
define('PATH_FW_LIBRARY', APP_PATH . 'framework/library'); //框架lib库目录
define('PATH_CUSTOM_FW_LIBRARY', APP_PATH . 'framework/library'); //框架lib库目录
define("PATH_FW_CONFIG", dirname(rtrim(PATH_FW_LIBRARY, '/\/')) . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR); //框架配置目录

define('APP_ENVRION', 'beta');

// 部分业务配置
define("DEFAULT_PAGE_LIMIT", 10);
include APP_PATH . "library/Base/Worker.php";