<?php

class SettingModel extends CacheModel // object je Nette\Object
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
		return $this->connection->select("*")->from('setting');
	}
	
	function insert($values){
		$values['date_add'] = new DibiDateTime();
		$values['date_upd'] = new DibiDateTime();
		$this->connection->insert('setting', $values)->execute();
		$this->invalidateCache();
	}
	
	function update($name,$values){
		
		$values['date_upd'] = new DibiDateTime();
		$this->connection->update('setting', $values)->where('name = %s',$name)->execute();
		$this->invalidateCache();
	}
	
	function checkIfExistName( $name ){
		return dibi::fetchSingle("SELECT 1 FROM [setting] WHERE name = %s", $name);
	}
	
	//ak existuje prepise ak nie prida
	function insert_update($name,$values){
		if($this->checkIfExistName($name)){
			$this->update($name, $values);
		}else{
			$this->insert($values);
		}
		$this->invalidateCache();
	}
	
	function fetchPairs(){
		$key = 'fetchPairs()';
		$list = $this->loadCache( $key );
		if( $list ){
			return $list;
		}else{
			$list = $this->getFluent()->fetchPairs('name','value');
		}
		
		return $this->saveCache($key, $list);		
	}
	
	function fetchPairsWithGroup(){
		$key = 'fetchPairsWithGroup()';
		$list = $this->loadCache( $key );
		if( $list ){
			return $list;
		}else{
			$values = $this->getFluent()->fetchPairs('name','value');
			$list = array();
			foreach($values as $name=>$value){
				$tmp = explode("__", $name, 2);
				if(count($tmp)>1){					
					$list[$tmp[0]][$tmp[1]] = $value;
				}else{
					$list[$name] = $value;
				}
			}
		}
//		dump($list);exit;
		return $this->saveCache($key, $list);		
	}
	
}