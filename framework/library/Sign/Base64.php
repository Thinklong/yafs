<?php
/**
 * Sign_Base64
 * base 64 加密
 *
 */

class Sign_Base64 extends Sign
{

    protected function doSign($signStr, $privateKey)
    {

        return base64_encode($signStr);

    }
} 