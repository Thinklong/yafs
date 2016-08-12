<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Mysql.php
 * 
 * @author     thinklong89@gmail.com
 */

class Db_Pdo_Mysql extends Db_Pdo_Abstract
{
    protected function _dsn($params)
    {
        return "mysql:host={$params['host']};port={$params['port']};dbname={$params['database']}";
    }
}
