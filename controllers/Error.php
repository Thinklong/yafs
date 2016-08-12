<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Error.php
 * 
 * @author     yulong8@leju.com
 */

class ErrorController extends Yaf_Controller_Abstract
{
    public function errorAction()
    {
        $exception = $this->getRequest()->getException();
        try
        {
            throw $exception;
            
        
        } catch (Yaf_Exception_LoadFailed $e)
        {
            echo $e->getMessage();
            //加载失败
        } catch (Yaf_Exception $e)
        {
            echo $e->getMessage();
            //其他错误
        }
        exit;
        AppResponse::statusCode(404);
        echo '<h1>404 Not Found</h1>';
        return false;
        
    }
}