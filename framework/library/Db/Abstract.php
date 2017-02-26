<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Abstract.php
 * 
 * @author     thinklong89@gmail.com
 */

abstract class Db_Abstract
{
    /**
     * Configuration
     *
     * @var array
     */
    protected $msconfig = array(
        'masterslave' => false
    );
    
    protected $adapter = null;
    
    /**
     * Configuration
     *
     * @var array
     */
    public $config = array(
        'adapter'   => 'Pdo_Mysql',
        'host'       => '127.0.0.1',
        'port'       => 3306,
        'user'       => 'test',
        'password'   => '',
        'database'   => 'test',
        'charset'    => 'utf8',
        'persistent' => false,
        'options'    => array()
    );

    /**
     * Connection
     *
     * @var resource
     */
    public $conn = null;

    /**
     * Query handler
     *
     * @var resource
     */
    public $query = null;

    /**
     * Debug or not
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * Log
     *
     * @var array
     */
    public $log = array();
    
    /**
     * Trans depth
     * 
     * @var int 
     */
    protected $_trans_depth = 0;

    /**
     * Constructor.
     *
     * $config is an array of key/value pairs
     * containing configuration options.  These options are common to most adapters:
     *
     * host           => (string) What host to connect to, defaults to localhost
     * user           => (string) Connect to the database as this username.
     * password       => (string) Password associated with the username.
     * database       => (string) The name of the database to user
     *
     * Some options are used on a case-by-case basis by adapters:
     *
     * port           => (string) The port of the database
     * persistent     => (boolean) Whether to use a persistent connection or not, defaults to false
     * charset        => (string) The charset of the database
     *
     * @param  array $config
     */
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        $this->setAdapter($this->config['adapter']);
    }

    /**
     * Query sql
     *
     * @param string $sql
     * @param array $params
     * @return resource
     */
    public function query($sql, array $params = array())
    {
        if (is_null($this->conn)) {
            $this->connect();
        }

        $log = $sql . '@' . date('Y-m-d H:i:s');
        if ($this->debug) {
            $this->log[] = $log;
        }

        if (($resource = $this->_query($sql, $params)) && ($this->query = $resource)) {
            unset($resource);
            return $this->query;
        }

        $this->log[] = $log;
        $this->_throwException();
    }

    /**
     * Get SQL result
     *
     * @param string $sql
     * @param string $type
     * @return mixed
     */
    public function sql($sql, array $params = array(), $type = 'ASSOC')
    {
        if ('PDO' === $this->getAdapter()) {
            $this->query($sql, $params);
        } else {
            $this->query($sql);
        }

        $tags = explode(' ', $sql, 2);
        switch (strtoupper($tags[0])) {
            case 'SELECT':
            case 'SHOW':
            case 'EXPLAIN':
                ($result = $this->fetchAll($type)) || ($result = array());
                break;
            case 'INSERT':
                $result = $this->lastInsertId();
                '0' === $result and $result = $this->affectedRows();
                break;
            case 'UPDATE':
            case 'DELETE':
                $result = $this->affectedRows();
                break;
            default:
                $result = $this->query;
        }

        return $result;
    }

    /**
     * Get a result row
     * 
     * @param string $table
     * @param string $where
     * @param array $params
     * @param string $type
     * @return array
     */
    public function row($table, $where = 0, array $params = array(), $type = 'ASSOC')
    {
        $sql = "select * from {$table} where {$where}";
        
        if ('PDO' === $this->getAdapter()) {
            $this->query($sql, $params);
        } else {
            $this->query($sql);
        }
        
        return $this->fetch($type);
    }

    /**
     * Get first column of result
     * 
     * @param string $table
     * @param string $where
     * @param array $params
     * @param string $type
     * @return array
     */
    public function col($table, $where = 0, array $params = array())
    {
        $sql = "select * from {$table} where {$where}";
        
        if ('PDO' === $this->getAdapter()) {
            $this->query($sql, $params);
        } else {
            $this->query($sql);
        }
        
        $result = $this->fetch();
        return empty($result) ? null : current($result);
    }
    
    /**
     * Build SQL
     * 
     * @param array $opts
     * @return string
     */
    public function buildSql($opts)
    {
        return $this->find($opts);
    }

    /**
     * Find data
     *
     * @param array $opts
     * @param array $params
     * @return array
     */
    public function find($opts, array $params = array())
    {
        if (is_string($opts)) {
            $opts = array('where' => $opts);
        }

        $opts = $opts + array(
            'fields' => '*',
            'where' => 1,
            'group' => null,
            'order' => null,
            'start' => -1,
            'limit' => -1
        );

        $opts['where'] or $opts['where'] = 1;
        $sql = "select {$opts['fields']} from {$opts['table']} where {$opts['where']}";

        if ($opts['group']) {
            $sql .= " group by {$opts['group']}";
        }

        if ($opts['order']) {
            $sql .= " order by {$opts['order']}";
        }

        if (0 <= $opts['start'] && 0 <= $opts['limit']) {
            $sql .= " limit {$opts['start']}, {$opts['limit']}";
        }

        return isset($opts['_build']) ? $sql : $this->sql($sql, $params);
    }

    /**
     * Insert
     *
     * @param array $data
     * @param string $table
     * @return boolean
     */
    public function insert($data, $table)
    {
        $duplicate = '';
        if (isset($data['_update']) && is_array($data['_update'])) {
            $tmp = $this->_duplicate_update($data['_update']);
            $duplicate = $tmp['_sql'];
            $duplicateVal = $tmp['_val'];
            unset($data['_update'], $tmp);
        }

        $params = array();
        $adapter = $this->getAdapter();
        foreach ($data as $key => $value) {
            $params[] = $value;
            $keys[] = "`$key`";
            $values[] = 'PDO' === $adapter ? '?' : "'" . $this->escape($value) . "'";
        }
        $keys = implode(',', $keys);
        $values = implode(',', $values);

        $sql = "insert into {$table} ({$keys}) values ({$values}){$duplicate}";

        if ('PDO' === $adapter && isset($duplicateVal)) {
            $params = array_merge($params, $duplicateVal);
        }
        unset($keys, $values);

        return $this->sql($sql, $params);
    }
    
    /**
     * Duplicate update
     * 
     * @param array $data
     * @return array
     */
    protected function _duplicate_update($data)
    {
        $adapter = $this->getAdapter();
        foreach ($data as $key => $value) {
            if (isset($value[0]) && 'exp' === $value[0]) {
                $values[] = "`{$key}`" . $this->escape($value[1]) . "";
            } else {
                $val[] = $value;
                $values[] = 'PDO' === $adapter ? "`{$key}`=?" : "`{$key}`='" . $this->escape($value[1]) . "'";
            }
        }
        $values = implode(',', $values);
        $duplicate = " on duplicate key update {$values}";
        unset($value, $values);
        
        return array('_sql' => $duplicate, '_val' => $val);
    }

    /**
     * Update table
     *
     * @param array $data
     * @param string $where
     * @param string $table
     * @return int
     */
    public function update($data, $table, $where = '0', array $params = array())
    {
        $tmp = $val = array();

        if (isset($params['__pk'])) {
            $__pk = $params['__pk'];
            unset($params['__pk']);
        }

        $adapter = $this->getAdapter();
        foreach ($data as $key => $value) {
            if (is_array($value) && $value[0] === 'exp') {
                $value = '=' === $value[1][0] ? $value[1] : ('=' . $value[1]);
                $value = $this->escape($value);
                ('PDO' === $adapter) && (false !== strpos($value, '?')) && ($val[] = array_shift($params));
            } else {
                $val[] = $value;
                $value = 'PDO' === $adapter ? '=?' : "='" . $this->escape($value) . "'";
            }
            $tmp[] = "`{$key}`{$value}";
        }

        $str = implode(',', $tmp);
        $sql = "update {$table} set {$str} where {$where}";

        if ('PDO' === $adapter) {
            $params = array_merge($val, $params);
            isset($__pk) && ($params[] = $__pk);
        }
        unset($tmp, $val, $__pk);

        return $this->sql($sql, $params);
    }

    /**
     * Delete from table
     *
     * @param string $where
     * @param string $table
     * @return int
     */
    public function delete($table, $where = '0', array $params = array())
    {
        $sql = "delete from {$table} where {$where}";
        return $this->sql($sql, $params);
    }

    /**
     * Count num rows
     *
     * @param string $where
     * @param string $table
     * @return int
     */
    public function count($where, $table, array $params = array())
    {
        $where = $where ? $where : 1;
        $sql = "select count(1) as cnt from {$table} where {$where}";
        $this->query($sql, $params);
        $result = $this->fetch();
        return empty($result['cnt']) ? 0 : $result['cnt'];
    }

    /**
     * Throw error exception
     *
     */
    protected function _throwException()
    {
        $error = $this->error();
        $lastSql = end($this->log);
        $adapter = $this->getAdapter();
        AppLog::error($adapter . ' DB exception when execute SQL: ' . $lastSql, $error);
        throw new Db_Exception($error['msg'], $error['code']);
    }
    
    /**
     * Mark transaction depth
     * 
     * @param boolean $isBegin
     * @return boolean
     */
    protected function _transDepth($isBegin)
    {
        $isBegin = !!$isBegin;
        if ($isBegin) {
            $this->_trans_depth < 0 && ($this->_trans_depth = 0);
            $ret = 0 === $this->_trans_depth;
            $this->_trans_depth += 1;
        } else {
            $this->_trans_depth -= 1;
            $ret = 0 === $this->_trans_depth;
        }
        
        return $ret;
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
        $adapter or ($adapter = 'Pdo_Mysql');
        $this->adapter = $adapter;
    }
    
    /**
     * Get logs for SQL
     * 
     * @return array
     */
    public function logs()
    {
        return $this->debug ? $this->log : 'Debug for SQL is close.';
    }
    
    /**
     * Get last SQL log
     * 
     * @return string
     */
    public function lastSql()
    {
        return $this->debug && !empty($this->log) ? end($this->log) : 'Debug for SQL is close.';
    }

    abstract public function connect();

    abstract public function close();

    abstract protected function _query($sql);

    abstract public function affectedRows();

    abstract public function fetch();

    abstract public function fetchAll();

    abstract public function lastInsertId();

    abstract public function beginTransaction();

    abstract public function commit();

    abstract public function rollBack();

    abstract public function free();

    abstract public function escape($str);
}
