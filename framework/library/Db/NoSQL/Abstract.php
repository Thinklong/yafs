<?php
abstract class Db_NoSQL_Abstract
{
    
    protected $adapter = null;
    
    protected $config = [];
    
    public $conn;
    
    public $debug = false;
    
    public $log = [];
    
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        $this->setAdapter($this->config['adapter']);
        $this->connect();
    }
    
    /**
     * Get adapter
     *
     * @return string
     */
    public function getAdapter()
    {
        return strtoupper($this->adapter);
    }
    
    /**
     * Set adapater parameter
     *
     * @param string $adapter
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }    
    
    
    
    /**
     * Throw error exception
     *
     */
    protected function _throwException()
    {
        $error = $this->error();
        $log = end($this->log);
        $adapter = $this->getAdapter();
        AppLog::error($adapter . ' DB exception ', $error);
        throw new Db_Exception($error['msg'], $error['code']);
    }
    
    
    /**
     * Get logs
     *
     * @return array
     */
    public function logs()
    {
        return $this->debug ? $this->log : 'Debug is close.';
    }
    
    
    abstract public function connect();
    
    abstract public function close();
    
    abstract public function error();
    
    
    
}