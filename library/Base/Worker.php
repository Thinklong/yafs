<?php
/**
 * Cli Worker
 * 
 * 
 */

if (!defined("APP_PATH"))
{
    //必须加载cli下local.php配置文件
    die("Error: the task local config is missing...");
}
//yaf初始化
$app = new Yaf_Application(APP_PATH . '/configs/application.ini');
Yaf_Loader::getInstance()->setLibraryPath(PATH_FW_LIBRARY, true);
$localLiraryPath = Yaf_Loader::getInstance()->getLibraryPath();

$basePath = $localLiraryPath . DIRECTORY_SEPARATOR . 'Base' . DIRECTORY_SEPARATOR;
Yaf_Loader::getInstance()->import($basePath . 'Dao.php');
Yaf_Loader::getInstance()->import($basePath . 'Redis.php');



class Worker
{
    /**
     * Worker 常量
     */
    const STATUS_STARTING = 1;  //worker开始状态
    const STATUS_RUNING = 2;    //worker运行状态
    const STATUS_SHUTDOWN = 3;  //worker停止状态
    const STATUS_RELOADING = 4; //worker重启状态
    
    /**
     * 当前worker对象
     * @var obj
     */
    protected static $_worker;
    
    /**
     * 子进程pid集合
     * @var array
     */
    protected static $pidMap;
    
    /**
     * 重启子进程pid集合
     * @var unknown
     */
    protected static $reloadPids;
    
    /**
     * 主进程Pid
     * @var int
     */
    protected static $masterPid;
    
    /**
     * 当前状态
     * @var int
     */
    protected static $status = 1;
        
    /**
     * 锁文件
     * @var string
     */
    protected static $lockFile;
    
    /**
     * 锁文件句柄
     */
    private static $_fp = false;
    
    /**
     * 锁文件目录
     * @var string
     */
    private static $lockFileDir = '/tmp';
    
    /**
     * 当前Worker文件名称
     * @var string
     */
    private static $fileName;
    
    /**
     * 进程数
     * @var int
     */
    public $count = 1;
    
    /**
     * 回调业务方法
     * @var func
     */
    public $onProcess;
    
    
    /**
     * Worker 初始化
     */
    public function __construct()
    {
        self::$_worker = $this;
    }
    
    /**
     * 析构
     */
    public function __destruct()
    {
        /*if (self::$masterPid == posix_getpid())
        {
            //文件解锁
            self::unlock();
        }*/
    }
    
    
    /**
     * Worker 运行入口
     */
    public static function masterRun()
    {
        self::init();
        self::parseCmd();
        self::installSignal();
        self::fork();
        self::monitor();
        
    }
    
    public function run()
    {
        self::reinstallSignal();
        //self::$start = self::START_RUNING;
        //register_shutdown_function(['Worker', 'workerShutdown']);
        if ($this->onProcess)
        {
            try {
                call_user_func($this->onProcess, $this);
            } catch (Exception $e) {
                exit;
            }
        }
    }
    
    /**
     * 初始化
     */
    public static function init()
    {
        //状态初始化
        self::$status = self::STATUS_STARTING;
        //文件锁 
        $apppath = (string) APP_PATH . 'cli/';
        $filename = str_replace($apppath, '', FILE_NAME);
        $filename = str_replace('/', '_', $filename);
        self::$fileName = $filename;
        self::$lockFile = self::$lockFileDir . '/' . $filename . '.lock';
        //设置进程名称
        self::setProcessTitle("TaskCenter PHP Master Process {$filename}");
        //错误捕获及输出
        //register_shutdown_function(['Worker', 'masterShutdown']);
        
        self::$masterPid = posix_getpid();
    }
    
    /**
     * 解析命令参数
     */
    public static function parseCmd()
    {
        //检查命令格式
        global $argv;
        //$start_file = $argv[0];
        if(!isset($argv[1]))
        {
            echo "Command error\n";
            exit("Usage: php yourfile.php {start|stop|reload|status}\n");
        }
        $command = trim($argv[1]);
        $master_pid = is_file(self::$lockFile) ? file_get_contents(self::$lockFile) : false;
        $is_alive = $master_pid && posix_kill($master_pid, 0);
        if ($is_alive)
        {
            if ($command == 'start')
            {
                exit("TaskCenter master worker [" . self::$fileName ."] is runing...\n");
            }
        }
        elseif ($command !== 'start' && ($command == 'stop' || $command == 'status' || $command == 'reload'))
        {
            exit("TaskCenter master worker [" . self::$fileName . "] not run.\n");
        }
        
        switch ($command)
        {
            case 'start':
                self::lock();
                break;
            case 'stop':
                $master_pid && posix_kill($master_pid, SIGINT);
                exit("Task [" . self::$fileName . "] is stoping...\n");
                
                break;
            case 'reload':
                $master_pid && posix_kill($master_pid, SIGUSR1);
                exit("Task [" . self::$fileName . "] is reloading...\n");
                break;
            case 'status':
                
                exit();
                break;
            default:
                echo "Command error\n";
                exit("Usage: php yourfile.php {start|stop|reload|status}\n");
        }
    }
    
    /**
     * 安装信号
     */
    public static function installSignal()
    {
        // stop
        pcntl_signal(SIGINT,  array('Worker', 'signalHandler'), false);
        // reload
        pcntl_signal(SIGUSR1, array('Worker', 'signalHandler'), false);
        // status
        pcntl_signal(SIGUSR2, array('Worker', 'signalHandler'), false);
    }
    
    /**
     * 信号处理函数
     * @param int $signo
     */
    public static function signalHandler($signo)
    {
        switch ($signo)
        {
            case SIGINT:
                self::stop();
                break;
            case SIGUSR1:
                echo "接收到重启信号.";
                self::$reloadPids = self::$pidMap;
                self::reload();
                break;
            case SIGUSR2:
                self::status();
                break;
        }
    }
    
