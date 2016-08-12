<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * 
 * Validate.php
 * 数据验证基类
 * 
 * @author     thinklong89@gmail.com
 */

/**
 * usage
$data = array(
    'id'     => 8,
    'sex'    => 'F',
    'tags'   => array('foo' => 3, 'bar' => 7),
    'age'    => 8,
    'email'  => 'foo@bar.com',
    'date'   => '2012-12-10',
    'body'   => 'foobarbarfoo',
);

$rules = array(
    'id'     => array('required' => true, 'type' => 'int'),
    'sex'    => array('in' => array('F', 'M')),
    'tags'   => array('required' => true, 'each' => array('type' => 'int')),
    'age'    => array('type' => 'int', 'range' => array(38, 130), 'msg' => 'age must be 18~130'),
    'email'  => array('type' => 'email'),
    'date'   => array('type' => 'date'),
    'body'   => array('required' => true, 'range' => array(1, 500))
);

var_dump(Ext_Validate::check($data, $rules));
**/

class Validate
{
    /**
     * Validate Errors
     *
     * @var array
     */
    public static $errors = array();

    /**
     * Check if is not empty
     *
     * @param string $str
     * @return boolean
     */
    public static function notEmpty($str, $trim = true)
    {
        if (is_array($str)) {
            return 0 < count($str);
        }

        return strlen($trim ? trim($str) : $str) ? true : false;
    }

    /**
     * Match regex
     *
     * @param string $value
     * @param string $regex
     * @return boolean
     */
    public static function match($value, $regex)
    {
        return preg_match($regex, $value) ? true : false;
    }

    /**
     * Max
     *
     * @param mixed $value numbernic|string
     * @param number $max
     * @return boolean
     */
    public static function max($value, $max, $number = false)
    {
        is_string($value) && false === $number && $value = strlen($value);
        return $value <= $max;
    }

    /**
     * Min
     *
     * @param mixed $value numbernic|string
     * @param number $min
     * @return boolean
     */
    public static function min($value, $min, $number = false)
    {
        is_string($value) && false === $number && $value = strlen($value);
        return $value >= $min;
    }

    /**
     * Range
     *
     * @param mixed $value numbernic|string
     * @param array $max
     * @return boolean
     */
    public static function range($value, $range, $number = false)
    {
        is_string($value) && false === $number && $value = strlen($value);
        return (($value >= $range[0]) && ($value <= $range[1]));
    }

    /**
     * Check if in array
     *
     * @param mixed $value
     * @param array $list
     * @return boolean
     */
    public static function in($value, $list)
    {
        return in_array($value, $list);
    }

    /**
     * Check if is email
     *
     * @param string $email
     * @return boolean
     */
    public static function email($email)
    {
        return preg_match('/^[a-z0-9_\-]+(\.[_a-z0-9\-]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)$/', $email) ? true : false;
    }

    /**
     * Check if is url
     *
     * @param string $url
     * @return boolean
     */
    public static function url($url)
    {
        return preg_match('/^((https?|ftp|news):\/\/)?([a-z]([a-z0-9\-]*\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(\/[a-z0-9_\-\.~]+)*(\/([a-z0-9_\-\.]*)(\?[a-z0-9+_\-\.%=&amp;]*)?)?(#[a-z][a-z0-9_]*)?$/i', $url) ? true : false;
    }

    /**
     * Check if is ip
     *
     * @param string $ip
     * @return boolean
     */
    public static function ip($ip)
    {
        return ((false !== ip2long($ip)) && (long2ip(ip2long($ip)) === $ip));
    }

