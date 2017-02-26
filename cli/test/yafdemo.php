<?php
define('FILE_NAME', __FILE__);
$basePath = getCliBasePath(__FILE__);
include $basePath . "/cli/local.php";
$worker = new Worker();
$worker->count = 3;
$worker->onProcess = function ()
{
    //echo Error::getErrorType(1) . "\n";
    //echo "onProcess\n";//exit(250);
    //$test = new Dao_App_AwardModel();
    //$a = $test->getAwardList(null, null, null);
    //var_dump($a);
    //sleep(10000);
    $i = 0;
    while (true)
    {
        $pid = getmypid();
        echo "pid:{$pid} ---->" . $i . "\n";
        sleep(1);
        $i++;
        if ($i > 100)
        {
            break;
        }
        if ($i > 10)
        {
            pcntl_signal_dispatch();
        }
    }
};


$app->execute(array('Worker', 'masterRun'));



/**
 * 获取项目基础目录
 * @param string $path
 * @return mixed
 */
function getCliBasePath($path = null)
{
    if (empty($path))
    {
        $path = realpath(__DIR__);
    }

    $pathinfo = pathinfo($path);
    if ($pathinfo['basename'] == 'cli' && is_dir($pathinfo['dirname'] . DIRECTORY_SEPARATOR . 'framework'))
    {
        return $pathinfo['dirname'];
    }
    return getCliBasePath($pathinfo['dirname']);
}
