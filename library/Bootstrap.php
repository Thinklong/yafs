<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Bootstrap.php
 * 
 * @author     yulong8@leju.com
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 */

class Bootstrap extends Yaf_Bootstrap_Abstract
{
    
    /**
     * 配置初始化
     */
    public function _initConfig()
    {
        //把配置保存起来
        $arrConfig = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $arrConfig);
        
        defined("PATH_CUSTOM_FW_LIBRARY") and Yaf_Loader::getInstance()->setLibraryPath(PATH_CUSTOM_FW_LIBRARY, true);
        
        unset($arrConfig);
    }
    
    /**
     * 错误处理函数初始化
     */
    public function _initError()
    {
        Yaf_Dispatcher::getInstance()->setErrorHandler(['Error', 'errorHandler']);
        register_shutdown_function(['Error', 'shutdownErrorHandler']);
    }

    /**
     * 类命名空间初始化
     */
    public function _initLocalNameSpace()
    {
        $localLiraryPath = Yaf_Loader::getInstance()->getLibraryPath();
        $localLibrary = Yaf_Registry::get('config')->get('application')->get('localLibrary');
        $local = $localLibrary ? array_map('ucfirst', array_filter(explode(',', $localLibrary))) : null;
        $local && Yaf_Loader::getInstance()->registerLocalNamespace($local);
        
        $basePath = $localLiraryPath . DIRECTORY_SEPARATOR . 'Base' . DIRECTORY_SEPARATOR;
        Yaf_Loader::getInstance()->import($basePath . 'Controller.php');
        Yaf_Loader::getInstance()->import($basePath . 'Action.php');
        Yaf_Loader::getInstance()->import($basePath . 'Dao.php');
        Yaf_Loader::getInstance()->import($basePath . 'Route.php');
    }
    
    /**
     * 初始化参数解析
     */
    public function _initParseParams()
    {
        Handle_Cli::initialize();
    }
    
    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        $route = new Base_Route();
        $dispatcher->getRouter()->addRoute("name", $route);
    }

    /**
     * 视图初始化
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        //Yaf_Dispatcher::getInstance()->disableView();
        //在这里注册自己的view控制器，例如smarty,firekylin
        //if (($config = Yaf_Registry::get('config'))) {
        if ($dispatcher->getRequest()->isXmlHttpRequest())
        {
            $dispatcher->autoRender(false);
        }
        else 
        {
            $view = new Smarty_Adapter(null, Yaf_Registry::get("config")->get("smarty"));
            
            Yaf_Dispatcher::getInstance()->setView($view);
        }
    }
    
    /**
     * 布局插件初始化/注册
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initLayout(Yaf_Dispatcher $dispatcher)
    {
        $layout = new LayoutPlugin('Layout/layout.html');
        
        $dispatcher->registerPlugin($layout);
    }
    
    /**
     * 插件初始化
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initPlugins(Yaf_Dispatcher $dispatcher)
    {
        $logPlugin = new LogPlugin();
        $dispatcher->registerPlugin($logPlugin);
    }
    
    
    
}