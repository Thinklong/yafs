<?php

/**
 * Copyright (c) 2016 Leju Inc. All rights reserved.
 * RedisMysqlLib.php
 */
class Base_RedisMysqlLib extends Base_Dao
{

    protected $_dk;
    protected $_dk_len = 1;
    protected $_redis_server_info;
    protected $_conf;
    protected $_expired_time = 86400;

    /*
     * 绑定对应的mysql数据库的表和redis组
     */

    public function __construct()
    {
        $this->_redis_server_info = $this->_conf['redis_group'];
    }

    /*
     * 按照规则生成redis的key
     */

    protected function get_redis_key($query_params)
    {
        if (empty($query_params)) {
            return false;
        }
        $query_keys = $this->_conf['redis_keys'];
        $ret_key = "";

        foreach ($query_keys as $key) {
            if (!isset($query_params[$key])) {
                return false;
            }
            $ret_key .= $query_params[$key] . "_";
        }

        if ("" != $this->_conf['secure_key']) {
            $ret_key = md5($ret_key . $this->_conf['secure_key']);
        }
        $table_name = $this->get_table_and_db_name_by_dk_value($this->get_dk_value($query_params));
        $ret_key = $table_name . ":" . $ret_key;
        return $ret_key;
    }

    /*
     * 获取表名
     */

    public function get_table_and_db_name_by_dk_value($dk_value)
    {
        if (!$this->_dk) {
            return $this->_table;
        }
        $dk_value = strval($dk_value);
        $len = -1 * $this->_dk_len;
        $suffix = substr($dk_value, $len);
        return $this->_table . "_" . $suffix;
    }

    /*
     * 获取分库分表值
     */

    public function get_dk_value($data)
    {
        if (!$this->_dk) {
            return null;
        } elseif (empty($data[$this->_dk])) {
            return null;
        } else {
            return $data[$this->_dk];
        }
    }

    /*
     * 插入新记录
     */

    public function sync_insert($data, $time_out = 0)
    {
        $redissvr = $this->get_redis_servinfo();
        $key = $this->get_redis_key($data);
        $table = $this->get_table_and_db_name_by_dk_value($this->get_dk_value($data));
        $this->beginTransaction();
        $retdb = $this->insert($data, $table);
        if (!$retdb) {
            return $retdb;
        }
        $redis_data = $this->load($retdb, $this->_pk, $table);
        $redis_data['ENABLE'] = 1;
        $redis_data = $this->conver_kvalue_to_array($redis_data);
        $ret = RedisProxy::instance($redissvr)->hMSet($key, $redis_data);
        if (!$ret) {
            $this->rollBack();
            return false;
        }
        if (0 !== $time_out) {
            $ret = RedisProxy::instance($redissvr)->expire($key, $time_out);
            if (!$ret) {
                $this->rollBack();
                return false;
            }
        }
        $this->commit();
        return $retdb;
    }

    /*
     * 更新记录
     */

    public function sync_update($set_params, $where_params, $time_out = 0)
    {
        //校验where参数是否一致
        $key = $this->get_redis_key($where_params);
        if (false === $key) {
            return false;
        }
        //更新
        $table_name = $this->get_table_and_db_name_by_dk_value($this->get_dk_value($where_params));
        $redissvr = $this->get_redis_servinfo();
        $this->beginTransaction();
        $ret = $this->update(array_fill_keys(array_keys($where_params), '?'), $set_params, false, $where_params, $table_name);
        if (!$ret) {
            $this->rollBack();
            return false;
        }
        $set_params['ENABLE'] = 1;
        $redis_data = $this->conver_kvalue_to_array($set_params);
        $ret = RedisProxy::instance($redissvr)->hMSet($key, $redis_data);
        if (!$ret) {
            $this->rollBack();
            return false;
        }
        if (0 !== $time_out) {
            $ret = RedisProxy::instance($redissvr)->expire($key, $time_out);
            if (!$ret) {
                $this->rollBack();
                return false;
            }
        }
        $this->commit();
        return true;
    }

    /*
     * 删除记录
     */

    public function sync_delete($data)
    {
        $key = $this->get_redis_key($data);
        if (false === $key) {
            return false;
        }
        $where = $this->where($data);
        $table_name = $this->get_table_and_db_name_by_dk_value($this->get_dk_value($data));
        //删除
        $sql = "delete from {$table_name} where {$where}";
        $this->sql($sql);

        $exist = $this->exist_hash_data($key);
        if ($exist == 1) {
            $redissvr = $this->get_redis_servinfo();
            RedisProxy::instance($redissvr)->delete($key);
        }
    }

    /*
     * 获取键值
     */

    public function sync_find($where_params, $is_sync_redis = false)
    {
        $need_find_mysql = true;
        $ret = [];
        $key = $this->get_redis_key($where_params);
        if (false === $key) {
            return false;
        }

        $redissvr = $this->get_redis_servinfo();
        $result = RedisProxy::instance($redissvr)->hGetAll($key);
        if ($result) {
            $res_redis = $this->convert_array_to_kvalue($result);
            if ($is_sync_redis && isset($res_redis['ENABLE']) && 0 == $res_redis['ENABLE']) {
                return null;
            }
            $ret[0] = $res_redis;
            return $ret;
        }

        $table_name = $this->get_table_and_db_name_by_dk_value($this->get_dk_value($where_params));
        $where = $this->where($where_params);
        $opt = [
            'where' => $where,
            'table' => $table_name,
        ];
        $ret = $this->find($opt);
        if (!$ret) {
            if ($is_sync_redis) {
                $timeout = $this->getExpiredTime();
                $ret_redis = RedisProxy::instance($redissvr)->hMset($key,array('ENABLE',0));
                if($ret_redis){
                    RedisProxy::instance($redissvr)->expire($key,$timeout);
                }
            }
        } else {
            //查询操作，如果查redis没有，查mysql有的话，需要更新redis
            $timeout = $this->getExpiredTime($ret[0]);
            $redis_data = $ret[0] + ['ENABLE' => 1];
            $redis_data = $this->conver_kvalue_to_array($redis_data);
            $ret_redis = RedisProxy::instance($redissvr)->hMSet($key,$redis_data);
            if($ret_redis){
                RedisProxy::instance($redissvr)->expire($key,$timeout);
            }
        }

        return $ret;
    }

    /*
     * 获取redis服务信息
     */

    public function get_redis_servinfo()
    {
        return $this->_redis_server_info;
    }

    /*
     * 是否存在相应键值
     */

    public function exist_hash_data($key)
    {
        $redissvr = $this->get_redis_servinfo();
        return RedisProxy::instance($redissvr)->exists($key);
    }

    /*
     * 将普通数组转换为k-value数组
     * 例：from['a','b'] -> to['a'=>'b']
     */

    public function convert_array_to_kvalue(array $data)
    {
        $ret = [];
        for ($i = 0; $i < count($data); $i += 2) {
            $key = $data[$i];
            $val = $data[$i + 1];
            $ret[$key] = $val;
        }
        return $ret;
    }

    /*
     * 将k-value数组转换为普通数组
     * 例：from['a'=>'b']  ->  to['a','b']
     */

    public function conver_kvalue_to_array(array $data)
    {
        $ret = [];
        foreach ($data as $k => $v) {
            $ret[] = $k;
            $ret[] = $v;
        }
        return $ret;
    }

    /*
     * 计算过期时间
     */
    protected function getExpiredTime($data = [])
    {
        return $this->_expired_time;
    }
}
