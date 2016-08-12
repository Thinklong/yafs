<?php

class IndexController extends Yaf_Controller_Abstract
{
    public function indexAction()
    {
        $this->getView()->assign('content', 'Hello, Yaf');
        //echo 'Hello, Yaf';
        $this->getView()->display('index.html');
        exit;
    }
}
