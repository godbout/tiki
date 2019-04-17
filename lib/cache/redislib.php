<?php
// (c) Copyright 2002-2019 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

/**
 * Class CacheLibRedis
 *
 * Requires PHP-Redis
 */

class CacheLibRedis
{
	private $redis;

	function __construct()
	{
		global $prefs;
		if (empty($this->redis)) {
			$this->redis = new Redis();
			$success = $this->redis->pconnect($prefs["redis_host"], $prefs["redis_port"], $prefs["redis_timeout"]);
			if (!$success) {
				throw new Exception('Unable to connect to Redis.');
			}
			if ($prefs['redis_prefix']) {
				// This option automatically prefixes ALL keys provided as input to Redis
				$this->redis->setOption(Redis::OPT_PREFIX, $prefs['redis_prefix']);
			}
		}
	}

	function __destruct()
	{
		$this->redis->close();
	}

	private function getKey($key, $type) {
		$key = $type . md5($key);
		return $key;
	}

	private function findKeys($pattern) {
		global $prefs;
		$keys = $this->redis->keys($pattern);
		if ($prefs['redis_prefix']) {
			// Need to strip prefix as it will be re-added
			$keys = substr_replace($keys, '', 0, strlen($prefs['redis_prefix']));
		}
		return $keys;
	}

	function cacheItem($key, $data, $type = '')
	{
		global $prefs;
		$key = $this->getKey($key, $type);
		if ($prefs['redis_expiry']) {
			return $this->redis->setEx($key, $prefs['redis_expiry'], $data);
		} else {
			return $this->redis->set($key, $data);
		}
	}

	function isCached($key, $type = '')
	{
		$key = $this->getKey($key, $type);
		return $this->redis->exists($key);
	}

	function getCached($key, $type = '', $lastModif = false)
	{
		$key = $this->getKey($key, $type);
		return $this->redis->get($key);
	}

	function invalidate($key, $type = '')
	{
		$key = $this->getKey($key, $type);
		return $this->redis->del([$key]);
	}

	function empty_type_cache($type)
	{
		$keys = $this->findKeys($type . '*');
		return $this->redis->del($keys);
	}

	function flush() {
		global $prefs;
		if ($prefs['redis_prefix']) {
			$keys = $this->findKeys('*');
			$this->redis->del($keys);
		} else {
			$this->redis->flushAll();
		}
		return;
	}
}
