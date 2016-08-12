<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Mysql.php
 * 
 * @author     yulong8@leju.com
 */

class Db_Pdo_Mysql extends Db_Pdo_Abstract
{
    protected function _dsn($params)
    {
        return "mysql:host={$params['host']};port={$params['port']};dbname={$params['database']}";
    }
}