    /**
     * 子进程重新注册信号
     */
    private static function reinstallSignal()
    {
        pcntl_signal(SIGUSR2, SIG_IGN, false);
        pcntl_signal(SIGUSR1, SIG_IGN, false);
        pcntl_signal(SIGINT, array('Worker', "childSignalHandler"), false);
    }
    
    /**
     * 子进程信号处理函数
     * @param int $signo
     */
    public static function childSignalHandler($signo)
    {
        switch ($signo)
        {
            case SIGINT:
                self::childStop();
                break;
        }
    }
    
    /**
     * 循环创建子进程
     */
    protected static function fork()
    {
        while (count(self::$pidMap) < self::$_worker->count)
        {
            self::forkOneWorker(self::$_worker);
        }
    }
    
    protected static function forkOneWorker($worker)
    {
        $pid = pcntl_fork();
        //父进程和子进程都会执行下面代码
        if ($pid == -1)
        {
            //错误处理：创建子进程失败时返回-1.
            die('could not fork!');
        }
        else if ($pid)
        {
            //父进程会得到子进程号，所以这里是父进程执行的逻辑
            self::$pidMap[$pid] = $pid;
            
        }
        else
        {
            //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
            self::setProcessTitle("TaskCenter PHP Child Process {" . self::$fileName . "}");
            //sleep(1000);
            $worker->run();
            self::$status = self::STATUS_SHUTDOWN;
            exit(101);
        }
    }
    
    
    /**
     * 主进程守护
     */
    public static function monitor()
    {
        self::$status = self::STATUS_RUNING;
        while (true)
        {
            pcntl_signal_dispatch();
            $status = 0;
            $pid = pcntl_wait($status, WUNTRACED);
            pcntl_signal_dispatch();
            
            if ($pid > 0)
            {
                if (isset(self::$pidMap[$pid]))
                {
                    //记录状态
                    //踢出workers
                    unset(self::$pidMap[$pid]);
                }
                if ($status == 25856 && count(self::$pidMap) == 0)
                {
                    self::$status = self::STATUS_SHUTDOWN;
                    self::exitMaster();
                }
                else if ($status == 25856 && count(self::$pidMap) > 0)
                {
                    continue;
                }
                
                if (self::$status !== self::STATUS_SHUTDOWN)
                {
                    //重新启动子进程 保证{$this->count}数量的进程
                    self::fork();
                    if (isset(self::$reloadPids[$pid]))
                    {
                        unset(self::$reloadPids[$pid]);
                        self::reload();
                    }
                }
                else
                {
                    if (count(self::$pidMap) == 0)
                    {
                        //完全退出 清空状态
                        self::exitMaster();
                    }
                }
            }
            else 
            {
                //没有子进程
                //停止状态 并且所有子进程已经完全关闭 主进程退出
                if (self::$status === self::STATUS_SHUTDOWN && count(self::$pidMap) == 0)
                {
                    //完全退出 清空状态
                    self::exitMaster();
                }
            }
        }
    }
    
    public static function exitMaster()
    {
        self::unlock();
        //更细任务状态
        $Service_Manager_CronModel = new Service_Manager_CronModel();
        $r = $Service_Manager_CronModel->updateStatusByTaskName(self::$fileName);
        exit;
    }
    
    public static function stop()
    {
        self::$status = self::STATUS_SHUTDOWN;
        echo "The master process receives the signal. The child process exiting. \n";
        foreach (self::$pidMap as $pid)
        {
            posix_kill($pid, SIGINT);
        }
    }
    
    
    public static function reload()
    {
        self::$status = self::STATUS_RELOADING;
        foreach (self::$reloadPids as $pid)
        {
            posix_kill($pid, SIGINT);
        }
        if (empty(self::$reloadPids))
        {
            self::$status = self::STATUS_RUNING;
            return ;
        }
        
        
    }
    
    public static function status()
    {
        
    }
    
    public static function childStop()
    {
        //检查是否有stop回调函数
        exit;
    }
    
    
    /**
     * 主进程完全退出 清空状态
     */
    public static function masterShutdown()
    {
        self::$status = self::STATUS_SHUTDOWN;
        
        echo 'Worker Shutdown. all child process exit. Master process exit.'. PHP_EOL;
        self::unlock();
        exit;
    }
    
    /**
     * 设置进程标题
     * @param unknown $title
     */
    public static function setProcessTitle($title)
    {
        if (function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($title);
        }
        elseif (function_exists('setproctitle'))
        {
            @setproctitle($title);
        }
        
    }
    
    
    /**
     * lock
     * 加文件锁，防止脚本重复运行
     */
    protected static function lock()
    {
        if (is_file(self::$lockFile))
        {
            $pid = intval(file_get_contents(self::$lockFile));
            
            if (@posix_kill($pid, 0))
            {
                exit('another process is running. PID: ' . $pid);
            }
        }
    
        self::$_fp = fopen(self::$lockFile, 'w+');
        if (self::$_fp === false) {
            return false;
        }
    
        flock(self::$_fp, LOCK_EX);
        return fwrite(self::$_fp, getmypid());
    }
    
    /**
     * unlock
     *
     * 脚本执行完成后，对加锁的文件解锁
     */
    protected static function unlock()
    {
        if (self::$_fp !== false || is_file(self::$lockFile))
        {
            @flock(self::$_fp, LOCK_UN);
            @fclose(self::$_fp);
            return @unlink(self::$lockFile);
        }
        return false;
    }
    
    
    public static function workerShutdown()
    {
        self::$status = self::STATUS_SHUTDOWN;
        echo "\nworkerShutdown\n";
    }
    
}