    /**
     * Check if is date
     *
     * @param string $date
     * @return boolean
     */
    public static function date($date)
    {
        return preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $date) ? true : false;
    }

    /**
     * Check if is datetime
     *
     * @param string $datetime
     * @return boolean
     */
    public static function datetime($datetime, $format = 'Y-m-d H:i:s')
    {
        return $datetime && ($time = strtotime($datetime)) && ($datetime == date($format, $time));
    }

    /**
     * Check if is numbers
     *
     * @param mixed $value
     * @return boolean
     */
    public static function number($value)
    {
        return is_numeric($value);
    }

    /**
     * Check if is int
     *
     * @param mixed $value
     * @return boolean
     */
    public static function int($value)
    {
        return is_int($value);
    }

    /**
     * Check if is digit
     *
     * @param mixed $value
     * @return boolean
     */
    public static function digit($value)
    {
        return is_int($value) || ctype_digit($value);
    }

    /**
     * Check if is string
     *
     * @param mixed $value
     * @return boolean
     */
    public static function string($value)
    {
        return is_string($value);
    }

    /**
     * Check if is json
     *
     * @param mixed $value
     * @return boolean
     */
    public static function json($value)
    {
        return null !== json_decode($value);
    }

    /**
     * Check
     *
     * $rules = array(
     *     'required' => true if required , false for not
     *     'type'     => var type, should be in ('email', 'url', 'ip', 'date', 'number', 'int', 'string')
     *     'regex'    => regex code to match
     *     'func'     => validate function, use the var as arg
     *     'max'      => max number or max length
     *     'min'      => min number or min length
     *     'range'    => range number or range length
     *     'msg'      => error message,can be as an array
     * )
     *
     * @param array $data
     * @param array $rules
     * @param boolean $onceCheck
     * @param boolean $ignorNotExists
     * @return boolean
     */
    public static function check($data, $rules , $onceCheck = false, $ignorNotExists = false, $clearErrors = true)
    {
        if ($clearErrors) {
            self::$errors = array();
        }

        foreach ($rules as $key => $rule) {
            $rule += array('required' => false, 'msg' => 'Unvalidated');

            // deal with not existed
            if (!isset($data[$key])) {
                if ($rule['required'] && !$ignorNotExists) {
                    self::$errors[$key] = $rule['msg'];
                }
                
                if (!$onceCheck || !$rule['required']) {
                    continue;
                } else {
                    break;
                }
            }

            if (!self::_check($data[$key], $rule)) {
                self::$errors[$key] = $rule['msg'];
                if (!$onceCheck) {
                    continue;
                } else {
                    break;
                }
            }

            if (isset($rule['rules'])) {
                $tmp = self::check($data[$key], $rule['rules'], $onceCheck, $ignorNotExists, false);
                if (0 !== $tmp['code']) {
                    self::$errors[$key] = $tmp['msg'];
                }
            }
        }

        return self::$errors;
    }

    /**
     * Check value
     *
     * @param mixed $value
     * @param array $rule
     * @return mixed string as error, true for OK
     */
    protected static function _check($data, $rule)
    {
        $flag = true;
        foreach ($rule as $key => $val) {
            switch ($key) {
            	case 'required':
            		if ($val) $flag = self::notEmpty($data);
            		break;

                case 'func':
                    $flag = false;
                    if (is_callable($val)) {
                        $flag = call_user_func($val, $data);
                    } 
//                    $flag = call_user_func(array(__CLASS__, $val), $data);
                    break;

                case 'regex':
                    $flag = self::match($data, $val);
                    break;

                case 'type':
                    $flag = self::$val($data);
                    break;

                case 'max':
                case 'min':
                case 'max':
                case 'range':
                    $flag = self::$key($data, $val, isset($rule['type']) && $rule['type'] == 'number');
                    break;

                case 'each':
                    $val += array('required' => false);
                    $separator = empty($val['sep']) ? ',' : $val['sep'];
                    unset($val['sep']);
                    $data = is_string($data) ? ("" !== $data ? explode($separator, $data) : array()) : $data;
                    foreach ($data as $item) {
                        if (!$flag = self::_check($item, $val)) break;
                    }
                    break;
            	default:
            		break;
            }
            if (!$flag) {
                return false;
            }
        }

        return true;
    }
}
