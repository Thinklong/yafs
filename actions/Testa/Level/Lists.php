<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Lists.php
 * 
 * @author     yulong8@leju.com
 * @version    $Id$
 */

class ListsAction extends Base_Action
{
    protected $_rules = [
        'platform' => ['required' => false, 'type' => 'string'],
        'source' => ['required' => false, 'type' => 'string'],
        'page' => ['type' => 'number', 'min' => 1],
        'count' => ['type' => 'number', 'min' => 1],
    ];
    
    protected $_parameters = [
        'platform', 'source', 'page', 'count'
    ];
    
    public function process()
    {
        $params = (object) $this->filterParams();
        $params->page = AppRequest::instance()->getParam('page', DEFAULT_PAGE);
        $params->count = AppRequest::instance()->getParam('count', DEFAULT_PAGE_LIMIT);
        
        
        
        $test = new Service_TestModel();
        $result = $test->getTestLists($params->platform, $params->source, $params->page, $params->count);
        if ($result)
        {
            $this->response(Public_Error::SUCCESS, $result);
        }
        else
        {
            $this->response(Public_Error::FAIL);
        }
        
        
    }
}