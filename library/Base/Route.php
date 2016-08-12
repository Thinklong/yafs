<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Route.php
 * 框架基础路由
 * 
 * @author     yulong8@leju.com
 */

final class Base_Route implements Yaf_Route_Interface
{
    protected $_ctl_router;
    
    protected $_delimeter = '?';
    
    public function route($request)
    {
        $this->_setResponseFormat($request);
        
        $this->_setRewriteRule($request);
        
        $request->setRouted();
        
        return true;
    }
    
    protected function _setResponseFormat(Yaf_Request_Abstract $request)
    {
        $url = $request->getRequestUri();
        $responseFormat = strrchr($url, '.');
        if ($responseFormat) {
            $rewriteUri = substr($url, 0, 0 - strlen($responseFormat));
            $request->setRequestUri($rewriteUri);
        } else {
            $responseFormat = 'json';
        }
        
        Yaf_Registry::set("responseFormat", trim($responseFormat, '.'));
    }
    
    protected function _setRewriteRule(Yaf_Request_Abstract $request)
    {
        $uri = $request->getRequestUri();

        $parse = explode('/', trim($uri, '/'));
        $config = Yaf_Registry::get("config")->get('application');
        $modules = ($modules = $config->get("modules")) ? explode(',', $modules) : [];
        
        $module = $controller = $action = null;
        $module = $modules && in_array($parse[0], $modules) ? array_shift($parse) : null;
        $action = count($parse) > 1 ? array_pop($parse) : null;
        $controller = empty($parse) ? null : implode('_', $parse);
 
        // 将 a_d_c_d 之类的 action 接口转换为 abcd
        Yaf_Registry::set("_route_action", $action);
        $action and $action = str_replace('_', '', $action);

        $module or $module = 'Index';
        $controller or $controller = 'Index';
        $action or $action = 'Index';

        $request->setModuleName($module);
        $request->setControllerName($controller);
        $request->setActionName($action);
    }

    public function assemble(array $info, array $query = [])
    {
        return null;
    }
}