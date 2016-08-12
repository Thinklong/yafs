<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * Bytes.php
 * 
 * @author     yulong8@leju.com
 */

class Bytes
{
    public static function getBytes($string)
    {
        if (null === $string || "" === $string) {
            return null;
        }
        
        $stringArray = str_split((string) $string, 1);
        
        return array_map("ord", $stringArray);
    }
    
    public static function toString(array $bytes)
    {
        if (empty($bytes)) {
            return null;
        }
        
        $string = array_map("chr", $bytes);
        
        return implode("", $string);
    }
    
    public static function integerToBytes($value)
    {
        $value = intval($value);
        if ($value < 0) {
            return null;
        }
        
        $byte = array();
        $byte[] = ($value & 0xff);
        $byte[] = ($value >> 8 & 0xff);
        $byte[] = ($value >> 16 & 0xff);
        $byte[] = ($value >> 24 & 0xff);
        
        return $byte;
    }
    
    public static function bytesToInteger(array $bytes, $position)
    {
        $position = intval($position);
        if ($position < 0 || empty($bytes) || empty($bytes[$position + 3])) {
            return null;
        }
        
        $value = 0;
        $value = $bytes[$position + 3] & 0xff;
        $value <<= 8;
        $value |= $bytes[$position + 2] & 0xff;
        $value <<= 8;
        $value |= $bytes[$position + 1] & 0xff;
        $value <<= 8;
        $value |= $bytes[$position] & 0xff;
        
        return $value;
    }
}