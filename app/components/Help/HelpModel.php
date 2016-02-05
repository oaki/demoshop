<?php

class HelpModel extends CacheModel{
	private $connection;
	
	public static function init(){
		return new self( dibi::getConnection(), NEnvironment::getCache( get_class() ) );
	}

	public function __construct(DibiConnection $connection, NCache $cache ){

		$this->connection = $connection;
		$this->cache = $cache;

	}

	public function getConnection(){
			return $this->connection;
	}

	
	function getFluent(){
		return $this->connection->select('*')->from('helps');
	}
	
	function fetchPairs(){
		return $this->getFluent()->fetchPairs('key','text');
	}
	
	function fetchAll(){
		return $this->getFluent()->fetchAll();
	}
	
	function fetch( $id ){
		return $this->getFluent()->where('id_helps = %i',$id)->fetch();
	}
	
	function insert($values){
		$this->connection->insert('helps', $values)->execute();
	}
	
	function update($values,$id){
		$this->connection->update('helps', $values)->where('id_helps = %i',$id)->execute();
	}
}