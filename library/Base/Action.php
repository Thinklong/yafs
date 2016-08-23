<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Controller.php
 * 动作基类
 * 
 * @author     thinklong89@gmail.com
 */

abstract class Base_Action extends Yaf_Action_Abstract
{
    /**
     * 是否检验参数的合法性
     * @var boolean 
     */
    protected $_validateParameters = true;
    
    /**
     * 参数检验基础规则
     * @var type 
     * 如果需要校验必传参数，在下面加必填参数
     */
    protected $_baseRules = [
        //'pub_key'   => [ 'required' => true, 'msg' => 'pub_key parameter is missing.' ],
        //'sign_type' => [ 'required' => true, 'msg' => 'sign_type parameter is missing.' ],
        //'sign'      => [ 'required' => true, 'msg' => 'sign parameter is missing.' ],
    ];
    
    /**
     * 参数检验规则
     * @var type 
     */
    protected $_rules = [];

    /**
     * 参数列表
     * @var type 
     */
    protected $_parameters = [];

    /**
     * 动作解析入口
     */
    abstract public function process();
    
    /**
     * Yaf 默认动作解析入口
     */
    final public function execute()
    {
        $this->beforeAction();
        
        $this->process();
        
        $this->afterAction();
    }
    
    /**
     * 解析前动作
     */
    final protected function beforeAction()
    {
        if (true !== ($info =$this->validateParameters()))
        {
            $this->response(Public_Error::ERR_PARAM, null, ['info' => $info]);
        }
        else if ($this->isVerify())
        {
            if (!$this->verifySign())
            {
                $curSign = ['sign' => $this->getParam('sign')];
                $this->response(Public_Error::ERR_SIGN, null, ['info' => $curSign]);
            }
        }
        /*
        else if (!$this->verifyIp())
        {
            $clientIP = $clientIp = AppRequest::instance()->clientIp();
            $this->response(Public_Error::ERR_WHITE_LIST, null, ['ip' => $clientIP]);
        }*/
        
        $this->setTplParams();
    }
    
    /**
     * 解析后动作
     */
    final protected function afterAction()
    {
        
    }
    
    /**
     * 检验参数
     * @return boolean
     */
    final protected function validateParameters()
    {
        if (!isset($this->_validateParameters) || !$this->_validateParameters) {
            return true;
        }
        
        if (isset($this->_rules) && is_array($this->_rules) && $this->_rules) {
            $rules = $this->_baseRules + $this->_rules;
        } else {
            $rules = $this->_baseRules;
        }
        
        $parameters = array_merge(array_keys($this->_baseRules), $this->_parameters);
        $data = $this->_parameters ? $this->filterParams($parameters) : $this->getParams();
        
        $result = Validate::check($data, $rules, true);

        return empty($result) ? true : $result;
    }
    
    /**
     * 是否要验签
     * @return boolean
     */
    final protected function isVerify()
    {
        if (($white_list = $this->getWhiteList()) === true)
        {
            return true;
        }
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();
        $ctl_tmp = '';
        foreach (explode('_', $controller) as $key=>$val)
        {
            if ($ctl_tmp == '')
            {
                $ctl_tmp = $ctl = $val;
            }
            else 
            {
                $ctl_tmp = $ctl = "{$ctl_tmp}_{$val}";
            }
            //一层一层校验是否在白名单里
            if (isset($white_list[$ctl]) && $controller != $ctl)
            {
                return false;
            }
            else if (isset($white_list[$ctl]) && $controller == $ctl)
            {
                return !in_array($action, $white_list[$ctl]);
            }
        }
        return true;
    }
    
