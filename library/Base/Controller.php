<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Controller.php
 * 控制器基类
 * 
 * @author     thinklong89@gmail.com
 */

abstract class Base_Controller extends Yaf_Controller_Abstract
{
    
    public $actions = [];
    
    /**
     * 初始化
     */
    public function init()
    {
        $this->autoSetActions();
    }
    
    /**
     * 自动设置 Actions 解析器
     * @return type
     */
    final protected function autoSetActions()
    {
        $controller = $this->getRequest()->getControllerName();

        // TODO route
        // 将 xx_xx_xx 之类的 action 接口转换为
        $action = Yaf_Registry::get("_route_action");
        if ($action && ($action = strtolower($action)) && false !== strpos($action, '_')) {
            $action = preg_replace_callback("/_(\S)/", function($match) {
                return ucfirst($match[1]);
            }, trim($action, '_'));
        }

        if (method_exists($this, $action . 'Action')) {
            die("Error: the action already exists in controller file...");
        }
        
        if (isset($this->actions[$action])) {
            return true;
        }

        $actionPath = implode([
            'actions' . DIRECTORY_SEPARATOR,
            str_replace('_', DIRECTORY_SEPARATOR, $controller) . DIRECTORY_SEPARATOR,
            ucfirst($action) . '.php'
        ]);

        $this->actions += [ $this->getRequest()->getActionName() => $actionPath ];
    }
    
}
