<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Test.php
 * 
 */

class Api_TestModel
{
    protected $daoTest;
    
    /**
     * 构造方法
     */
    public function __construct()
    {
        
    }
    
    
    public function getTestLists()
    {
        return [
            'result' => array(),
            'cur_page' => 1,
            'total' => 10,
        ];
    }
    
    
    
    

}
