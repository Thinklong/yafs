<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Masterslave.php
 * 
 * @author     yulong8@leju.com
 */

class Db_Masterslave extends Db_Abstract
{
    /**
     * Configuration
     *
     * @var array
     */
    public $msconfig = array(
        'masterslave' => true
    );
    
    /**
     * Master MySQL Adapter
     *
     * @var Db_Abstract
     */
    protected $_master = null;

    /**
     * Slave MySQL Adapter
     *
     * @var Db_Abstract
     */
    protected $_slave = null;
    
    protected $__slave = null;

    /**
     * Current MySQL Adapter
     *
     * @var Db_Abstract
     */
    protected $_mysql = null;

    public $autoSelecteDB = true;

    public function __construct($config)
    {
        unset($config['adapter']);
        $this->msconfig = $config;
        
        if (!isset($config['master'])) {
            throw new Db_Exception('NO_MYSQL_MASTER_CONFIG');
        }
        
        if (!isset($config['slave'])) {
            throw new Db_Exception('NO_MYSQL_SLAVE_CONFIG');
        }
    }

    /**
     * Common factory pattern constructor
     *
     * @param string $class
     * @param array $config
     * @return Object
     */
    final public static function factory($class, $config)
    {
        if ($config && isset($config['adapter'])) {
            $class .= '_' . ucfirst($config['adapter']);
        }

        return new $class($config);
    }

    /**
     * Master MySQL Adapter
     *
     * @return Db_Abstract
     */
    public function master()
    {
        if ($this->_master) {
            $this->_mysql = $this->_master;
            return $this->_master;
        }

        $config = $this->_config(__FUNCTION__);

        $this->_master = self::factory('Db', $config);
        $this->_master->debug = $this->debug;
        //$this->_slave = $this->_master;
        $this->_mysql = $this->_master;

        return $this->_master;
    }

    /**
     * Slave MySQL Adapter
     *
     * @param string $name
     * @return Db_Abstract
     */
    public function slave($name = null)
    {
        if ($this->_slave) {
            $this->_mysql = $this->_slave;
            return $this->_slave;
        }

        $config = $this->_config(__FUNCTION__, $name);

        $this->_slave = self::factory('Db', $config);
        $this->_slave->debug = $this->debug;
        $this->_mysql = $this->_slave;

        return $this->_slave;
    }
    
    private function _config($masterslave, $name = null)
    {
        if ('slave' === $masterslave)
        {
            if (!$name || empty($this->msconfig['slave'][$name]))
            {
                $name = array_rand($this->msconfig['slave']);
            }
            $config = $this->msconfig['slave'][$name];
        }
        else
        {
            $config = $this->msconfig['master'];
        }
        $config += array('adapter' => $this->adapter) + $this->msconfig;
        unset($config['master'], $config['slave'], $config['masterslave']);
        
        return $config;
    }

    /**
     * Returns the underlying database connection object or resource.
     * If not presently connected, this initiates the connection.
     *
     * @return object|resource|null
     */
    public function connect()
    {
        return $this;
    }

    /**
     * Close mysql connection
     *
     */
    public function close()
    {
        if ($this->_master) {
            $this->_master->close();
        }
        
        if ($this->_slave) {
            $this->_slave->close();
        }
    }

    /**
     * Query SQL
     *
     * @param string $sql
     * @return Db_Mysql
     */
    protected function _query($sql, array $params = array())
    {
        if (strtolower(strrchr($sql, ' ')) === ' update') {
            return $this->master()->_query($sql, $params);
        }
        
        return $this->_mysql->_query($sql, $params);
    }

    /**
     * Return the rows affected of the last sql
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->_mysql->affectedRows();
    }

    /**
     * Fetch one row result
     *
     * @param string $type
     * @return mixd
     */
    public function fetch($type = 'ASSOC')
    {
        return $this->_mysql->fetch($type = 'ASSOC');
    }

    /**
     * Fetch All result
     *
     * @param string $type
     * @return array
     */
    public function fetchAll($type = 'ASSOC')
    {
        return $this->_mysql->fetchAll($type = 'ASSOC');
    }

    /**
     * Get last insert id
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->master()->lastInsertId();
    }

    /**
     * Beging transaction
     *
     */
    public function beginTransaction()
    {
        $result = $this->master()->beginTransaction();
        $this->__slave = $this->_slave;
        $this->_slave = $this->_master;
        return $result;
    }

    /**
     * Commit transaction
     *
     * @return boolean
     */
    public function commit()
    {
        $this->_slave = $this->__slave;
        $this->__slave = null;
        return $this->master()->commit();
    }

    /**
     * Roll back transaction
     *
     * @return boolean
     */
    public function rollBack()
    {
        $this->_slave = $this->__slave;
        $this->__slave = null;
        return $this->master()->rollBack();
    }

    /**
     * Free result
     *
     */
    public function free()
    {
        $this->_mysql->free();
    }

    /**
     * Escape string
     *
     * @param string $str
     * @return string
     */
    public function escape($str)
    {
        foreach (array($this->_master, $this->_slave) as $mysql) {
            if ($mysql) {
                return $mysql->escape($str);
            }
        }

        return addslashes($str);
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
        $func = $this->auto();
        return $this->$func()->row($table, $where, $params, $type);
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
        $func = $this->auto();
        return $this->$func()->col($table, $where, $params);
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
        $func = $this->auto();

        return $this->$func()->find($opts, $params);
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
        return $this->master()->insert($data, $table);
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
        return $this->master()->update($data, $table, $where, $params);
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
        return $this->master()->delete($table, $where, $params);
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
        $func = $this->auto();
        return $this->$func()->count($where, $table, $params);
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
        $tags = explode(' ', $sql, 2);
        if ('FOR UPDATE' === strtoupper(substr(rtrim($sql, " \t\n\r\0\x0B;"), -10))) {
            $tags[0] = 'UPDATE';
        }

        switch (strtoupper($tags[0])) {
            case 'SELECT':
                $func = $this->auto();
                $this->$func()->query($sql, $params);
                break;
            default:
                $this->master()->query($sql, $params);
        }
    }
    
    /**
     * Auto select master or slave
     * 
     * @return string
     */
    public function auto()
    {
        if (true === $this->autoSelecteDB) {
            $func = 'slave';
        } else {
            $func = 'master';
        }
        
        $this->autoSelecteDB = true;
        return $func;
    }
    
    /**
     * Get logs
     * 
     * @return type
     */
    public function logs()
    {
        return $this->_mysql->log;
    }
    
    public function lastSql()
    {
        return $this->_mysql->lastSql();
    }
}
