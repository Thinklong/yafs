<?php
abstract class CliBase {
    
    /**
     * execute 
     * 
     * 脚本执行的逻辑处理
     */
    abstract public function execute();

    /**
     * _opt 
     * 
     * 脚本参数
     */
    protected $_opt;

    /**
     * state 
     * 
     * 运行状态，和信号相关
     */
    protected $state;

    /**
     * pidDir 
     *
     * 加文件锁时，存放pid目录
     */
    protected $pidDir = '/tmp';

    /**
     * _fp 
     * 
     * 文件锁句柄
     */
    private $_fp;
    protected $className ;
    public function __construct($params = array()) {
        $this->_opt = $params; 
        $this->state = true;
        $this->init();
    }

    public function init() {
    
    }
    /**
     * lock 
     * 
     * 加文件锁，防止脚本重复运行
     */
    public function lock() {
        $lockFile = $this->pidDir . "/$this->className.lock";
        if (is_file($lockFile)) {
            $pid = intval(file_get_contents($lockFile));
            if (is_dir('/proc/' . $pid)) {
                exit('another process is running. PID: ' . $pid);
            }
        }

        $this->_fp = fopen($lockFile, 'w+');

        if ($this->_fp === false) {
            return false;
        }

        flock($this->_fp, LOCK_EX);
        return fwrite($this->_fp, getmypid());
    }

    /**
     * unlock 
     * 
     * 脚本执行完成后，对加锁的文件解锁
     */
    public function unlock() {
     if ($this->_fp !== false) {
        flock($this->_fp, LOCK_UN);
        fclose($this->_fp);
        return unlink($this->pidDir . "/$this->className.lock");
     }
        return false; 
    }

    /**
     * log 
     * 
     * 脚本运行记录的日志
     */
    public function log() {
    
    }

    /**
     * registerSignal 
     *
     * 注册信号
     */
    protected function registerSignal() {
        pcntl_signal(SIGINT, array($this, 'sigHandler'));
    }

    /**
     * sigHandler 
     *
     * 信号处理
     */
    protected function sigHandler($sig) {
        switch ($sig) {
            case SIGINT:
                $this->state = false;
                break;
           default:
               break;
        }
    }
}
