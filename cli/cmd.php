<?php
define('APP_PATH', dirname(__DIR__));

if (count($argv) < 2) {
    usage();
    exit;
}

if ($argv[1] != '-t') {
    usage();
    exit;
}

function usage() {
    echo <<<EOF
Usage: php <tool.php/cron.php> -t <脚本名称> [params...]
    
    -t 脚本名称，与文件名保持一致 
    
    [params...]  脚本执行所需的参数 脚本通过this->_opt['paramName']获取

    用例：
    php tool.php -t ToolDemo -f file  

EOF;
}

$dir = APP_PATH . '/cli/' . CLI_CMD ;

$className = $argv[2];
$file = $dir . '/' . $className . '.php';

// 检查文件路径
if (false == file_exists($file) || false == is_file($file)) {
    echo "PATH : $file not exists" . PHP_EOL;
    exit;
}


$index = 0;
$params = [];

// 处理参数
for($i = 3; $i < count($argv);) {
    $arg = $argv[$i];
    if (strpos($arg, '-') === 0) {
        $arg = substr($arg,  strpos($arg, '_') + 1);
        $i++;
        $params[$arg] = $argv[$i++];
    } else {
        $params[$index++] = $argv[$i++];
    }
}

$app = new Yaf_Application(APP_PATH . '/configs/application.ini');
// 引入脚本类
if (false == class_exists($className, false)) {
    Yaf_Loader::import($file);
}

$class = new $className($params);
$app->execute(array($class, 'execute'));


