<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Lists.php
 * 
 * @author     yulong8@leju.com
 * @version    $Id$
 */

class ListsInfoAction extends Base_Action
{
    protected $_rules = [
        'source' => ['required' => true, 'type' => 'string'],
        'platform' => ['required' => false, 'type' => 'string'],
        'page' => ['type' => 'number', 'min' => 1],
        'count' => ['type' => 'number', 'min' => 1],
    ];
    
    protected $_parameters = [
        'source', 'platform', 'page', 'count'
    ];
    
    public function process()
    {
        $params = (object) $this->filterParams();
        $params->page = AppRequest::instance()->getParam('page', DEFAULT_PAGE);
        $params->count = AppRequest::instance()->getParam('count', DEFAULT_PAGE_LIMIT);
        
        
        /*$test = new Service_TestModel();
        $result = $test->getTestLists($params->source, $params->platform, $params->page, $params->count);
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