<?php
/*
 *Copyright (C)2010 1verge.com 
 *file:RedisProxy.class.php
 *redis数据存储引擎代理接口类
 *Interface descripe: 
 *	1、通过一致性hash算法，自动查找redis数据存储引擎服务器。
 *	2、redis数据存储引擎中key[val,intval,sort_list,queue,hash]数据存储、查找接口。
 *Author:zhangyufeng 
 *Createtime:2010.10.22
 *MSN: zhangyufeng_syy@hotmail.com
 *Report Bugs:zhangyufeng@youku.com
 *Address:China BeiJing
 *Version:0.1.0
 *Latest modify time:2010.10.22
 */

define('REDISPROXY_SUCC',0);
define('ERR_CREATE_REDISPROXY', -200);
define('REDIS_PARAM_INT',1);
define('REDIS_PARAM_STR',2);
define('REDIS_PARAM_FLOAT',4);
define('REDIS_PARAM_CONSTR',8);
define('REDIS_PARAM_ARRAY',16);

define('RET_ERR_INDEX',0);
define('RET_HOST_INDEX',1);
define('RET_PORT_INDEX',2);
define('RET_PID_INDEX',3);
define('RET_DATATYPE_INDEX',4);
define('RET_VAL_INDEX',5);
define('RET_ARRAY_LEN',5);
define('RET_ARRAY_START',6);

define('REDIS_DT_ERR',0);
define('REDIS_DT_LINE',1);
define('REDIS_DT_INT',2);
define('REDIS_DT_FLOAT',4);
define('REDIS_DT_BULK',8);
define('REDIS_DT_MUTI_BULK',16);

define('ERR_IO_TIMEOUT',       106); // send或者recv数据超时
define('ERR_REDIS_CMD',        201); // 构建redis协议命令失败（多数是输入不对，比如输入了空串） 
define('ERR_REDIS_CONSISHASH', 204); // 一致性hash定位服务器失败（多数是输入的组名、机器IP或者端口跟配置不符或配置文件有误）
define('ERR_GET_SERVERINFO',   302); // 获取服务器配置信息失败（多数是redis_server_list.json配置文件写得不对） 

class RedisProxy{
	static private $instance__;
	private $host = "";
	private $port = 0;
	private $last_error = array();
	private $pid = 0;
	protected $serverinfo = array();
	protected $servgrp = "";

	public function get_return_val($resp){
		$keys = array(RET_ERR_INDEX, RET_HOST_INDEX, RET_DATATYPE_INDEX, RET_VAL_INDEX);
		$this->last_error = array();
		foreach ($keys as $k){
			$this->last_error[] = isset($resp[$k]) ? $resp[$k] : 'null';
		}
		if($resp[RET_ERR_INDEX] != REDISPROXY_SUCC){
			$error = array('ret'=>$this->get_last_error(), 'servgrp'=>$this->servgrp);
			trigger_error('redis error:' . json_encode($error), E_USER_WARNING);
			return false;
		}
		$this->host = $resp[RET_HOST_INDEX];
		$this->port = $resp[RET_PORT_INDEX];
		$this->pid  = $resp[RET_PID_INDEX];
		switch($resp[RET_DATATYPE_INDEX]){
			case REDIS_DT_ERR:
				return false;
			case REDIS_DT_LINE:
			case REDIS_DT_INT:
			case REDIS_DT_FLOAT:
			case REDIS_DT_BULK:
				return $resp[RET_VAL_INDEX];
			case REDIS_DT_MUTI_BULK:
				$array = array();
				$len = $resp[RET_ARRAY_LEN];
				$total = RET_ARRAY_START + $len;
				$j = 0;
				for($i = RET_ARRAY_START;$i<$total;$i++){
					$array[$j++] = $resp[$i];
				}
				return $array;
			default:
				return false;
		}
	}

