<?php
/*
 *file:RedisDBProxy.class.php
 *redisdb数据存储引擎代理接口类
 *Interface descripe: 
 *	1、通过一致性hash算法，自动查找redisdb数据存储引擎服务器。
 *	2、redisdb数据存储引擎中遍历接口。
 *Author:pengjinzhen 
 *Createtime:2012.02.15
 *MSN: jinzhenpeng@hotmail.com
 *Report Bugs:pengjinzhen@youku.com
 *Address:China BeiJing
 *Version:0.1.0
 *Latest modify time:2012.02.15
 */
class RedisDBProxy extends RedisProxy{
	static private $instance__;
	/*描述：获取实例操作
	 *功能：当如果只传入$servgrp参数,则根据命令操作的键值自动选择服务器, 一致性hash可以按照ip，port或者srv_name进行
	 *		当指定host和port参数时，向指定的服务程序发送redis命令
	 *		当以srv_name做一致性hash且要指定server时，
	 *		将$host和$port参数设为空，传入对应的$servgrp和$srv_name参数
	 *		用户可以通过get_serverinfo接口 获取当前操作命令的服务器ip和端口;
	*/
	static public function &instance($servgrp="",$host="",$port=0,$srv_name=""){
		if (!isset(self::$instance__)) {
			$class = __CLASS__;
			self::$instance__ = new $class;
		}
		self::$instance__->set_server_info($servgrp,$host,$port,$srv_name);
		return self::$instance__;
	}
	/*描述：遍历操作(iter_id-num-is_get_value)
	 *功能：从第一个Key开始向后遍历kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的下一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是第1个key，第2个key。。。第num个key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是第1个key，第1个value，第2个key，第2个value。。。第num个key，第num个value。
	*/
	public function dbFirst($iter_id, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBFIRST",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-num-is_get_value)
	 *功能：从最后一个Key开始向前遍历kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的前一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是最后1个key，倒数第2个key。。。倒数第num个key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是最后1个key，最后1个value，倒数第2个key，倒数第2个value。。。倒数第num个key，倒数第num个value。
	*/
	public function dbLast($iter_id, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBLAST",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-key-num-is_get_value)
	 *功能：从某一个Key开始向后遍历kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$key 遍历的起始key值（key必须在db中存在，否则会获取失败）
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的下一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是Key自己，第1个后继key。。。第num-1个后继key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是Key自己，Key的value，第1个后继key，第1个后继value。。。第num-1个后继key，第num-1个后继value。
	*/
	public function dbNext($iter_id, $key, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBNEXT",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-key-num-is_get_value)
	 *功能：从某一个Key开始向前遍历kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$key 遍历的起始key值（key必须在db中存在，否则会获取失败）
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的前一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是Key自己，第1个前继key。。。第num-1个前继key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是Key自己，Key的value，第1个前继key，第1个前继value。。。第num-1个前继key，第num-1个前继value。
	*/
	public function dbPrev($iter_id, $key, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBPREV",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-key-num-is_get_value)
	 *功能：先定位一个Key（该Key可以不存在于db中），再开始向后遍历kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$key 遍历的起始key值（若Key存在于db中，则db为起始key；否则选取db中Key的最小后继为起始key。）
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的下一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是Key自己，第1个后继key。。。第num-1个后继key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是Key自己，Key的value，第1个后继key，第1个后继value。。。第num-1个后继key，第num-1个后继value。
	*/
	public function dbSeekNext($iter_id, $key, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBSEEKNEXT",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-key-num-is_get_value)
	 *功能：先定位一个Key（该Key可以不存在于db中），再开始向前遍历kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$key 遍历的起始key值（若Key存在于db中，则db为起始key；否则选取db中Key的最大前继为起始key。）
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的下一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是Key自己，第1个前继key。。。第num-1个前继key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是Key自己，Key的value，第1个前继key，第1个前继value。。。第num-1个前继key，第num-1个前继value。
	*/
	public function dbSeekPrev($iter_id, $key, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBSEEKPREV",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-start_key-end_key)
	 *功能：先定位两个Key，再计算该Key区间内有多少个有效key。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$start_key 区间起始key值
	 *	$end_key 区间终止key值
	 *返回
	 *  若start_key存在于db中，则起始key值为start_key；否则选取start_key的最大前继为起始key，start_key前面有“(”表示计算时去掉start_key。
	 *  若end_key存在于db中，则终止key值为end_key；否则选取end_key的最小后继为起始key，end_key前面有“(”表示计算时去掉end_key。
	 *  返回Key区间中有效key的个数。
	*/
	public function dbRangeCount($iter_id, $start_key, $end_key){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$start_key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$end_key);
		return $this->get_return_val(execmd_RedisProxy("DBRANGECOUNT",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：遍历操作(iter_id-start_key-end_key-num-is_get_value)
	 *功能：先定位两个Key，再获取该Key区间内的kv数据。
	 *参数：
	 *	$iter_id 游标id（由dbIterCreate生成）
	 *	$start_key 区间起始key值
	 *	$end_key 区间终止key值
	 *	$num 取回的记录个数，最少取一个，最多取N个（N可通过dbconfig接口获取），小于1时会被重置为1。
	 *  $is_get_value 返回记录中是否返回value
	 *返回
	 *  返回的第一个参数是获取到的记录数，正常情况下等于num值（当后面没有记录时，有可能小于num）。
	 *  返回的第二个参数是获取num条记录后的下一个key（若不存在则返回字符串“KEY_NOT_EXISTS”），可以用来循环遍历整个库。
	 *  若没有WITHVALUES参数，则从第三个参数开始依次是Key自己，第1个后继key。。。第num-1个后继key。
	 *  若有WITHVALUES参数，则从第三个参数开始依次是Key自己，Key的value，第1个后继key，第1个后继value。。。第num-1个后继key，第num-1个后继value。
	*/
	public function dbRange($iter_id, $start_key, $end_key, $num, $is_get_value = true){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$start_key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$end_key);
		$ary_params[2] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$num);
		if ($is_get_value) {
			$ary_params[3] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)"WITHVALUES");
		}
		return $this->get_return_val(execmd_RedisProxy("DBRANGE",(string)$iter_id,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：增删改操作(DBPUT key value [key value ...])
	 *功能：往redisdb中添加kv数据。
	 *参数：
	 *	$key 添加数据的key值
	 *	$value 添加数据的value值
	 *返回
	 *  可以一次put多条kv数据。
   *  key value须成对出现，否则返回redis错误。
   *  若参数正确，则返回写入成功的记录数，否则返回false。
	*/
	public function dbPut($members){
		if(empty($members) || (count($members)<2)){
			return false;
		}
		$ary_params = array();
		$param1 = (string)$members[0];
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$members[1]);
		for($i=2;$i<count($members);$i+=2){
			$k = (string)$members[$i];
			$v = (string)$members[$i+1];
			
			if ($k != '' && $v != ''){
				$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>$k);
				$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>$v);
			}
		}
		$ret = $this->get_return_val(execmd_RedisProxy("DBPUT",$param1,$this->servgrp,$this->serverinfo,$ary_params));
		if(is_numeric($ret)){
			return $ret;
		}
		return false;
	}
	/*描述：增删改操作(dbget key [key …])
	 *功能：从redisdb中获取kv数据。
	 *参数：
	 *	$key 获取数据的key值（可以一次get多条kv数据）。
	 *返回
	 *  返回value数组。
	*/
	public function dbGet($keys){
		if(empty($keys)){
			return false;
		}
		$ary_params = array();
		$key1 = (string)$keys[0];
		for($i=1;$i<count($keys);$i++){
			$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$keys[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("DBGET",$key1,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：增删改操作(dbdel key [key …])
	 *功能：从redisdb中删除kv数据。
	 *参数：
	 *	$key 删除数据的key值（可以一次delete多条kv数据）。
	 *返回
	 *  返回成功删除的记录条数。
	*/
	public function dbDel($keys){
		if(empty($keys)){
			return false;
		}
		$ary_params = array();
		$key1 = (string)$keys[0];
		for($i=1;$i<count($keys);$i++){
			$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$keys[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("DBDEL",$key1,$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：增删改操作(dbpop key)
	 *功能：从redisdb中删除kv数据。
	 *参数：
	 *	$key 删除数据的key值（可以一次delete多条kv数据）。
	 *返回
	 *  返回成功删除的记录条数。
	*/
	public function dbPop($key){
		if(empty($key)){
			return false;
		}
		$value = $this->get_return_val(execmd_RedisProxy("DBPOP",$key,$this->servgrp,$this->serverinfo,array()));
		if(empty($value)){
			return false;
		}else{
			return $value;
		}
	}
	/*描述：游标管理操作(dbiter create)
	 *功能：创建一个游标（默认最多允许同时存在16个游标）。
	 *参数：
	 *	无。
	 *返回
	 *  返回创建的游标id。
	*/
	public function dbIterCreate(){
		return $this->get_return_val(execmd_RedisProxy("DBITER","CREATE",$this->servgrp,$this->serverinfo,array()));
	}
	/*描述：游标管理操作(dbiter info)
	 *功能：查看当前所有游标（默认最多允许同时存在16个游标）。
	 *参数：
	 *	无。
	 *返回
	 *  返回目前存在的游标id。
	*/
	public function dbIterInfo(){
		return $this->get_return_val(execmd_RedisProxy("DBITER","INFO",$this->servgrp,$this->serverinfo,array()));
	}
	/*描述：游标管理操作(dbiter release iter_id)
	 *功能：删除一个游标。
	 *参数：
	 *	$iter_id 游标id。
	 *返回
	 *  一次删除一个游标。
	 * 删除成功返回1，失败或者iter_id不存在则返回0。（默认10分钟不使用游标时，系统会自动清除之。）
	*/
	public function dbIterRelease($iter_id){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$iter_id);
		return $this->get_return_val(execmd_RedisProxy("DBITER","RELEASE",$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：开始备份(dbbackup start)
	 *功能：开始备份操作，有如下几种情况会导致启动备份失败：1) backup配置没有打开；2) backup正在进行中。
	 *参数：
	 *	无。
	 *返回
	 *  成功返回true，失败返回false。
	*/
	public function dbBackupStart(){
		$ret = $this->get_return_val(execmd_RedisProxy("DBBACKUP","START",$this->servgrp,$this->serverinfo,array()));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	/*描述：查看备份信息(dbbackup info)
	 *功能：查看备份信息操作。
	 *参数：
	 *	无。
	 *返回
	 *  返回备份信息。
	*/
	public function dbBackupInfo(){
		$ret = $this->get_return_val(execmd_RedisProxy("DBBACKUP","INFO",$this->servgrp,$this->serverinfo,array()));
		if(!$ret){
			return false;
		}
		$ary = array();
		$ary_1 = explode("\n", $ret);
		foreach ($ary_1 as $val_1){
			$val_1 = trim($val_1);
			$ary_2 = explode(":", $val_1);
			foreach ($ary_2 as $val_2){
				if (count($ary_2) == 2){
					$ary[$ary_2[0]] = $ary_2[1];
				}
			}
		}
		return $ary;
	}
	/*描述：停止备份(dbbackup stop)
	 *功能：停止备份操作，有如下几种情况会导致停止备份失败：1) backup配置没有打开；2) backup还未开始。
	 *参数：
	 *	无。
	 *返回
	 *  成功返回true，失败返回false。
	*/
	public function dbBackupStop(){
		$ret = $this->get_return_val(execmd_RedisProxy("DBBACKUP","STOP",$this->servgrp,$this->serverinfo,array()));
		if($ret == "OK"){
			return true;
		}
		return false;
	}
	/*描述：dbprop操作(DBPROP propname)
	 *功能：获取leveldb的属性。
	 *参数：
	 *	propname (leveldb.num-files-at-level<N>, leveldb.stats, leveldb.sstables)
	 *返回：
	 *	成功返回属性，失败返回false。
	 */
	public function dbProp($propname) {
		if (empty($propname)) {
			return false;
		}
		return $this->get_return_val(execmd_RedisProxy("DBPROP", $propname,
			$this->servgrp, $this->serverinfo, array()));
	}
	/*描述：dbapsz操作(DBAPSZ start limit)
	 *功能：获取leveldb中从start到limit的大概size。
	 *参数：
	 *	start 开始键值
	 *	limit 结束键值
	 *返回：
	 *	成功返回size，失败返回false。
	 */
	public function dbApsz($start, $limit) {
		if (empty($start) || empty($limit)) {
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$limit);
		return $this->get_return_val(execmd_RedisProxy("DBAPSZ", $start,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：dbcompact操作(DBCOMPACT start limit)
	 *功能：归并leveldb中从start到limit的所有键值。
	 *参数：
	 *	start 开始键值
	 *	limit 结束键值
	 *返回：
	 *	成功返回OK，失败返回false。
	 */
	public function dbCompact($start, $limit) {
		if (empty($start) || empty($limit)) {
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$limit);
		return $this->get_return_val(execmd_RedisProxy("DBCOMPACT", $start,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：查看主动归并信息(DBCOMPACT info)
	 *功能：查看主动归并信息操作。
	 *参数：
	 *	无。
	 *返回
	 *  返回主动归并状态信息。
	*/
	public function dbCompactInfo(){
		$ret = $this->get_return_val(execmd_RedisProxy("DBCOMPACT","INFO",$this->servgrp,$this->serverinfo,array()));
		if(!$ret){
			return false;
		}
		return $ret;
	}
	/*描述：dblist操作(DBLISTPUSH listname value [value ...])
	 *功能：往队列中添加消息。
	 *参数：
	 *	listname 队列名
	 *	value    消息数据
	 *返回：
	 *	可以一次push多条消息。
	 *	返回写入成功的消息个数，否则返回0。
	 */
	public function dbListPush($members) {
		if (empty($members) || count($members) < 2) {
			return false;
		}
		$listname = (string)$members[0];
		$ary_params = array();
		for ($i = 1; $i < count($members); ++$i) {
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$members[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("DBLISTPUSH", $listname,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：dblist操作(DBLISTPOP listname [count] [type])
	 *功能：从队列中弹出消息。
	 *参数：
	 *	listname 队列名
	 *	count    弹出的消息个数，大于0，缺省为1
	 *	type     弹出的方向，从队头(left)弹出还是从队尾(right)弹出，缺省为从队头
	 *返回：
	 *	返回的第一个参数是弹出成功的消息个数，正常情况下等于count值。
	 *	从第二个参数开始依次是第1个消息，第2个消息。。。第count个消息。
	 */
	public function dbListPop($listname, $count = 1, $type = 'left') {
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$count);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$type);
		return $this->get_return_val(execmd_RedisProxy("DBLISTPOP", $listname,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：dblist操作(DBLISTREMOVE listname)
	 *功能：删除队列。
	 *参数：
	 *	listname 队列名
	 *返回：
	 *	返回删除成功的消息个数，否则返回0。
	 */
	public function dbListRemove($listname) {
		return $this->get_return_val(execmd_RedisProxy("DBLISTREMOVE", $listname,
			$this->servgrp, $this->serverinfo, array()));
	}
	/*描述：dblist操作(DBLISTCOUNT listname)
	 *功能：队列中的消息个数。
	 *参数：
	 *	listname 队列名
	 *返回：
	 *	返回队列中的消息个数，否则返回0。
	 */
	public function dbListCount($listname) {
		return $this->get_return_val(execmd_RedisProxy("DBLISTCOUNT", $listname,
			$this->servgrp, $this->serverinfo, array()));
	}
	/*描述：dblist操作(DBLISTRANGE listname index [length])
	 *功能：从index开始，长度为length的范围内的所有消息。
	 *参数：
	 *	listname 队列名
	 *	index    起始位置(...-3, -2, -1, 0, 1, 2, 3...)
	 *	length   范围长度
	 *返回：
	 *	返回的第一个参数是范围内的消息个数, 正常情况下等于length值。
	 *	从第二个参数开始依次是第1个消息，第2个消息。。。第length个消息。
	 */
	public function dbListRange($listname, $index, $length = 50) {
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$index);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$length);
		return $this->get_return_val(execmd_RedisProxy("DBLISTRANGE", $listname,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：通用操作(key-val)
	 *功能：获取redisdb的配置信息
	 *参数：
	 *	$item_key 配置项的key
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true，false
	*/
	public function dbGetConfig($item_key){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$item_key);
		return $this->get_return_val(execmd_RedisProxy("DBCONFIG","GET",$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：通用操作(key-val)
	 *功能：设置redisdb的配置信息
	 *参数：
	 *	$item_key 配置项的key
	 *	$item_value 配置项的value
	 *返回
	 *	数据类型：成功-true，失败-false
	 *	数据：true，false
	*/
	public function dbSetConfig($item_key, $item_value){
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$item_key);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$item_value);
		return $this->get_return_val(execmd_RedisProxy("DBCONFIG","SET",$this->servgrp,$this->serverinfo,$ary_params));
	}
	/*描述：hcappedlpush操作(HCAPPEDLPUSH key memberkey max memberval [memberval ...])
	 *功能：向json数组的左侧追加n个成员。如果总成员数量超过max，需要把json数组右侧清理超出的部分。
	 *参数：
	 *	key       主键
	 *	memberkey 成员键
	 *	max       最大个数
	 *	memberval 成员键值
	 *返回：
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该键值存在，返回0，否则返回1
	 */
	public function hcappedlpush($key, $memberkey, $max, $membervals) {
		if (empty($key) || empty($memberkey) || empty($max) || empty($membervals)) {
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$memberkey);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$max);
		for ($i = 0; $i < count($membervals); ++$i) {
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$membervals[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("HCAPPEDLPUSH", $key,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：hcappedrpush操作(HCAPPEDRPUSH key memberkey max memberval [memberval ...])
	 *功能：向json数组的右侧追加n个成员。如果总成员数量超过max，需要把json数组左侧清理超出的部分。
	 *参数：
	 *	key       主键
	 *	memberkey 成员键
	 *	max       最大个数
	 *	memberval 成员键值
	 *返回：
	 *	数据类型：成功-int，失败-false
	 *	数据：如果该键值存在，返回0，否则返回1
	 */
	public function hcappedrpush($key, $memberkey, $max, $membervals) {
		if (empty($key) || empty($memberkey) || empty($max) || empty($membervals)) {
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$memberkey);
		$ary_params[1] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$max);
		for ($i = 0; $i < count($membervals); ++$i) {
			$ary_params[] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$membervals[$i]);
		}
		return $this->get_return_val(execmd_RedisProxy("HCAPPEDRPUSH", $key,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	/*描述：hpop操作(HPOP key [count])
	 *功能：从key-hashtable中弹出键和键值。
	 *参数：
	 *	key       主键
	 *	count     弹出个数，大于0，缺省为1
	 *返回：
	 *	数据类型：成功-array，失败-false
	 *	正常情况下返回count个键和键值。
	 */
	public function hpop($key, $count = 1) {
		if (empty($key)) {
			return false;
		}
		$ary_params = array();
		$ary_params[0] = array("t" => REDIS_PARAM_ARRAY, "d" => (string)$count);
		return $this->get_return_val(execmd_RedisProxy("HPOP", $key,
			$this->servgrp, $this->serverinfo, $ary_params));
	}
	
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：获取多个 key-hashtable所有成员的键和键值
	*参数：
	*	$keys数组
	*返回
	*	数据类型：成功-array，失败-false
	*	数据：返回数组array("key[0]","val[0]","key[1]","val[1]",..."key[n]","val[n]");
	*      val为json串格式
	*/
	public function hBatchGetAll($keys){
		if(empty($keys)  || !is_array($keys)){
			return false;
		}
		$key = $keys[0];
		$ary_params = array();
		for($i=1;$i<count($keys);$i++){
			$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$keys[$i]);
		}
	
		return $this->get_return_val(execmd_RedisProxy("HBATCHGETALL",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
	
	/*描述：键上关联hashtable(key-hashtable)
	 *功能：获取多个 key-hashtable多个成员的键和键值
	*参数：
	*	$keys 主key数组
	*   $fields 子key数组
	*返回
	*	数据类型：成功-array，失败-false
	*	数据：返回数组array("key[0]","val[0]","key[1]","val[1]",..."key[n]","val[n]");
	*       val为json串格式
	*/
	public function hBatchMget($keys, $fields){
		if(empty($keys) || !is_array($keys) || empty($fields) || !is_array($fields)){
			return false;
		}
		$number = count($keys);
		$key = $keys[0];
		$ary_params = array();
		for($i=1;$i<$number;$i++){
			$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$keys[$i]);
		}
	
		for($i=0;$i<count($fields);$i++){
			$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$fields[$i]);
		}
		$ary_params[] =  array("t" => REDIS_PARAM_ARRAY,"d"=>(string)$number);
		return $this->get_return_val(execmd_RedisProxy("HBATCHMGET",$key,$this->servgrp,$this->serverinfo,$ary_params));
	}
};
?>
