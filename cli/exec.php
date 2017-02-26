<?php
define('FILE_NAME', __FILE__);
define('BASEPATH', getCliBasePath(__FILE__));
include BASEPATH . "/cli/local.php";
$worker = new Worker();
$worker->count = 1;
$worker->onProcess = function ()
{
    $daoCron = new Dao_Manager_CronModel();
    $redis = new Base_Redis('redis');
    
    
    while (true)
    {
        pcntl_signal_dispatch();
        $val = $redis->lPop('mf_cron_execute_queue');
        var_dump($val);exit;
        if (!$val)
        {
            sleep(10);
            continue;
        }
        $act = str_replace('_', '/', $val['exec_act']);
        $command = 'nohup php ' . BASEPATH . "/cli/{$val['exec_mod']}/{$act}" . ' start > /dev/null 2>&1 & echo $!';
        
        exec($command, $op);
        $pid = (int) $op[0];
        
        
        $data = [
            'status' => 2,
        ];
        $daoCron->update($val['id'], $data);
        
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
