<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * 
 * secret.php
 * 密钥配置文件
 * 说明：
 * whiltelist/blacklist 白名单/黑名单 IP 限制策略从全局配置文件[iptables]标签下读取
 * 
 * pubkey 公钥
 * prikey 私钥
 * whitelist ip白名单，空或者allow为不限制，支持 * - 区间
 * blacklist ip黑名单（从白名单中剔除），空或者none为不限制，支持 * - 区间
 * modules 开放模块，空或者all为不限制
 * 
 */

return [
    // tests 专用密钥
    /*'T001' => [
        'pubkey' => 'T001',
        'prikey' => '123456',
        'whitelist' => 'allow',
        'blacklist' => 'none',
        'modules' => 'all',
    ],*/
    //Manager专用签名
    'ManagerSign' => [
        'pubkey' => 'ManagerSign',
        'prikey' => '123456',
        'whitelist' => 'allow',
        'blacklist' => 'none',
        'modules' => 'all',
    ],
    
];
