<?php

abstract class CacheModel extends NObject{
	
	protected $cache;
	
	public function invalidateCache( ){
		return $this->cache->clean( array(
			NCache::TAGS => array( get_class($this) ) 
		));
	}
	
	public function saveCache( $key, $data ){
		
		$this->cache->save($key, $data, array(
			NCache::TAGS => array( get_class($this) )
		));
		
		return $data;
	}
	
	public function loadCache( $key ){
		
		return $this->cache[$key];
	}
	
	public function getCache(){
		return $this->cache;
	}
	
}