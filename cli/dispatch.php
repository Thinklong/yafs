<?php
define('FILE_NAME', __FILE__);
define('BASEPATH', getCliBasePath(__FILE__));
include BASEPATH . "/cli/local.php";
$worker = new Worker();
$worker->count = 1;
$worker->onProcess = function ()
{
    $daoCron = new Dao_Manager_CronModel();
    $serviceCron = new Service_Manager_CronModel();
    
    while (true)
    {
        pcntl_signal_dispatch();
        $condition = [
            'nxt_time' => strtotime(date('Y-m-d H:i')),
            'is_valid' => 1,
            'status' => ['IN', [1, 3]],
        ];
        $taskLists = $daoCron->getList($condition);
        if (!$taskLists)
        {
            continue;
        }
        foreach ($taskLists as $key => $val)
        {
            $act = str_replace('_', '/', $val['exec_act']);
            $command = 'nohup php ' . BASEPATH . "/cli/{$val['exec_mod']}/{$act}" . ' start > /dev/null 2>&1 & echo $!';
            
            exec($command, $op);
            $pid = (int) $op[0];
            
            $nxt_time = $pre_time = 0;
            if ($val['type'] == 1)
            {
                $nxt_time = $pre_time = $val['nxt_time'];
            }
            else if($val['type'] == 2)
            {
                $nxt_time = $serviceCron->nxtTimeHandler($val['nxt_time'], $val['period_type'], $val['period_interval']);
            }
            
            $data = [
                'nxt_time' => $nxt_time,
                'pre_time' => $val['nxt_time'],
                'status' => 2,
                'pid' => $pid,
            ];
            $daoCron->update($val['id'], $data);
        }
        
        sleep(10);
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
