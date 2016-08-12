<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Lists.php
 * 
 * @author     thinklong89@gmail.com
 * @version    $Id$
 */

class ListsAction extends Base_Action
{
    protected $_rules = [
        'source' => ['required' => true, 'type' => 'string'],
        'platform' => ['required' => false, 'type' => 'string'],
    ];
    
    protected $_parameters = [
        'source', 'platform'
    ];
    
    public function process()
    {
        $params = (object) $this->filterParams();
        
        $test = new Service_TestModel();
        $result = $test->getTestLists($params->platform, $params->source);
        //$result = [];
        if (false !== $result)
        {
            $this->response(Public_Error::SUCCESS, $result);
        }
        else
        {
            $this->response(Public_Error::FAIL);
        }
        
        
    }
    
}
