<?php

class NodeModel extends CacheModel // object je Nette\Object
{
	private $connection;

	const MODULE_NAME = 'node';

	public static function init(){
		return new self( dibi::getConnection(), NEnvironment::getCache( self::MODULE_NAME ) );
	}

	public function __construct(DibiConnection $connection, NCache $cache ){
		$this->connection = $connection;
		$this->cache = $cache;
	}

	public function getConnection(){
			return $this->connection;
	}
		
	public function getFluent( $id_menu_item = NULL ){
		 $f = $this->connection->select('*')
				 ->from('node')
				 ->join('type_modul')->using('(id_type_modul)');				 
		 if($id_menu_item!=NULL)
			 $f->where('id_menu_item = %i',$id_menu_item);
		
		 return $f;
	}
	
	public function getAll( $id_menu_item ){
		 return $this->getFluent($id_menu_item)->orderBy('sequence DESC');	 
	}
	
	public function get( $id_node ){
		 $c = $this->connection->select('*')
				 ->from('node')
				 ->join('type_modul')->using('(id_type_modul)')
				 ->where('id_node = %i',$id_node);				 
		 
		 return $c->fetch();	 
	}
		
		
	public function slugToId($slug){
		
		$slug = rtrim($slug, '/'); 
		echo $slug;exit;
		$key = 'slugToId'.$slug;
		
		if (!isset($this->cache[ $key ])) {
			$id = dibi::fetchSingle("SELECT id_node FROM [node] WHERE url_identifier LIKE %s",$slug);
			if(!$id) $id = NULL;
			 $this->cache[$key] = $id;
		}
		
		return $this->cache[$key];		
	}
	
	public function idToSlug($id){	
		
		$key = 'idToSlug'.$id;
		
		if (!isset($this->cache[ $key ])) {
			$name = dibi::fetchSingle("SELECT url_identifier FROM [menu_item] WHERE id_menu_item = %i",$id);
			if(!$name) $name = NULL;
			 $this->cache[$key] = $name;
		}
		
		return $this->cache[$key];
	}

}