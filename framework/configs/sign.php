<?php

/**
 * 签名配置
 *  签名白名单
 *  签名类型
 *  
 *  whitelist   不签名白名单
 *      Manager_User:Login|Index  ‘:’前为控制器名称（支持不同层级）
 *                                ‘:’后为Action 用‘|’区分一个控制器下的多个Action
 *      Manager:ALL               支持不同层级白名单 ‘:’后为ALL 表示控制器下全部
 *  
 *  sign_type    签名方式
 *      'Manager' => 'HMAC_MD5'    只支持最顶级的控制器下统一使用一种签名方式
 *                                 签名方式有：MD5、HMAC_MD5、RSA... 可以在签名工厂里增加新的签名方式
 */

return [
    'whitelist' => [
        'Manager_User:Login|Index',
        'Manager:ALL',
        
    ],
    'sign_type' => [
        'Manager' => 'HMAC_MD5', // MD5、HMAC_MD5、RSA...
    ],
];