<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Comm.php
 * 公共基类静态方法
 * 
 */
        
class Public_Comm
{
    
    public static function array_rebuild(array $data, $col, array &$newdata = array())
    {
        if ($data) {
            foreach ($data as $value) {
                if (is_array($value) && isset($value[$col])) {
                    $newdata[$value[$col]] = $value;
                }
            }
        }
        
        return $newdata;
    }
    
    public static function array_column(array $data, $columnKey, array &$newdata)
    {
        if ($data) {
            foreach ($data as $key => $value) {
               if (is_array($value)) {
                   self::array_column($value, $columnKey, $newdata);
               } else if ($key === $columnKey) {
                   $newdata[] = $value;
               }
            }
        }
        
        return true;
    }
    
    public static function object_to_array($obj)
    {
        var_dump(get_object_vars($obj));
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        var_dump($_arr);
        foreach ($_arr as $key=>$val)
        {
            $val = (is_array($val) || is_object($val)) ? self::object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }
    
    
}