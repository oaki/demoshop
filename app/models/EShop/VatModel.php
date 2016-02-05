<?php

class VatModel extends CacheModel // object je Nette\Object
{
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
		return $this->connection->select('*')
				->from('vat');
	}
	
	function fetchAll(){
		return $this->getFluent()->fetchAll();
	}
	
	function getDefault(){
		return $this->connection->select("id_vat")->from('vat')->where('is_default = 1')->fetchSingle();
	}
	
	
	
	
	function delete($id){
		$this->connection->delete('vat')->where('id_vat = %i', $id)->execute();
		$this->invalidateCache();
	}
	
	function insert($value){
		$this->connection->insert('vat',$value)->execute();
		$this->invalidateCache();
	}
	
	function update($id_vat, $value){
		$this->connection->update('vat', $value)->where('id_vat = %i', $id_vat)->execute();
		$this->invalidateCache();
	}
}