    /**
     * 获取是否验签白名单
     * @return boolean
     */
    final private function getWhiteList()
    {
        $cache_name = '_sign_white_list_config';
        if (Yaf_Registry::has($cache_name))
        {
            return Yaf_Registry::get($cache_name);
        }
        $sign_config = Handle_Config::load('sign');
        if (!isset($sign_config['whitelist']) || empty($sign_config['whitelist']))
        {
            return true;
        }
        $whitelist_tree = [];
        foreach ($sign_config['whitelist'] as $key=>$val)
        {
            list($ctl, $act) = explode(':', $val);
            if ($act === 'ALL')
            {
                $ctl_tree = true;
            }
            else
            {
                $ctl_tree = explode('|', $act);
            }
            $whitelist_tree[$ctl] = $ctl_tree;
        }
        Yaf_Registry::set($cache_name, $whitelist_tree);
        return $whitelist_tree;
    }
    
    
    /**
     * 验证签名
     * 
     * @param array $data
     * @return boolean
     */
    final protected function verifySign(array $data = [])
    {
        if (!isset($this->_validateParameters) || !$this->_validateParameters)
        {
            return true;
        }
        
        $pub_key = $this->getParam("pub_key");
        //$sign_type = $this->getParam("sign_type");
        $sign = $this->getParam("sign");
        if (!$pub_key || !$sign) {
            return false;
        }

        empty($data) and ($data = $this->getParams());
        $seckey = Handle_Sign::getPriKey($pub_key);
        
        //获取签名方式
        $sign_config = Handle_Config::load('sign');
        if (!isset($sign_config['sign_type']) || empty($sign_config['sign_type']))
        {
            return true;
        }
        $sign_type = '';
        $ctl = $this->getRequest()->getControllerName();
        if (isset($sign_config['sign_type'][$ctl]) && empty($sign_config['sign_type'][$ctl]))
        {
            $sign_type = $sign_config['sign_type'][$ctl];
        }
        $data += array('pub_key' => $pub_key);
        return Handle_Sign::vSign($sign_type, $data, $sign, $seckey);
    }
    
    /**
     * 验证 IP
     * @return boolean
     */
    final protected function verifyIp()
    {
        if (!isset($this->_validateParameters) || !$this->_validateParameters)
        {
            return true;
        }
        
        $pub_key = $this->getParam("pub_key");
        return Handle_Sign::verifyIp($pub_key);
    }

    /**
     * 过滤参数数据
     * 
     * @param array $keys
     * @return array
     */
    final protected function filterParams(array $keys = [])
    {
        if (!$keys && !$this->_parameters)
        {
            return [];
        }
        
        return AppRequest::instance()->filterParams($keys ? $keys : $this->_parameters);
    }
    
    /**
     * 获取单个参数
     * 
     * @param string $key 键值
     * @param mixed $default 默认值
     * @return string
     */
    final protected function getParam($key, $default = null)
    {
        return AppRequest::instance()->getParam($key, $default);
    }
    
    /**
     * 获取所有参数
     * 
     * @return type
     */
    final protected function getParams()
    {
        return AppRequest::instance()->getParams();
    }

    /**
     * 输出响应
     * 
     * @param type $errno
     * @param type $result
     * @param type $params
     */
    final protected function response($errno, $result = null, $params = null)
    {
        return AppResponse::response($errno, $result, $params);
    }
    
    
    /**
     * Assign variables to the template
     * Allows setting a specific key to the specified value, OR passing
     * an array of key => value pairs to set en masse.
     *
     * @see __set()
     * @param string|array $spec The assignment strategy to use (key or
     * array of key => value pairs)
     * @param mixed $value (Optional) If assigning a named variable,
     * use this as the value.
     * @return void
     */
    final protected function assign($spec, $value = null)
    {
        return $this->getView()->assign($spec, $value);
    }
    
    final protected function display($tpl, array $value = null)
    {
        return $this->getView()->display($tpl, $value);
    }
    
    
    /**
     * 设置常用模板变量
     */
    private function setTplParams()
    {
        $params = [
        ];
        $this->assign($params);
    }
    
    
    
    
    
    
    
    
    
}
