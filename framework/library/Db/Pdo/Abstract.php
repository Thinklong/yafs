<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Abstract.php
 * 
 * @author     yulong8@leju.com
 */

abstract class Db_Pdo_Abstract extends Db_Abstract
{
	/**
	 * Create a PDO object and connects to the database.
	 *
	 * @param array $config
	 * @return resource
	 */
	public function connect()
	{
	    if ($this->ping(false)) {
            return $this->conn;
        }

	    if (!empty($this->config['persistent'])) {
	        $this->config['options'][PDO::ATTR_PERSISTENT] = true;
	    }

	    if (!empty($this->config['errmode']) && ($errmode = intval($this->config['errmode']))) {
            $errmode = 2 === $errmode ? PDO::ERRMODE_EXCEPTION : (1 === $errmode ? PDO::ERRMODE_WARNING : PDO::ERRMODE_SILENT) ;
	        $this->config['options'][PDO::ATTR_ERRMODE] = $errmode;
	    }
        
        if($this->config['charset'] && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $this->config['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$this->config['charset']}'";
        }

	    $this->conn = new PDO($this->_dsn($this->config), $this->config['user'], $this->config['password'], $this->config['options']);
        if($this->config['charset'] && !defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $this->query("SET NAMES '{$this->config['charset']}'");
        }
        
	    return $this->conn;
	}

	/**
     * Select Database
     *
     * @param string $database
     * @return boolean
     */
    public function selectDb($database)
    {
        return $this->query("use $database;");
    }

    /**
     * Close mysql connection
     *
     */
    public function close()
    {
        $this->conn = null;
    }

    /**
     * Free result
     *
     */
    public function free()
    {
        $this->query = null;
    }

    /**
     * Query sql
     *
     * @param string $sql
     * @return Db_Mysql
     */
    protected function _query($sql)
    {
        return $this->conn->query($sql);
    }

    /**
     * Return the rows affected of the last sql
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->query->rowCount();
    }

    /**
     * Get pdo fetch style
     *
     * @param string $style
     * @return int
     */
    protected static function _getFetchStyle($style)
    {
        switch ($style) {
            case 'ASSOC':
                $style = PDO::FETCH_ASSOC;
                break;
            case 'BOTH':
                $style = PDO::FETCH_BOTH;
                break;
            case 'NUM':
                $style = PDO::FETCH_NUM;
                break;
            case 'OBJECT':
                $style = PDO::FETCH_OBJECT;
                break;
            default:
                $style = PDO::FETCH_ASSOC;
        }

        return $style;
    }

    /**
     * Fetch one row result
     *
     * @param string $type
     * @return mixd
     */
    public function fetch($type = 'ASSOC')
    {
        $type = strtoupper($type);
        if (0 === $this->query->rowCount()) {
            return array();
        }
        
        return $this->query->fetch(self::_getFetchStyle($type));
    }

    /**
     * Fetch All result
     *
     * @param string $type
     * @return array
     */
    public function fetchAll($type = 'ASSOC')
    {
        $type = strtoupper($type);
        $result = $this->query->fetchAll(self::_getFetchStyle($type));
        $this->free();
        return $result;
    }

	/**
	 * Initiate a transaction
	 *
	 * @return boolean
	 */
	public function beginTransaction()
	{
        if (is_null($this->conn)) {
            $this->connect();
        }
        if ($this->_transDepth(true)) {
            return $this->conn->beginTransaction();
        }
        
		return true;
	}

	/**
	 * Commit a transaction
	 *
	 * @return boolean
	 */
	public function commit()
	{
        if (!is_null($this->conn) && $this->_transDepth(false)) {
            return $this->conn->commit();
        }
        
        return true;
	}

	/**
	 * Roll back a transaction
	 *
	 * @return boolean
	 */
	public function rollBack()
	{
        if (!is_null($this->conn) && $this->_transDepth(false)) {
            return $this->conn->rollBack();
        }
        
        return true;
	}

	/**
	 * Get the last inserted ID.
	 *
	 * @param string $tableName
	 * @param string $primaryKey
	 * @return integer
	 */
	public function lastInsertId($tableName = null, $primaryKey = null)
	{
		return $this->conn->lastInsertId();
	}

	/**
     * Escape string
     *
     * @param string $str
     * @return string
     */
	public function escape($str) {
        return addslashes($str);
    }

    /**
     * Get error
     *
     * @return array
     */
    public function error()
    {
        if (0 != $this->conn->errorCode()) {
            $errno = $this->conn->errorCode();
            $error = $this->conn->errorInfo();
        } else {
            $errno = $this->query->errorCode();
            $error = $this->query->errorInfo();
        }

        return array('code' => intval($errno), 'msg' => $error[2]);
    }

    /**
     * Ping mysql server
     *
     * @param boolean $reconnect
     * @return boolean
     */
    public function ping($reconnect = true)
    {
        if ($this->conn && $this->conn->query('select 1')) {
            return true;
        }

        if ($reconnect) {
            $this->close();
            $this->connect();
            return $this->ping(false);
        }

        return false;
    }
}
