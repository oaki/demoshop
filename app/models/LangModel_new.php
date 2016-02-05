<?php

class LangModel extends BaseModel{
	
	function __construct(DibiConnection $connection, ICacheStorage $cache) {
		parent::__construct($connection, $cache);
		$this->table = 'lang';		
	}
	
	
	function fetchAssoc($row = 'iso'){
		$key = 'fetchAssoc('.$row.')';
		if($this->loadCache($key)){
			return $this->cache[$key];
		}
		
		return $this->saveCache($key, $this->getFluent()->orderBy('sequence')->fetchAssoc( $row ) );
	}
	
	function convertIsoToId($iso){
		$key = 'convertIsoToId('.$iso.')';
		if($this->loadCache($key)){
			return $this->cache[$key];
		}
		
		return $this->saveCache($key, $this->getFluent()
				->removeClause('select')->select('id_lang')->fetchSingle( ) );
		
	}
	
	function getTranslate( $iso ){
		
	}
	
	
	
	
}