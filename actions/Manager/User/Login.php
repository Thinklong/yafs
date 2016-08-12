<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Login.php
 * 
 * @author     yulong8@leju.com
 * @version    $Id$
 */

class LoginAction extends Base_Action
{
    protected $_rules = [
        'name' => ['required' => false, 'type' => 'string'],
        'password' => ['required' => false, 'type' => 'string'],
    ];
    
    protected $_parameters = [
        'platform', 'source'
    ];
    
    public function process()
    {
        $params = (object) $this->filterParams();
        
        
        
        $this->assign('abc', 'dsd');
        /*$test = new Service_TestModel();
        $result = $test->getTestLists($params->platform, $params->source, $params->page, $params->count);
        if ($result)
        {
            $this->response(Public_Error::SUCCESS, $result);
        }
        else
        {
            $this->response(Public_Error::FAIL);
        }*/
        
        
    }
}