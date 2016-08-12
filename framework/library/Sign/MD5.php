<?php
/**
 * Sign_MD5
 * MD5加密
 *
 */

class Sign_MD5 extends Sign
{

    protected function doSign($signStr, $privateKey)
    {

        return md5($signStr . $privateKey);
    }
} 