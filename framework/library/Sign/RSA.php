<?php
/**
 * Sign_RSA
 * RSA加密
 *
 */

class Sign_RSA extends Sign
{

    protected function doSign($signStr, $privateKey)
    {
        echo $signStr . PHP_EOL;
        return 'sign';
    }
}