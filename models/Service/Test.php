<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Test.php
 * 
 */

class Service_TestModel
{
    protected $daoTest;
    
    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->daoTest = new Dao_TestModel();
    }
    
    
    /**
     * 
     * @param unknown $platform
     * @param unknown $source
     * @param string $page
     * @param string $count
     * @return multitype:string unknown Ambigous <multitype:, boolean, unknown> 
     */
    public function getTestLists($platform, $source, $page = DEFAULT_PAGE, $count = DEFAULT_PAGE_LIMIT)
    {
        $total = $this->daoTest->total($platform, $source);
        
        if ($total && ((ceil($total / $count) >= $page))) {
            $result = $this->daoTest->getTestLists($platform, $source, $page, $count);
        } else {
            $result = [];
        }
        return [
            'result' => $result,
            'cur_page' => $page,
            'total' => $total,
        ];
    }
    
    
    
    

}
