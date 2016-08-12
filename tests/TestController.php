<?php
/**
 * TestController
 * 测试入口
 *
 */
define('APP_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_TEST_PATH', __DIR__);

/**
 * 项目配置
 */
define('APP_CONFIG', APP_PATH . 'configs' . DIRECTORY_SEPARATOR);

/**
 * 加载项目本地配置
 */
file_exists(APP_CONFIG . 'local.php') or die("Error: the app local config is missing...");
require_once APP_CONFIG . 'local.php';

/**
 * 设置时区
 */
date_default_timezone_set('PRC');

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
Usage: php TestController.php -t <testfile> [-m method]
    
    -t 测试文件相对路径(根目录是tests) 

    用例：
    php TestController.php -t paycenter/AppDoPayTest.php [-m testPay]

EOF;
}

function parse_params()
{
    global $argv;
    
    $lists = ["-t", "-m"];
    $data = ["-t" => null, "-m" => null];
    
    foreach ($argv as $key => $value) {
        if (in_array($value, $lists)) {
            $data[$value] = empty($argv[$key + 1]) ? null : $argv[$key + 1];
        }
    }

    return $data;
}

$cli_params = parse_params();

class TestController {

    private $app;

    public function __construct() {
        $this->app = new Yaf_Application(APP_PATH . 'configs/application.ini', APP_ENVRION);
        $this->app->bootstrap();
    }

    public function runTest($testFile, $testMethod = null) {
        $file =  __DIR__ . '/' . $testFile;

        if (false == file_exists($file) || false == is_file($file)) {
            echo "PATH : $file not exists." . PHP_EOL;
            exit;
        }

        $className = basename($testFile, ".php");
        if (false == class_exists($className, false)) {
            Yaf_Loader::import($file);
        }
        
        $testMethod && 'test' !== substr($testMethod, 0, 4) && ($testMethod = 'test' . ucfirst($testMethod));
        if ($testMethod && !method_exists($className, $testMethod)) {
            echo "METHOD : {$testMethod} not exists in {$className} class." . PHP_EOL;
            exit;
        }

        $class = new $className();

        $reflection = new ReflectionClass($class);

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
        
            if (strncmp($method->name, 'test', 4) == 0) {
                if ($testMethod && $testMethod !== $method->name) {
                    continue;
                }
                
                echo PHP_EOL . "=== start run test $method->name ===" . PHP_EOL;
                $method->invoke($class);

                echo PHP_EOL . "=== end  run test $method->name ===" . PHP_EOL;
                
                if ($testMethod) {
                    break;
                }
            }
        }
    }
} 

$controller = new TestController() ;

$controller->runTest($cli_params["-t"], $cli_params["-m"]);
