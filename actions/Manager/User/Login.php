<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Login.php
 * 
 * @author     thinklong89@gmail.com
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
