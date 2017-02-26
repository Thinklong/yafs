<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Comm.php
 * 公共基类静态方法
 *
 */
class Public_Comm
{

    public static function array_rebuild(array $data, $col, array &$newdata = array())
    {
        if ($data)
        {
            foreach ($data as $value)
            {
                if (is_array($value) && isset($value[$col]))
                {
                    $newdata[$value[$col]] = $value;
                }
            }
        }

        return $newdata;
    }

    public static function array_column(array $data, $columnKey, array &$newdata)
    {
        if ($data)
        {
            foreach ($data as $key => $value)
            {
                if (is_array($value))
                {
                    self::array_column($value, $columnKey, $newdata);
                } else if ($key === $columnKey)
                {
                    $newdata[] = $value;
                }
            }
        }

        return true;
    }

    public static function object_to_array($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        $arr = array();
        foreach ($_arr as $key => $val)
        {
            $val = (is_array($val) || is_object($val)) ? self::object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    /**加载出ctl对应的action
     * @param $dir
     * @return array
     */
    public static function treeDirectory($dir)
    {
        $files = array();
        $file_names = @scandir($dir);

        if (is_array($file_names))
        {

            foreach ($file_names as $filename)
            {
                if ($filename == '.' || $filename == '..')
                {
                    continue;
                }
                $file_info = pathinfo($filename);
                if ($file_info['extension'] !== 'php')
                {
                    continue;
                }

                $files[] = rtrim(strtolower($filename), '.php');
            }
        }


        if (count($files) < 1)
        {
            return array();
        }
        return $files;
    }

    /**
     * 根据绝对路径，返回ctl目录下本类的方法【不包括继承及魔术方法】
     * @param $path 绝对路径
     * @return array|bool
     */
    public static function load_class($path)
    {
        if (!is_file($path)) return false;
        require_once($path);
        $info_arr = explode("controllers" . DIRECTORY_SEPARATOR, $path);
        $info = trim($info_arr[1], '.php');

        $ctl_class = str_replace(DIRECTORY_SEPARATOR, '_', $info) . 'Controller';
        $filter_methods = array('init', 'getRequest', 'getResponse', 'getModuleName', 'getView', 'initView', 'setViewpath', 'getViewpath', 'getViewpath', 'redirect', 'getInvokeArgs', 'getInvokeArg', 'forward');

        $exists_methods = get_class_methods($ctl_class);
        $ctl_action = [];
        if (count($exists_methods) > 0)
        {
            foreach ($exists_methods as $method)
            {
                if (substr($method, 0, 1) == '_' || in_array($method, $filter_methods)) continue;

                $ctl_action[] = substr($method, 0, strrpos($method, 'Action'));

            }
        }

        return $ctl_action;
    }


}