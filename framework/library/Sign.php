<?php
/**
 * Sign
 * 签名验证基类
 *
 *
 */

abstract class Sign
{

    const TYPE_RSA = 1;
    const TYPE_HMAC_MD5 = 2;
    const TYPE_NORMAL_MD5 = 3;

    public static function factory($type = Sign::TYPE_HMAC_MD5)
    {

        switch($type) {
            case self::TYPE_HMAC_MD5:
                return new Sign_HMACMD5();
            case self::TYPE_RSA:
                return new Sign_RSA();
            case self::TYPE_NORMAL_MD5:
                return new Sign_MD5();
            default:
                throw new Exception('unkown sign type');
        }

    }


     public function createSign($params, $privateKey) {

         $signStr = $this->createSignString($params);

         return $this->doSign($signStr, $privateKey);
     }

     public function verifySign($params, $privateKey, $sign) {

         $signValue = $this->createSign($params, $privateKey);

         return ($signValue === $sign);
     }

    protected function createSignString($params) {
        if (!is_array($params)) {
            return $params;
        }

        ksort($params);
        reset($params);

        $signPars = '';
        while(list($k, $v) = each($params)){
            if('' === $v) continue;
            $signPars .= $k . '=' . $v . '&';
        }
        // 去掉最后一个 "&"
        $signPars = substr($signPars, 0, strlen($signPars)-1);

        return $signPars;
    }
    abstract protected function doSign($signStr, $privateKey);

} 
