<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Dao.php
 * 模型基类
 * 
 * @author     thinklong89@gmail.com
 */

abstract class Base_Dao
{
    const ERROR_VALIDATE_CODE = -400;

    /**
     * Db name
     *
     * @var string
     */
    protected $_db = 'default';

    /**
     * Table name, with prefix and main name
     *
     * @var string
     */
    protected $_table;

    /**
     * Primary key
     *
     * @var string
     */
    protected $_pk = 'id';

    /**
     * Cache config
     *
     * @var mixed, string for config key and array for config
     */
    protected $_cache = '_cache';

    /**
     * Cache expire time
     *
     * @var int
     */
    protected $_ttl = 60;

    /**
     * Validate rules
     *
     * @var array
     */
    protected $_validate = array();

    /**
     * Error infomation
     *
     * @var array
     */
    public $error = array();

    /**
     * Dynamic set vars
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value = null)
    {
        $this->$key = $value;
    }

    /**
     * Dynamic get vars
     *
     * @param string $key
     */
    public function __get($key)
    {
        switch ($key) {
            case 'db' :
                $this->db = $this->db();
                return $this->db;
            default:
                throw new Yaf_Exception('Undefined property: ' . get_class($this). '::' . $key);
        }
    }

    /**
     * Connect db from config
     *
     * @param array $config
     * @param string
     * @return Db_
     */
    final public function db($name = null)
    {
        is_null($name) && $name = $this->_db;

        if (is_array($name) && ($config = $name)) {
            return self::factory('Db', $name);
        }

        $regName = "_". APP_NAME ."_db_{$name}";
        if (!$db = Yaf_Registry::get($regName)) {
            $config = Handle_Config::load('database');
            if (!$config || !isset($config[$name])) {
                exit('Error: ' . $name . ' database config not exists.');
            }

            $config = $config[$name];
            $adapter = $config['adapter'];
            if (isset($config['masterslave']) && $config['masterslave']) {
                $config = array('adapter' => 'masterslave') + $config;
            } else {
                $config += array('adapter' => 'Pdo_Mysql');
            }

            $db = self::factory('Db', $config);
            $db->setAdapter($adapter);
            
            if (isset($this->debug)) {
                $db->debug = !!$this->debug;
            } else if (defined("SWITCH_SQL_LOG")) {
                $db->debug = !!SWITCH_SQL_LOG;
            }

            Yaf_Registry::set($regName, $db);
        }

        return $db;
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
     * Get last SQL log
     * 
     * @return string
     */
    final public function lastSql()
    {
        return $this->db()->lastSql();
    }
    
    /**
     * Show logs for SQL
     * 
     * @return array
     */
    final public function sqlLogs()
    {
        return $this->db()->logs();
    }

    /**
     * Begin transaction
     *
     */
    public function beginTransaction()
    {
        //$this->db->commit();
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     *
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * Rollback
     *
     */
    public function rollBack()
    {
        $this->db->rollBack();
    }

    /**
     * Get SQL result
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function sql($sql, array $params = array())
    {
        try {
            $result = $this->db->sql($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build SQL
     * 
     * @param array $opts
     * @return string
     */
    public function buildSql(array $opts)
    {
        isset($opts['table']) or $opts['table'] = $this->_table;
        isset($opts['_build']) or $opts['_build'] = true;
        return $this->db->find($opts);
    }

    /**
     * Find result
     *
     * @param array $opts
     * @param array $params
     * @return array
     */
    public function find($opts = array(), array $params = array())
    {
        is_string($opts) && $opts = array('where' => $opts);

        $opts += array('table' => $this->_table);

        try {
            $result = $this->db->find($opts, $params);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }

    /**
     * Insert
     *
     * @param array $data
     * @param array $params
     * @param string $table
     * @return boolean
     */
    public function insert($data, $table = null)
    {
        if (is_null($table)) {
            $table = $this->_table;
        }

        try {
            $result = $this->db->insert($data, $table);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }

    /**
     * Update
     *
     * @param int $id
     * @param array $data
     * @return boolean
     */
    public function update($id, $data, $pk = true, array $params = array(), $table = null)
    {
        if (is_null($table)) {
            $table = $this->_table;
        }
        
        if (true === !!$pk) {
            if ('PDO' === $this->db->getAdapter()) {
                $params['__pk'] = $id;
                $where = $this->_pk . '=?';
            } else {
                $where = $this->_pk . '=' . (is_int($id) ? $id : "'$id'");
            }
        } else {
            $where = is_array($id) ? $this->where($id) : $id;
        }

        try {
            $result = $this->db->update($data, $table, $where, $params);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }

    /**
     * Delete
     *
     * @param string $where
     * @param string $table
     * @return boolean
     */
    public function delete($id, $col = null, $table = null)
    {
        if (is_null($table)) {
            $table = $this->_table;
        }
        
        $params = array();
        is_null($col) && $col = $this->_pk;
        if ('PDO' === $this->db->getAdapter()) {
            $params = array($id);
            $where = "{$col} = ?";
        } else {
            $id = $this->escape($id);
            $where = "{$col} = '{$id}'";
        }

        try {
            $result = $this->db->delete($table, $where, $params);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }

    /**
     * Count result
     *
     * @param string $where
     * @param string $table
     * @return int
     */
    public function count($where, $params = array(), $table = null)
    {
        if (is_null($table)) {
            $table = $this->_table;
        }

        try {
            $where = is_array($where) ? $this->where($where) : $where;
            $result = $this->db->count($where, $table, (array) $params);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }

    /**
     * Load data
     *
     * @param int $id
     * @return array
     */
    public function load($id, $col = null, $table = null)
    {
        is_null($col) && $col = $this->_pk;
        $table = $table ? $table : $this->_table;
        
        $params = array();
        if ('PDO' === $this->db->getAdapter()) {
            $where = "{$col} = ?";
            $params = array( $id );
        } else {
            $id = $this->escape($id);
            $where = "{$col} = '{$id}'";
        }

        try {
            $result = $this->db->row($table, $where, $params);
            return $result;
        } catch (Exception $e) {
            $this->error = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            return false;
        }
    }

    /**
     * Set table
     * 
     * @param string $table
     * @return \Base_Model
     */
    public function table($table)
    {
        $this->_table = $table;
        
        return $this;
    }

    /**
     * Escape string
     *
     * @param string $str
     * @return string
     */
    final public function escape($str)
    {
        return $this->db->escape($str);
    }
	
	/**
	 * parse where condition
	 * 
	 * @param type $where
	 * @return null
	 */
	final protected function parseWhere($where)
	{
		if (empty($where)) {
			return null;
		}
		
		$whereStr = '';
		if (!is_array($where)) {
			// string
			$whereStr .= (string) $where;
		} else {
			// array
			$operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
			unset($where['_logic']);
			$operate = sprintf(" %s ", (in_array($operate, array('AND', 'OR', 'XOR')) ? $operate : 'AND'));
			foreach ($where as $key => $val) {
				$whereStr .= '( ';
				if (!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/', trim($key))) {
					die('error_sql_condition: ' . $key);
				}
				// 多条件支持
				$multi = is_array($val) && isset($val['_multi']);
				$key = trim($key);
				if (strpos($key, '|')) {
					// 支持 name|title|nickname 方式定义查询字段, OR 查询
					$array = explode('|', $key);
					$str = array();
					foreach ($array as $m => $k) {
						$v = $multi ? $val[$m] : $val;
						$str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
					}
					$whereStr .= implode(' OR ', $str);
				} elseif (strpos($key, '&')) {
					// 支持 name&title&nickname 方式定义查询字段, AND 查询
					$array = explode('&', $key);
					$str = array();
					foreach ($array as $m => $k) {
						$v = $multi ? $val[$m] : $val;
						$str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
					}
					$whereStr .= implode(' AND ', $str);
				} else {
					$whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
				}
				$whereStr .= ' )' . $operate;
			}
			$whereStr = substr($whereStr, 0, -strlen($operate));
		}
		
		return empty($whereStr) ? '' : $whereStr;
	}
	
	/**
	 * parse where item
	 * 
	 * @param type $key
	 * @param type $val
	 * @return string
	 */
	final protected function parseWhereItem($key, $val)
	{
		$comparison = array(
			'eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 
			'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE',
			'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN'
		);
		$whereStr = '';
		if (is_array($val)) {
			if (is_string($val[0])) {
				if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i', $val[0])) {
					// 比较运算
					$whereStr .= $key . ' ' . $comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
				} elseif (preg_match('/^(NOTLIKE|LIKE)$/i', $val[0])) {
					// 模糊查找
					if (is_array($val[1])) {
						$likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
						if (in_array($likeLogic, array('AND', 'OR', 'XOR'))) {
							$likeStr = $comparison[strtolower($val[0])];
							$like = array();
							foreach ($val[1] as $item) {
								$like[] = $key . ' ' . $likeStr . ' ' . $this->parseValue($item);
							}
							$whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
						}
					} else {
						$whereStr .= $key . ' ' . $comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
					}
				} elseif ('exp' == strtolower($val[0])) {
					// 使用表达式
					$whereStr .= ' (' . $key . ' ' . $val[1] . ') ';
				} elseif (preg_match('/IN/i', $val[0])) {
					// IN 运算
					if (isset($val[2]) && 'exp' == $val[2]) {
						$whereStr .= $key . ' ' . strtoupper($val[0]) . ' ' . $val[1];
					} else {
						if (is_string($val[1])) {
							$val[1] = explode(',', $val[1]);
						}
						$zone = implode(',', $this->parseValue($val[1]));
						$whereStr .= $key . ' ' . strtoupper($val[0]) . ' (' . $zone . ')';
					}
				} elseif (preg_match('/BETWEEN/i', $val[0])) { // BETWEEN运算
					$data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$whereStr .= ' (' . $key . ' ' . strtoupper($val[0]) . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]) . ' )';
				} else {
					die('error_condition_sql: ' . $val[0]);
				}
			} else {
				/**
				 * 支持
				 * $where['name'] = array(
				 *		array('in', '1,2,3,4'),
				 *		array('like', '%sina%'),
				 *		'and',
				 * );
				 * 即 WHERE ( (id IN '1,2,3,4,5') AND (id LIKE '%sina%') )
				 */
				$count = count($val);
				$rule = isset($val[$count - 1]) && is_string($val[$count - 1]) ? strtoupper($val[$count - 1]) : '';
				if (in_array($rule, array('AND', 'OR', 'XOR'))) {
					$count = $count - 1;
				} else {
					$rule = 'AND';
				}
				for ($i = 0; $i < $count; $i++) {
					$data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
					if ('exp' == strtolower($val[$i][0])) {
						$whereStr .= '(' . $key . ' ' . $data . ') ' . $rule . ' ';
					} else {
						$op = is_array($val[$i]) ? $comparison[strtolower($val[$i][0])] : '=';
						$whereStr .= '(' . $key . ' ' . $op . ' ' . $this->parseValue($data) . ') ' . $rule . ' ';
					}
				}
				$whereStr = substr($whereStr, 0, -4);
			}
		} else {
			$whereStr .= $key . ' = ' . $this->parseValue($val);
		}
		
		return $whereStr;
	}
	
	/**
	 * parse where item key
	 * 
	 * @param type $key
	 * @return type
	 */
	final protected function parseKey($key)
	{
		return $key;
	}
	
	/**
	 * parse where item value
	 * 
	 * @param type $key
	 * @return type
	 */
	final protected function parseValue($value)
	{
        if ('?' === $value) {
            return $value;
        }
        
        if (is_string($value)) {
            $value =  '\''.$this->_escapeString($value).'\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value =  $this->_escapeString($value[1]);
        } elseif (is_array($value)) {
            $value =  array_map(array($this, 'parseValue'),$value);
        } elseif (is_bool($value)){
            $value =  $value ? '1' : '0';
        } elseif (is_null($value)){
            $value =  'null';
        }
        return $value;
	}
	
	/**
	 * safe to sql
	 * 
	 * @param type $value
	 * @return type
	 */
	final protected function _escapeString($value)
	{
		if (defined('MAGIC_QUOTES_GPC') && MAGIC_QUOTES_GPC) {
			return $value;
		} else {
			return addslashes($value);
		}
	}
	
	/**
	 * make where condition
	 * 
	 * @param type $where
	 * @param type $chain
	 * @return type
	 */
	final public function where($where = array())
	{
		return $this->parseWhere($where);
	}
    
    /**
     * Convert master
     * @return \Base_Model
     */
    final public function master()
    {
        $this->db->autoSelecteDB = false;
        return $this;
    }
    
    /**
     * Convert slave
     * @return \Base_Model
     */
    final public function slave()
    {
        $this->db->autoSelecteDB = true;
        return $this;
    }

}