	function __construct(){
	}
	/*描述：获取实例操作
	 *功能：当如果只传入$servgrp参数,则根据命令操作的键值自动选择服务器, 一致性hash可以按照ip，port或者srv_name进行
	 *		当指定host和port参数时，向指定的服务程序发送redis命令
	 *		当以srv_name做一致性hash且要指定server时，
	 *		将$host和$port参数设为空，传入对应的$servgrp和$srv_name参数
	 *		用户可以通过get_serverinfo接口 获取当前操作命令的服务器ip和端口;
	*/
	static public function &instance($servgrp="",$host="",$port=0, $srv_name=""){
		if (!isset(self::$instance__)) {
            $class = __CLASS__;
            self::$instance__ = new $class;
        }
		self::$instance__->set_server_info($servgrp,$host,$port,$srv_name);
        return self::$instance__;
	}
	protected function set_server_info($servgrp,$host,$port,$srv_name=''){
	    /* -- map sergrp --*/
	    $servgrp = self::map_group($servgrp); //add by wj
	    /* -- map sergrp -- */
		$this->servgrp = $servgrp;
		$this->serverinfo["s"] = $host;
		$this->serverinfo["p"] = intval($port);
		$this->serverinfo["n"] = $srv_name;
	}
	public function print_redis_resp($cmd,$key,$resp){
		if($resp === false){
			echo "execute cmd:$cmd key:$key failed.";
		}
		else{
			echo "execute cmd:$cmd key:$key succed.";
		}
		echo "return val is:";
		var_dump($resp);
		echo "</br>lasterr is:". json_encode($this->last_error). " host is:".$this->host ."|port is:" . $this->port;
		echo "</br>user set servinfo host:".$this->serverinfo["s"]."|port is:".$this->serverinfo["p"];
		echo "</br>";
	}
	static public function get_key_serverinfo($servgrp,$key){
	    /* -- map servgrp --*/
	    $servgrp = self::map_group($servgrp); //add by wj
	    /* -- map sergrp -- */
	    
	    
		$ret = get_key_serverinfo_RedisProxy($key,$servgrp);
		if(count($ret) > 1 && $ret[0] == 0){
			return split(":",$ret[1],2);
		}
		return false;
	}
	static public function get_group_serverinfo($servgrp){
	    /* -- map servgrp --*/
	    $servgrp = self::map_group($servgrp); //add by wj
	    /* -- map sergrp -- */
	    
	    
		$ret = get_group_serverinfo_RedisProxy($servgrp);
		if(count($ret) > 1 && $ret[0] == 0){
			$ary = split(" ", $ret[1]);
			for($i = 0; $i < count($ary); $i++)
			{
				$ary[$i] = split(":", $ary[$i], 2);
			}
			return $ary;
		}
		return false;
	}
	/*描述：通用操作
	 *功能：获取key对应的一致性hash的server name
	 *    如果group不是以servername做一致性hash，返回false
	*/
	static public function get_key_server_name_info($servgrp,$key){
	    /* -- map servgrp --*/
	    $servgrp = self::map_group($servgrp); //add by wj
	    /* -- map sergrp -- */
	    
		$ret = get_key_server_name_info_RedisProxy($key,$servgrp);
		if(count($ret) > 1 && $ret[0] == 0){
			return $ret[1];
		}
		return false;
	}
	/*描述：通用操作
	 *功能：获取group的所有server_name，如果group不是以servername做一致性hash，返回false
	*/
	static public function get_group_server_name_info($servgrp){
	    /* -- map servgrp --*/
	    $servgrp = self::map_group($servgrp); //add by wj
	    /* -- map sergrp -- */
	    
		$ret = get_group_server_name_info_RedisProxy($servgrp);
		if(count($ret) > 1 && $ret[0] == 0){
			$ary = split(" ", $ret[1]);
			return $ary;
		}
		return false;
	}
	/*描述：通用操作
	 *功能：获取group的一致性hash方式,0表示以ip port做一致性hash，1表示以server_name做一致性hash,出错返回false
	*/
	static public function get_group_hash_type($servgrp){
	    /* -- map servgrp --*/
	    $servgrp = self::map_group($servgrp); //add by wj
	    /* -- map sergrp -- */
	    
	    if (!function_exists('get_group_hash_type_info_RedisProxy')){
	    	return 0;
	    }
		$ret = get_group_hash_type_info_RedisProxy($servgrp);
		if(count($ret) > 1 && $ret[0] == 0){
			return $ret[1];
		}
		return false;
	}
	/*描述：通用操作
	 *功能：获取group某个server_name的repl set 信息
	*/
	static public function get_repl_set_info($servgrp, $srv_name){
		/* -- map servgrp --*/
		$servgrp = self::map_group($servgrp); //add by wj
		/* -- map sergrp -- */
		 
		 
		$ret = get_repl_set_info_RedisProxy($servgrp, $srv_name);
		if(count($ret) > 1 && $ret[0] == 0){
			$ary = split(";", $ret[1]);
			for($i = 0; $i < count($ary); $i++)
			{
			$ary[$i] = split(":", $ary[$i], 2);
			}
			return $ary;
		}
		return false;
	}	
	/*描述：通用操作
	 *功能：获取操作失败代码;
	*/
	public function get_last_error(){
		return $this->last_error;
	}
	/*描述：通用操作
	 *功能：获取当前执行redis命令的服务器信息
	 *返回：
	 *	数据类型：string
	 *	数据：ip|port
	*/
	public function get_serverinfo(){
		return array($this->host,$this->port);
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：设置key的生命周期
	 *参数：
	 *	$key 键
	 *	$time 键过期时间
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：当该键已经设置了expire并且键未过期时返回0,设置成功返回 1
	*/
	public function expire($key, $time){
		return $this->get_return_val(execmd_RedisProxy("EXPIRE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$time))));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：设置key的生命周期，以毫秒为单位
	*参数：
	*	$key 键
	*	$time 键过期时间,毫秒为单位
	*返回
	*	数据类型：成功-int，失败-false
	*	数据：当该键已经设置了expire并且键未过期时返回0,设置成功返回 1
	*/
	public function pexpire($key, $time){
		return $this->get_return_val(execmd_RedisProxy("PEXPIRE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$time))));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：设置key的生命周期的绝对时间，以秒为单位
	*参数：
	*	$key 键
	*	$time 键过期绝对时间，从 1970。1.1开始的秒数
	*返回
	*	数据类型：成功-int，失败-false
	*	数据：key不存在返回0，设置成功返回 1
	*/
	public function expireAt($key, $time){
		return $this->get_return_val(execmd_RedisProxy("EXPIREAT",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$time))));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：设置key的生命周期的绝对时间，以毫秒为单位
	*参数：
	*	$key 键
	*	$time 键过期时间的绝对时间,从 1970。1.1开始的毫秒数
	*返回
	*	数据类型：成功-int，失败-false
	*	数据：key不存在返回0，设置成功返回 1
	*/
	public function pexpireAt($key, $time){
		return $this->get_return_val(execmd_RedisProxy("PEXPIREAT",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$time))));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：重命名key，注意：该操作要指定server，不然新key和原key的一致性hash很可能不在同一个实例
	*参数：
	*	$key 原键值
	*	$newkey 新键值
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true，false
	*/
	public function rename($key, $newkey){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$newkey);
		$ret = $this->get_return_val(execmd_RedisProxy("RENAME",$key,$this->servgrp,$this->serverinfo,$ary_params));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：获取key的类型
	*参数：
	*	$key 键
	*返回
	*	数据类型：string
	*	数据：key的类型，不存在返回none
	*/
	public function type($key){
		return $this->get_return_val(execmd_RedisProxy("TYPE",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：去掉key的超时属性
	*参数：
	*	$key 键
	*返回
	*	数据类型：int
	*	数据：超时属性去掉返回1，如果key不存在或未设置超时，返回0
	*/
	public function persist($key){
		return $this->get_return_val(execmd_RedisProxy("PERSIST",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：获取object的REFCOUNT属性
	*参数：
	*	$key 键
	*返回
	*	数据类型：int 
	*	数据：返回object被引用的次数,，如果key对应的object不存在返回NULL
	*/
	public function objectRefcount($key){
		return $this->get_return_val(execmd_RedisProxy("OBJECT REFCOUNT",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：获取object的IDLETIME属性
	*参数：
	*	$key 键
	*返回
	*	数据类型：int
	*	数据：返回object空闲时间，以秒为单位,，如果key对应的object不存在返回NULL
	*/
	public function objectIdletime($key){
		return $this->get_return_val(execmd_RedisProxy("OBJECT IDLETIME",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(适用于所有的key类型[key-val,key-sortlist,key-unikeyset,key-hashtable])
	 *功能：获取object的编码方式属性
	*参数：
	*	$key 键
	*返回
	*	数据类型：string
	*	数据：返回object的编码方式,，如果key对应的object不存在返回NULL
	*/
	public function objectEncoding($key){
		return $this->get_return_val(execmd_RedisProxy("OBJECT ENCODING",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(适用于list, set sorted set)
	 *功能：排序
	*参数：
	*	$key 键
	*	$val 排序方式 , [BY pattern] [LIMIT offset count] [GET pattern [GET pattern ...]] [ASC|DESC] [ALPHA] 
	*        ASC升序排序，DESC降序排序，ALPHA以字母序排序，当val是由多个单词组成时，需要以array方式传递值，比如"LIMIT 3 5"，
	*        $val=array("LIMIT",3,5);
	*        
	*返回
	*	数据类型：成功-array，失败-false
	*	数据：返回排序之后的结构
	*/
	public function sort($key, $val){
		$ary_params = array();
		if (!empty($val)){
			if (is_array($val)){
				$i=0;
				foreach($val as $v){
					$ary_params[$i] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$v);
					$i++;
				}			
			}
			else{
				$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
			}
		}
		return $this->get_return_val(execmd_RedisProxy("SORT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：通用操作(key-val)
	 *功能：设置key上绑定的val值，key存在设置key的val，不存在增加一对key-val数据
	 *参数：
	 *	$key 键
	 *	$val 键值
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true，false
	*/
	public function set($key, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		$ret = $this->get_return_val(execmd_RedisProxy("SET",$key,$this->servgrp,$this->serverinfo,$ary_params));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	
	/*描述：通用操作(key-val)
	*功能：设置key上绑定的val值，key存在不对其进行更改，不存在增加一对key-val数据
	*参数：
	*	$key 键
	*	$val 键值
	*返回
	*	数据类型：成功-1，失败-0, 异常-false
	*/
	public function setnx($key, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		$ret = $this->get_return_val(execmd_RedisProxy("SETNX",$key,$this->servgrp,$this->serverinfo,$ary_params));
	
		return $ret;
	}
	
	/*描述：通用操作(key-val)
	 *功能：设置key上绑定的val值，key存在设置key的val，不存在增加一对key-val数据，并设置数据存活时间$timeout
	 *参数：
	 *	$key 键
	 *	$val 键值
	 *	$timeout 数据存活时间
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true，false
	*/
	public function setex($key,$val,$timeout){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$timeout);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		$ret = $this->get_return_val(execmd_RedisProxy("SETEX",$key,$this->servgrp,$this->serverinfo,$ary_params));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	/*描述：通用操作(key-val)
	 *功能：设置key上绑定的val值，key存在设置key的val，不存在增加一对key-val数据，并设置数据存活时间$timeout
	*参数：
	*	$key 键
	*	$val 键值
	*	$timeout 数据存活时间,以毫秒为单位
	*返回
	*	数据类型：成功-true，失败-false
	*	数据：true，false
	*/
	public function psetex($key,$val,$timeout){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$timeout);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		$ret = $this->get_return_val(execmd_RedisProxy("PSETEX",$key,$this->servgrp,$this->serverinfo,$ary_params));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	
	/*描述：通用操作(key-val [[key-val ...]])
	 *功能：设置多个key上绑定的val值，key存在设置key的val，不存在增加一对key-val数据，
	 *    多个key需要在同一个redis实例上
	*参数：
	*	$kvs 需要设置的键值对
	*返回
	*	数据类型：成功-true，失败-false
	*	数据：true，false
	*/
	public function mSet($kvs){
		if (empty($kvs) || !is_array($kvs)){
			return false;
		}
		$ary_params = array();
		$key='';
		foreach($kvs as $k => $val){
			if (empty($key)){
				$key = $k;
				$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
				continue;
			}
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$k);
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		}
		$ret = $this->get_return_val(execmd_RedisProxy("MSET",$key,$this->servgrp,$this->serverinfo,$ary_params));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	
	/*描述：通用操作(key-val [[key-val ...]])
	 *功能：设置多个key上绑定的val值，如果有某一个key存在，则所有key都不会设置
	 *    多个key需要在同一个redis实例上
	*参数：
	*	$kvs 需要设置的键值对
	*返回
	*	数据类型：成功-1，失败-0, 异常-false
	*/
	public function mSetnx($kvs){
		if (empty($kvs) || !is_array($kvs)){
			return false;
		}
		$ary_params = array();
		$key='';
		foreach($kvs as $k => $val){
			if (empty($key)){
				$key = $k;
				$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
				continue;
			}
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$k);
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		}
		$ret = $this->get_return_val(execmd_RedisProxy("MSETNX",$key,$this->servgrp,$this->serverinfo,$ary_params));
	
		return $ret;
	}
	
	/*描述：通用操作(key-val)
	 *功能：获取key上绑定的val值,并在key上设置新值
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-string，失败-false
	 *	数据：键不存在 null，存在 key上绑定的val值
	*/
	public function getset($key,$newval){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$newval);
		return $this->get_return_val(execmd_RedisProxy("GETSET",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：通用操作(key-val)
	 *功能：获取key上绑定的val值
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-string，失败-false
	 *	数据：键不存在 null，存在 key上绑定的val值
	*/
	public function get($key){
		return $this->get_return_val(execmd_RedisProxy("GET",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(key-val)
	 *功能：获取key上绑定的val值的长度
	*参数：
	*	$key 键
	*返回
	*	数据类型：成功-int 失败-false
	*	数据：键不存在 返回0，存在返回 key上绑定的val值的长度, 如果key对应的value不是string类型返回false
	*/
	public function strlen($key){
		return $this->get_return_val(execmd_RedisProxy("STRLEN",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(key-val)
	 *功能：设置key的部分值，从offset开始
	*参数：
	*	$key 键
	*	$offset 偏移
	*	$value 值
	*返回
	*	数据类型：成功-int 失败-false
	*	数据：修改之后的字符串长度
	*/
	public function setRange($key, $offset, $value){
		return $this->get_return_val(execmd_RedisProxy("SETRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$offset),
				array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$value))));
	}
	/*描述：通用操作(key-val)
	 *功能：范围key的值对应start到end部分的字串
	*参数：
	*	$key 键
	*	$start 起始位置
	*	$end 终止位置
	*返回
	 *	数据类型：成功-string，失败-false
	 *	数据：key对应的子串值
	*/
	public function getRange($key, $start, $end){
		return $this->get_return_val(execmd_RedisProxy("GETRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
				array("t"=>REDIS_PARAM_INT,"d"=>(int)$end))));
	}
	/*描述：通用操作(key-intval)
	 *功能：对key上绑定的intval值自动加 $amount 指定的值
	 *参数：
	 *	$key 键
	 *	$amount 自动增加的值[default = 1]
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：键值不存在，自动执行增加键值操作，并初始化键值为0
	 *		  返回该键值上绑定值执行加法操作后的值
	*/
	public function incr($key,$amount=1){
		if($amount == 1){
			return $this->get_return_val(execmd_RedisProxy("INCR",$key,$this->servgrp,$this->serverinfo));
		}
		else{
			return $this->get_return_val(execmd_RedisProxy("INCRBY",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$amount))));
		}
	}
	public function incr_float($key,$amount=1.0){
		return $this->get_return_val(execmd_RedisProxy("INCRBYFLOAT",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_FLOAT,"d"=>(float)$amount))));
	}
	/*描述：通用操作(key-intval)
	 *功能：对key上绑定的intval值自动减 $amount 指定的值
	 *参数：
	 *	$key 键
	 *	$amount 自动减少的值[default = 1]
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：键值不存在，自动执行增加键值操作，并初始化键值为0
	 *		  返回该键值上绑定值执行减法操作后的值
	*/
	public function decr($key,$amount=1){
		if($amount == 1){
			return $this->get_return_val(execmd_RedisProxy("DECR",$key,$this->servgrp,$this->serverinfo));
		}
		else{
			return $this->get_return_val(execmd_RedisProxy("DECRBY",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$amount))));
		}
	}
	/*描述：通用操作(key-any data[val,sort_list,queue])
	 *功能：键值是否存在
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：键值存在 1，不存在 0
	*/
	public function exists($key){
		return $this->get_return_val(execmd_RedisProxy("EXISTS",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(key-any data[val,sort_list,queue])
	 *功能：删除键
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：键存在 1，不存在 0
	*/
	public function delete($key){
		return $this->get_return_val(execmd_RedisProxy("DEL",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：在sort_list批量插入一组节点
	 *参数：
	 *	$key 键
	 *	$ary 节点数据组(score0,member0,score1,member1,....score[n],member[n]);
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：插入成功返回 新增节点个数,如果失败返回小于0,如果该节点存在于sort_list中返回 0
	*/
	public function zBatchAdd($key,$ary){
		if(!is_array($ary) || empty($ary)){
			return false;
		}
		$ary_params = array();
		for($i = 0;$i<count($ary);$i++){
			$ary_params[$i] = array('t'=>REDIS_PARAM_ARRAY, 'd'=>(string)$ary[$i]);
		}
		$ret = @$this->get_return_val(execmd_RedisProxy('ZBATCHADD',$key,$this->servgrp,$this->serverinfo,$ary_params));
		if ($ret === false){
			$err = $this->get_last_error();
			if ($err[0] == ERR_REDIS_CMD || ($err[2] == REDIS_DT_ERR && strncasecmp($err[3], 'ERR unknown', 11) == 0)){
				return $this->get_return_val(execmd_RedisProxy('ZADD',$key,$this->servgrp,$this->serverinfo,$ary_params));
			}
		}
		return $ret;
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：在sort_list插入一个节点
	 *参数：
	 *	$key 键
	 *	$score 排序值
	 *	$val 节点值
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：插入成功返回 1,如果该节点存在于sort_list中返回 0
	*/
	public function zAdd($key,$score,$val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$score);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("ZADD",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：为有序集 key 的成员 member 的 score 值加上增量 increment 
	*参数：
	*	$key 键
	*	$increment 增量值，可以是浮点数，负数
	*	$member 成员
	*返回
	*	数据类型：成功-int，失败-false
	*	数据：操作成功返回最终score值 
	*/
	public function zIncrBy($key,$increment,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$increment);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("ZINCRBY",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：获取关联排序队列(key--sort_list)元素得分
	 *功能：获取sort_list中节点的得分
	 *参数：
	 *	$key 键
	 *	$member 节点值
	 *返回
	 *	数据类型：成功-double float，失败-false
	 *	数据：成功返回结点得分（字符串形式的浮点数）
	*/
	public function zScore($key,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("ZSCORE",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list) Available since 2.1.6. 
	 *功能：获取sort_list上节点成员排序在一段数值区间中的节点(降序)
	 *参数：
	 *	$key 键
	 *	$range 查询排序值数值区间条件
			array("max","min") 表示  min<= score <= max;
			array("max","-INF") 表示  score <= max;
			array("INF","min")  表示  score >= min;
			array("(max","(min") 表示 min < score < max;
	 *	$withscore 是否返回排序值
	 *返回
	 *	数据类型：成功-array，失败-false
	 *	数据：返回节点成员信息
				witchscore array("member1","score1","member2","score2"....);
				not withscore array("member1","member2",.....);
	*/
	public function zRevRangeByScore($key,$range,$withscore=false,$limit=-1,$skip=0){
		if(!is_array($range) || count($range) != 2){
			return false;
		}
		$max = PHP_INT_MAX;
		$min = -$max;
		if($range[0] == "INF"){
			$range[0] = $max;
		}
		if($range[1] == "-INF"){
			$range[1] = $min;
		}
		$ary_params = array();
		for($i = 0;$i<count($range);$i++){
			$ary_params[$i] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$range[$i]);
		}
		if($limit >0){
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>"limit");
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$skip);
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$limit);
		}
		if($withscore){
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>"withscores");
		}
		return $this->get_return_val(execmd_RedisProxy("ZREVRANGEBYSCORE",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：获取sort_list上节点成员排序在一段数值区间中的节点(升序)
	 *参数：
	 *	$key 键
	 *	$range 查询排序值数值区间条件
			array("min","max") 表示  min<= score <= max;
			array("-INF","max") 表示  score <= max;
			array("min","INF")  表示  score >= min;
			array("(min","(max") 表示 min < score < max;
	 *	$withscore 是否返回排序值
	 *返回
	 *	数据类型：成功-array，失败-false
	 *	数据：返回节点成员信息
				witchscore array("member1","score1","member2","score2"....);
				not withscore array("member1","member2",.....);
	*/
	public function zRangeByScore($key,$range,$withscore=false,$limit=-1,$skip=0){
		if(!is_array($range) || count($range) != 2){
			return false;
		}
		$max = PHP_INT_MAX;
		$min = -$max;
		if($range[0] == "-INF"){
			$range[0] = $min;
		}
		if($range[1] == "INF"){
			$range[1] = $max;
		}
		$ary_params = array();
		for($i = 0;$i<count($range);$i++){
			settype($range[$i],"string");
			$ary_params[$i] = array("t" => REDIS_PARAM_ARRAY,"d"=>$range[$i]);
		}
		if($limit >0){
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>"limit");
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$skip);
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$limit);
		}
		if($withscore){
			$ary_params[$i++] = array("t" => REDIS_PARAM_ARRAY,"d"=>"withscores");
		}
		return $this->get_return_val(execmd_RedisProxy("ZRANGEBYSCORE",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：获取sort_list上节点成员排序在一段数值区间中的节点个数
	 *参数：
	 *	$key 键
	 *	$range 查询排序值数值区间条件
			array("min","max") 表示  min<= score <= max;
			array("-INF","max") 表示  score <= max;
			array("min","INF")  表示  score >= min;
			array("(min","(max") 表示 min < score < max;
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：返回该段数据的个数
	*/
	public function zCount($key,$range){
		if(!is_array($range) || count($range) != 2){
			return false;
		}
		$max = PHP_INT_MAX;
		$min = -$max;
		if($range[0] == "-INF"){
			$range[0] = $min;
		}
		if($range[1] == "INF"){
			$range[1] = $max;
		}
		$ary_params = array();
		for($i = 0;$i<count($range);$i++){
			settype($range[$i],"string");
			$ary_params[$i] = array("t" => REDIS_PARAM_ARRAY,"d"=>$range[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("ZCOUNT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：获取sort_list节点个数
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：sort_list节点个数
	*/
	public function zSize($key){
		return $this->get_return_val(execmd_RedisProxy("ZCARD",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：升序获取sort_list的一段节点数据
	 *参数：
	 *	$key 键
	 *	$start 开始位置(int)
	 *	$end 结束位置 -1为队列的尾部(int)
	 *  $withscore 是否返回节点上的排序值
	 *返回
	 *	数据类型：成功-array，无数据-空array，失败-false
	 *	数据：sort_list上指定开始位置和结束位置的一段节点数据或者节点排序值数据
	*/
	public function zRange($key,$start,$end,$withscore=false){
		if($withscore){
			return $this->get_return_val(execmd_RedisProxy("ZRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
														 array("t"=>REDIS_PARAM_INT,"d"=>(int)$end),
														 array("t"=>REDIS_PARAM_CONSTR,"d"=>"withscores"))
									));
		}
		else{
			return $this->get_return_val(execmd_RedisProxy("ZRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
														 array("t"=>REDIS_PARAM_INT,"d"=>(int)$end))
									));
		}
		
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：降序获取sort_list的一段节点数据
	 *参数：
	 *	$key 键
	 *	$start 开始位置
	 *	$end 结束位置 -1为队列的尾部
	 *  $withscore 是否返回节点上的排序值
	 *返回
	 *	数据类型：成功-array，无数据-空array，失败-false
	 *	数据：sort_list上指定开始位置和结束位置的一段节点数据或者节点排序值数据
	*/
	public function zReverseRange($key,$start,$end,$withscore=false){
		if($withscore){
			return $this->get_return_val(execmd_RedisProxy("ZREVRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
																				  array("t"=>REDIS_PARAM_INT,"d"=>(int)$end),
																				  array("t"=>REDIS_PARAM_CONSTR,"d"=>"withscores"))));
		}
		else{
			return $this->get_return_val(execmd_RedisProxy("ZREVRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
																				  array("t"=>REDIS_PARAM_INT,"d"=>(int)$end))));
		}
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：删除sort_list的节点数据
	 *参数：
	 *	$key 键
	 *	$val sort_list的节点数据
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：成功 1 失败 0
	*/
	public function zDelete($key,$val){
		if($val == ""){
			return false;
		}
		$ary_params = array();
		if(is_array($val)) {
			for($i = 0;$i<count($val);$i++){
				$ary_params[$i] = array('t'=>REDIS_PARAM_ARRAY, 'd'=>(string)$val[$i]);
			}
		} else {
			$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		}
		return $this->get_return_val(execmd_RedisProxy("ZREM",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：删除sort_list索引区间[$start, $stop]的节点数据
	 *参数：
	 *	$key 键
	 *	$val sort_list的节点数据
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：成功则返回实际删除的节点个数
	*/
	public function zRemRangeByRank($key,$start,$stop){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$start);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$stop);
		return $this->get_return_val(execmd_RedisProxy("ZREMRANGEBYRANK",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排序队列(key--sort_list)
	 *功能：删除sort_list评分区间[$min_score, $max_score]的节点数据
	 *参数：
	 *	$key 键
	 *	$val sort_list的节点数据
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：成功则返回实际删除的节点个数
	*/
	public function zRemRangeByScore($key,$min_score,$max_score){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$min_score);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$max_score);
		return $this->get_return_val(execmd_RedisProxy("ZREMRANGEBYSCORE",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：获取关联排序队列(key--sort_list)元素得分
	 *功能：获取sort_list中节点的排名，按得分由低到高
	*参数：
	*	$key 键
	*	$member 节点值
	*返回
	*	数据类型：成功-int失败-NULL
	*	数据：成功返回结点排名，不存在返回NULL
	*/
	public function zRank($key,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("ZRANK",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：获取关联排序队列(key--sort_list)元素得分
	 *功能：获取sort_list中节点的排名，按得分由高到低
	*参数：
	*	$key 键
	*	$member 节点值
	*返回
	*	数据类型：成功-int失败-NULL
	*	数据：成功返回结点排名,不存在返回NULL
	*/
	public function zRevRank($key,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("ZREVRANK",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：从队列的头部插入一个节点
	 *参数：
	 *	$key 键
	 *	$val 插入到queue队列上的节点值
	 *返回：
	 *	数据类型：成功-int，失败-false
	 *	数据：queue队列节点个数
	*/
	public function lPush($key, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("LPUSH",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：当key关联的队列存在时,从队列的头部插入一个节点
	*参数：
	*	$key 键
	*	$val 插入到queue队列上的节点值
	*返回：
	*	数据类型：int
	*	数据：queue队列节点个数
	*/
	public function lPushX($key, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("LPUSHX",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：从队列头部获取一个节点，并从队列中删除该节点
	 *参数：
	 *	$key 键
	 *返回：
	 *	数据类型：成功-string，空链表-null，失败-false
	 *	数据：queue队列节点值
	*/
	public function lPop($key){
		return $this->get_return_val(execmd_RedisProxy("LPOP",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：从队列的尾部插入一个节点
	 *参数：
	 *	$key 键
	 *	$val 插入到队列上的值
	 *返回：
	 *	数据类型：成功-int，失败-false
	 *	数据：queue队列节点个数
	*/
	public function rPush($key, $val){
		$ary_params = array();
		if (is_array($val)){
			for($i = 0; $i < count($val); $i++){
				$ary_params[$i] = array('t'=>REDIS_PARAM_ARRAY, 'd'=>(string)$val[$i]);
			}
		}
		else {
			$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		}
		return $this->get_return_val(execmd_RedisProxy("RPUSH",$key,$this->servgrp,$this->serverinfo,$ary_params));
    }
    /*描述：键上关联队列(key-queue)
     *功能：当key关联的队列存在时,从队列的尾部插入一个节点
    *参数：
    *	$key 键
    *	$val 插入到队列上的值
    *返回：
    *	数据类型：int
    *	数据：queue队列节点个数
    */
    public function rPushX($key, $val){
    	$ary_params = array();
    	$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
    	return $this->get_return_val(execmd_RedisProxy("RPUSHX",$key,$this->servgrp,$this->serverinfo,$ary_params));
    }
	/*描述：键上关联队列(key-queue)
	 *功能：从队列尾部获取一个节点，并从队列中删除该节点
	 *参数：
	 *	$key 键
	 *返回：
	 *	数据类型：成功-string，空链表-null，失败-false
	 *	数据：queue队列节点值
	*/
	public function rPop($key){
		return $this->get_return_val(execmd_RedisProxy("RPOP",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：获取队列的节点个数
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：queue队列节点个数
	*/
	public function lSize($key){
		return $this->get_return_val(execmd_RedisProxy("LLEN",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：获取queue队列上的一段数据
	 *参数：
	 *	$key 键
	 *	$start 开始位置
	 *	$end 结束位置 -1为队列的尾部
	 *返回
	 *	数据类型：成功-array，无数据-空array，失败-false
	 *	数据：queue队列上指定开始位置和结束位置的一段节点数据
	*/
	public function lGetRange($key, $start, $end){
		return $this->get_return_val(execmd_RedisProxy("LRANGE",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
											         array("t"=>REDIS_PARAM_INT,"d"=>(int)$end))));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：保留queue队列上的一段数据，删除其他数据
	 *参数：
	 *	$key 键
	 *	$start 开始位置
	 *	$end 结束位置 -1为队列的尾部
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true,false
	*/
	public function lTrim($key, $start, $end){
		$ret = $this->get_return_val(execmd_RedisProxy("LTRIM",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$start),
																		  array("t"=>REDIS_PARAM_INT,"d"=>(int)$end))));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	/*描述：键上关联队列(key-queue)
	 *功能：获取queue队列上位置为 $index 的节点数据
	 *参数：
	 *	$key 键
	 *	$index 队列的索引位置 -1为队列的尾部
	 *返回
	 *	数据类型：成功-string，失败-false
	 *	数据：节点数据，节点不存在 null
	*/
	public function lIndex($key,$index){
		return $this->get_return_val(execmd_RedisProxy("LINDEX",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$index))));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：从队列的$pivot节点前插入val
	*参数：
	*	$key 键
	*	$pivot 队列上的节点
	*	$val 插入到queue队列上的节点值
	*返回：
	*	数据类型：int
	*	数据：$pivot不存在返回-1,否者返回queue队列节点个数
	*/
	public function lInsertBefore($key, $pivot, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"BEFORE");
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$pivot);
		$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("LINSERT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：从队列的$pivot节点后插入val
	*参数：
	*	$key 键
	*	$pivot 队列上的节点
	*	$val 插入到queue队列上的节点值
	*返回：
	*	数据类型：int
	*	数据：$pivot不存在返回-1,否者返回queue队列节点个数
	*/
	public function lInsertAfter($key, $pivot, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"AFTER");
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$pivot);
		$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("LINSERT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联队列(key-queue)
	 *功能：队列中$index位置的节点值设置为$value
	*参数：
	*	$key 键
	*	$index 位置索引
	*	$value 节点值
	*返回
	*	数据类型：成功-true，失败-false
	*	数据：true，false
	*/
	public function lSet($key, $index, $value){
		$ret = $this->get_return_val(execmd_RedisProxy("LSET",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$index),
				array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$value))));
		if($ret == "OK"){
			return true;
		}
		return false;
		
	}
	/*
	 *功能：在set批量插入一组节点
	 *	$key 键
	 *	$ary 数组成员
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该成员存在返回0，不存在返回1
	*/
	public function sBatchAdd($key,$ary){
		if(!is_array($ary) || empty($ary)){
			return false;
		}
		$ary_params = array();
		for($i = 0;$i<count($ary);$i++){
			$ary_params[$i] = array('t'=>REDIS_PARAM_ARRAY, 'd'=>(string)$ary[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("SADD",$key,$this->servgrp,$this->serverinfo,$ary_params));
	
	}
	
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：在key-uni_queue上插入一个成员
	 *参数：
	 *	$key 键
	 *	$member 成员
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该成员存在返回0，不存在返回1
	*/
	public function sAdd($key,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("SADD",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：从key-uni_queue上删除一个成员
	 *参数：
	 *	$key 键
	 *	$member 成员
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该成员不存在返回0，存在返回1
	*/
	public function sRem($key,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("SREM",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：在key-uni_queue上弹出一个成员
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-string,失败-false;
	 *	数据：弹出成员的值
	*/
	public function sPop($key){
		return $this->get_return_val(execmd_RedisProxy("SPOP",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：获取key-uni_queue所有成员
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-array,失败-false
	 *	数据：所有成员值的数组
	*/
	public function sMembers($key){
		return $this->get_return_val(execmd_RedisProxy("SMEMBERS",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：随机获取key-uni_queue一个成员
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-array,失败-false
	 *	数据：所有成员值的数组
	*/
	public function sRandMember($key){
		return $this->get_return_val(execmd_RedisProxy("SRANDMEMBER",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：获取key-uni_queue成员个数
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：成员个数
	*/
	public function sSize($key){
		return $this->get_return_val(execmd_RedisProxy("SCARD",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联排重队列(key-uni_queue)
	 *功能：从key-uni_queue上删除一个成员
	*参数：
	*	$key 键
	*	$member 成员
	*返回
	*	数据类型：成功-int，失败-false
	*	数据：如果该成员不存在返回0，存在返回1
	*/
	public function sScan($key,$cursor, $count=0){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$cursor);
		if (!empty($count)){
			$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$count);
		}
		return $this->get_return_val(execmd_RedisProxy("SSCAN",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：批量获取某个redis实例进程上的多个键值
	 *功能：批量获取某个redis实例进程上的多个键值；
	 *    若要获取不同redis实例进程上的多个键值，请用BatchKeyvalDataEngine.class.php中的batch_getdata_from_redis。
	 *参数：
	 *	$keys 键数组
	 *返回
	 *	数据类型：成功-array，失败-false
	*/
	public function MGet($keys){
		if(empty($keys)|| !is_array($keys)){
			return false;
		}
		$ary_valid_key = array();
		for($i = 0;$i<count($keys);$i++){
			if(!empty($keys[$i])){
				$ary_valid_key[] = $keys[$i];
			}
		}
		if(empty($ary_valid_key)){
			return false;
		}
		if(count($ary_valid_key) > 1){
			$key = $ary_valid_key[0];
			$ary_params = array();
			for($i = 1;$i<count($ary_valid_key);$i++){
				$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$ary_valid_key[$i]);
			}
			return $this->get_return_val(execmd_RedisProxy("MGET",$key,$this->servgrp,$this->serverinfo,$ary_params));
		}
		else{
			$val = $this->get($ary_valid_key[0]);
			if($val === false){
				return false;
			}
			return array($val);
		}
	}
	public function hMget($key,$memberkeys){
		if(empty($memberkeys) || empty($key)){
			return false;
		}
		$ary_params = array();
		for($i=0;$i<count($memberkeys);$i++){
			$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkeys[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("HMGET",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	public function hMset($key,$members){
		if(empty($members) || empty($key)){
			return false;
		}
		$ary_params = array();
		for($i=0;$i<count($members);$i+=2){
			$k = strval($members[$i]);
			$v = strval($members[$i+1]);
			
			if ($k != '' && $v != ''){
				$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>$k);
				$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>$v);
			}
		}
		$ret = $this->get_return_val(execmd_RedisProxy("HMSET",$key,$this->servgrp,$this->serverinfo,$ary_params));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：在key-hashtable增加一个成员，如果该成员存在，则更新该成员的值
	 *参数：
	 *	$key 键
	 *	$memberkey 成员键
	 *	$memberval 成员键值
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该键值存在，返回0，否则返回1
	*/
	public function hSet($key,$memberkey,$memberval){
		if(empty($key) || empty($memberkey)){
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberval);
		return $this->get_return_val(execmd_RedisProxy("HSET",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：在key-hashtable增加一个成员，如果该成员存在，不更新成员值
	 *参数：
	 *	$key 键
	 *	$memberkey 成员键
	 *	$memberval 成员键值
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该键值存在，返回0，否则返回1
	*/
	public function hSetNX($key,$memberkey,$memberval){
		if(empty($key) || empty($memberkey)){
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberval);
		return $this->get_return_val(execmd_RedisProxy("HSETNX",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：获取key-hashtable成员值
	 *参数：
	 *	$key 键
	 *	$memberkey 成员键
	 *	$memberval 成员键值
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：如果该键存在于hashtable中返回键值，否则返回 null
	*/
	public function hGet($key,$memberkey){
		if(empty($key) || empty($memberkey)){
			return null;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		return $this->get_return_val(execmd_RedisProxy("HGET",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：删除key-hashtable成员值
	 *参数：
	 *	$key 键
	 *	$memberkey 成员键
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：如果该键存在于hashtable中返回 1,否则返回 0
	*/
	public function hDel($key,$memberkey){
		if($memberkey == ""){
			return false;
		}
		$ary_params = array();
		if(is_array($memberkey)) {
			for($i = 0;$i<count($memberkey);$i++){
				$ary_params[$i] = array('t'=>REDIS_PARAM_ARRAY, 'd'=>(string)$memberkey[$i]);
			}
		} else {
			$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		}
		return $this->get_return_val(execmd_RedisProxy("HDEL",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：key-hashtable成员个数
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：hashtable 成员个数
	*/
	public function hLen($key){
		return $this->get_return_val(execmd_RedisProxy("HLEN",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：获取key-hashtable所有成员的键
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-array,失败-false
	 *	数据：返回数组array("memberkey[0]","memberkey[1]",..."memberkey[n]");
	*/
	public function hKeys($key){
		return $this->get_return_val(execmd_RedisProxy("HKEYS",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：获取key-hashtable所有成员的键值
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-array,失败-false
	 *	数据：返回数组array("memberval[0]","memberval[1]",..."memberval[n]");
	*/
	public function hVals($key){
		return $this->get_return_val(execmd_RedisProxy("HVALS",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：获取key-hashtable所有成员的键和键值
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：成功-array，失败-false
	 *	数据：返回数组array("memberkey[0]","memberval[0]","memberkey[1]","memberval[1]",..."memberkey[n]","memberval[n]");
	*/
	public function hGetAll($key){
		return $this->get_return_val(execmd_RedisProxy("HGETALL",$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：判断key-hashtable成员是否存在
	 *参数：
	 *	$key 键
	 *	$memberkey 成员键值
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：存在返回1，不存在返回0
	*/
	public function hExists($key,$memberkey){
		if($memberkey == ""){
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		return $this->get_return_val(execmd_RedisProxy("HEXISTS",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：增加key-hashtable所有成员的键值
	 *参数：
	 *	$key 键
	 *	$memberkey 成员键
	 *	$amount 成员键值增加的值
	 *返回
	 *	数据类型：成功-int,失败-false
	 *	数据：成功返回该成员修改后的值
	*/
	public function hIncrBy($key,$memberkey,$amount){
		if($memberkey == ""){
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$amount);
		return $this->get_return_val(execmd_RedisProxy("HINCRBY",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：增加key-hashtable所有成员的键值
	*参数：
	*	$key 键
	*	$memberkey 成员键
	*	$amount 成员键值增加的值
	*返回
	*	数据类型：成功-int,失败-false
	*	数据：成功返回该成员修改后的值
	*/
	public function hIncrByFloat($key,$memberkey,$amount){
		if($memberkey == ""){
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$memberkey);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$amount);
		return $this->get_return_val(execmd_RedisProxy("HINCRBYFLOAT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：通用操作(key-val)
	 *功能：设置key上追加的val值，key存在追加key的val，不存在增加一对key-val数据
	 *参数：
	 *	$key 键
	 *	$val 键值
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true，false
	*/
	public function append($key, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("APPEND",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
    /*描述：通用操作(key-val)
	 *功能：获取key剩下的过期时间
	 *参数：
	 *	$key 键
	 *返回
	 *	数据类型：-int
	 *	数据：键不存在或没有设置过期时间则返回-1，否则返回正整数或0
	*/
	public function ttl($key){
		return $this->get_return_val(execmd_RedisProxy('TTL',$key,$this->servgrp,$this->serverinfo));
	}
	/*描述：通用操作(key-val)
	 *功能：获取key剩下的过期时间，以毫秒为单位
	*参数：
	*	$key 键
	*返回
	*	数据类型：-int
	*	数据：键不存在或没有设置过期时间则返回-1，否则返回正整数或0
	*/
	public function pttl($key){
		return $this->get_return_val(execmd_RedisProxy('PTTL',$key,$this->servgrp,$this->serverinfo));
	}
    /*描述：基于string的bit操作
	 *功能：对key所存储的字符串值，设置或清除指定偏移量上的位(bit)
	 *参数：
	 *	$key 键
	 *  $offset 偏移量
	 *	$val bit值，可为0或1
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：0或1
	*/
	public function setbit($key, $offset, $val){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$offset);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		return $this->get_return_val(execmd_RedisProxy("SETBIT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
    /*描述：基于string的bit操作
	 *功能：对key所存储的字符串值，获取指定偏移量上的位(bit)
	 *参数：
	 *	$key 键
	 *  $offset 偏移量
	 *	$val bit值，可为0或1
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：0或1
	*/
	public function getbit($key, $offset){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$offset);
		return $this->get_return_val(execmd_RedisProxy("GETBIT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：基于string的bit操作
	 *功能：key所对应的字符串在range范围内包含的bit数
	*参数：
	*	$key 键
	*  $range string的范围
	*返回
	*	数据：bit数
	*/
	public function bitcount($key, $range=""){
		$ary_params = array();
		if(!empty($range)){
			if(count($range) != 2){
				return false;
			}
			$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$range[0]);
			$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$range[1]);
			
		}
		return $this->get_return_val(execmd_RedisProxy("BITCOUNT",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
    /*描述：键上关联排重队列(key-uni_queue)
	 *功能：判断key-uni_queue成员是否存在
	 *参数：
	 *	$key 键
	 *	$member 成员
	 *返回
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该成员不存在返回0，存在返回1
	*/
	public function sIsMember($key,$member){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$member);
		return $this->get_return_val(execmd_RedisProxy("SISMEMBER",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}

	/*描述：阻塞型的LPOP
	 *功能：从队列头部获取一个节点，并从队列中删除该节点
	*参数：
	*	$key 键
	*   $timeout 超时值,0表示一直等待
	*返回：
	*	数据类型：返回array，
	*	数据：成功返回array，第一个值为list name，第二个值为pop出的节点值，超时返回空
	*/
	public function blPop($key, $timeout){
		return $this->get_return_val(execmd_RedisProxy("BLPOP",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$timeout))));
	}
	
	/*描述：阻塞型的RPOP
	 *功能：从队列尾部获取一个节点，并从队列中删除该节点
	*参数：
	*	$key 键
	*   $timeout 超时值，0表示一直等待
	*返回：
	*	数据类型：返回array，
	*	数据：成功返回array，第一个值为list name，第二个值为pop出的节点值，超时返回空
	*/
	public function brPop($key, $timeout){
		return $this->get_return_val(execmd_RedisProxy("BRPOP",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_INT,"d"=>(int)$timeout))));
	}
	
	/*描述：键上关联队列(key-queue)
	 *功能：删除队列中数据
	*参数：
	*	$key 键
	*	$count， 》0，从头到尾依次删除count个等于value的节点
	*           <0，从尾到头依次删除|count|个等于value的节点
	*           =0, 删除所有等于value的节点
	*	$value
	*返回
	*	数据类型：int
	*	数据：返回删除的节点数
	*/
	public function lRem($key, $count, $value){
		return $this->get_return_val(execmd_RedisProxy("LREM",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$count),
				array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$value))));
	}
	
	/*描述：订阅发布(Pub/Sub)
	 *功能：发布消息
	*参数：
	*	$key channel
	*	$message 发布的消息
	*返回
	*	数据类型：int
	*	数据：返回订阅者的个数
	*/
	public function publish($key, $message){
		return $this->get_return_val(execmd_RedisProxy("PUBLISH",$key,$this->servgrp,$this->serverinfo,array(array("t"=>REDIS_PARAM_ARRAY,"d"=>(string)$message))));
	}
	
    /*描述：lua 脚本
	 *功能：执行lua脚本, 令格式为"EVAL script numkeys key [key ...] arg [arg ...]"
	 *参数：
	 *	$key 键, 用于一致性hash选择server,实际发送redis命令是会忽略该值
	 *	$script, 要执行的lua脚本
	 *	$members 命令格式为"EVAL script numkeys key [key ...] arg [arg ...]", script后面的参数都作为members的内容传入
	 *返回
	 *	数据类型：根据脚本的不同返回不同类型的值
	 *	数据：
	*/
	public function lua_eval($key, $script, $members){
		$ary_params = array();
		$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$script);
		foreach($members as $val){
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		}
		return $this->get_return_val(execmd_RedisProxy("EVAL",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}

    /*描述：lua 脚本
	 *功能：执行lua脚本, 令格式为"EVALSHA script_sha numkeys key [key ...] arg [arg ...]"
	 *参数：
	 *	$key 键, 用于一致性hash选择server,实际发送redis命令是会忽略该值
	 *	$script_sha, 要执行的lua脚本的sha值
	 *	$members 命令格式为"EVALSHA script_sha numkeys key [key ...] arg [arg ...]", scrip_sha后面的参数都作为members的内容传入
	 *返回
	 *	数据类型：根据脚本的不同返回不同类型的值
	 *	数据：
	*/
	public function lua_evalsha($key, $script_sha, $members){
		$ary_params = array();
		$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$script_sha);
		foreach($members as $val){
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$val);
		}
		return $this->get_return_val(execmd_RedisProxy("EVALSHA",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	
	/**
	 * 将group映射成为一个统一的group  
	 * 这是一个补丁，group不同，hostx相同，会创建不同的tcp连接，本函数将相同host的group映射成为同一个
	 *
	 */
	static public function map_group($servgrp){
	    return  (isset($GLOBALS['redisproxyGroupMap']) && isset($GLOBALS['redisproxyGroupMap'][$servgrp])) ? $GLOBALS['redisproxyGroupMap'][$servgrp] :  $servgrp;
	}
};
?>
