<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Pdo.php
 * 
 * @author     yulong8@leju.com
 */

class Db_Pdo extends Db_Pdo_Mysql
{
    /**
     * Query sql
     * 
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    protected function _query($sql, array $params = array())
    {
        try {
            $this->query = $this->conn->prepare($sql);
            $this->bindParams($params);
            $ret = $this->query->execute();
        } catch (PDOException $e) {
            $ret = false;
        }
        
        return $ret ? $this->query : false;
    }
    
    /**
     * Bind param
     * 
     * @param string $parameter
     * @param string $variable
     * @return boolean
     */
    public function bind($parameter, $variable)
    {
        if (!$this->query) {
            return false;
        }

        if (is_int($variable)) {
            $dataType = PDO::PARAM_INT;
        } else if ('0' === $variable) {
            $dataType = PDO::PARAM_INT;
        } else if ((is_numeric($variable) && '0' !== substr($variable, 0, 1))) {
            $dataType = PDO::PARAM_INT;
        } else {
            $dataType = PDO::PARAM_STR;
        }

        return $this->query->bindParam($parameter, $variable, $dataType);
    }
    
    /**
     * Bind params
     * 
     * @param array $variables
     * @return boolean
     */
    public function bindParams(array $variables)
    {
        if (!$variables) {
            return true;
        }
        
        $pos = 0;
        foreach ($variables as $item) {
            $this->bind(++$pos, $item);
        }
        
        return true;
    }
}
