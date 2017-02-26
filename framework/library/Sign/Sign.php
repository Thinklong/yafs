<?php

/**
 * 签名类接口
 *
 */
abstract class Sign_Sign
{



    /**
     * 创建签名接口
     * @param unknown $params
     * @param unknown $key
     */
    abstract public function createSign($params, $key);

    /**
     * 验证签名接口
     * @param unknown $params
     * @param unknown $key
     * @param unknown $sign
     */
    abstract public function verifySign($params, $key, $sign);
    
    
    abstract public function decrypt($sign, $key);
    
    /**
     * 将参数转换成字符串
     * @param unknown $data
     * @return string
     */
    protected function toString($data)
    {
        if (is_string($data))
        {
            return $data;
        }
        unset($data['sign']);
        ksort($data);
        reset($data);
        
        $string = $glue = '';
        foreach ($data as $key => $value)
        {
            if ('0' !== (string)$value && empty($value))
            {
                continue;
            }
            $string .= "{$glue}{$key}={$value}";
            $glue = '&';
        }
        return $string;
    }
}