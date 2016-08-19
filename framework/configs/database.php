<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * database.php
 * 
 * @author     thinklong89@gmail.com
 * @version    $Id$
 */

return [
    // default
    'default' => [
        'adapter' => 'Pdo',
        'masterslave' => true,
        'database' => $_SERVER['SINASRV_DB_NAME_R'],
        'charset' => 'utf8',
        'errmode' => 2,
        'master' => [
            'database' => $_SERVER['SINASRV_DB_NAME'],
            'host' => $_SERVER['SINASRV_DB_HOST'],
            'port' => $_SERVER['SINASRV_DB_PORT'],
            'user' => $_SERVER['SINASRV_DB_USER'],
            'password' => $_SERVER['SINASRV_DB_PASS'],
        ],
        'slave' => [
            0 => [
                'database' => $_SERVER['SINASRV_DB_NAME_R'],
                'host' => $_SERVER['SINASRV_DB_HOST_R'],
                'port' => $_SERVER['SINASRV_DB_PORT_R'],
                'user' => $_SERVER['SINASRV_DB_USER_R'],
                'password' => $_SERVER['SINASRV_DB_PASS_R'],
            ],//Handle_Config::get("database.test.slave"),
        ],
    ],
    'redis' => [
        'adapter' => 'NoSQL_Redis',
        'masterslave' => false,
        'host' => $_SERVER['SINASRV_REDIS_HOST'],
        'port' => $_SERVER['SINASRV_REDIS_PORT'],
        'auth' => $_SERVER['SINASRV_REDIS_AUTH'],
        'charset' => 'utf8',
        'errmode' => 2,
    ],
    
];
