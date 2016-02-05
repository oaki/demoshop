<?php
/**
* @property-read DibiConnection $connection
*/
class BaseModel extends CacheModel{
	
	protected $connection;

	protected $table;
	
	protected $convention = 'id__TABLENAME__';

	public function __construct(DibiConnection $connection, ICacheStorage $fileStorage ){
		$this->connection = $connection;
		$this->cache = new NCache($fileStorage, get_class());
	}

	public function getConnection(){
		return $this->connection;
	}

	function insert( $values ){
		
		$this->connection->insert($this->table, $values)->execute();
		
		$this->invalidateCache();
		
		return $this->connection->insertId();
	}
	
	function delete( $id ){
		$this->connection->delete($this->table)->where($this->getTableIdName().'=%i', $id)->execute();
		$this->invalidateCache();
	}
	
	function update( $values, $id ){
		$this->connection->update($this->table, $values)->where($this->getTableIdName().'=%i', $id)->execute();
		$this->invalidateCache();
	}

	function getFluent(){
		return $this->connection->select('*')->from( $this->table );
	}
	
	
	public function getTableIdName(){
		return str_replace('_TABLENAME__', $this->table, $this->convention);
	}
}