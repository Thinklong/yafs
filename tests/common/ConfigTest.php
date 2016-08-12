<?php
/**
 * ConfigTest
 * 测试框架Config工具类
 *
 */
class ConfigTest {

    public function testConfigGet() {

        $val = Config::get('config.A001.pubkey');

        print_r($val);
        $val = Config::get('config.A001.seckey');

        print_r($val);
        $val = Config::get('A001.seckey');

        print_r($val);
    }
}
