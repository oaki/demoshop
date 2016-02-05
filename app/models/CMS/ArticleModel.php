<?php

class ArticleModel extends CacheModel // object je Nette\Object
{
    private $connection;

	const MODULE_NAME = 'article';
		
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

	function getFluent(){
		return $this->connection->select("*")->from('article');
	}
	
	function get($id_node){
		$a = dibi::fetch("SELECT *, DATE_FORMAT(add_date, '%d.%m.%Y') AS add_date_formated FROM [article] WHERE id_node=%i",$id_node);
		if(!$a)
			return false;
		
		$a['files'] = self::getFiles($id_node);
		$a['first_img'] = (isset($a['files'][0]))?$a['files'][0]:array('src'=>'no-image', 'ext'=>'jpg');
		   
		return $a;
	}
       
	public static function getFiles($id_node){
		return FilesNode::getAllFiles(self::MODULE_NAME, $id_node, 'all');
//		return FilesNode::getAllFiles(self::MODULE_NAME, $id_node);
    }
	
	
	
	public function slugToId($slug){		
		$slug = rtrim($slug, '/');		
		$key = 'slugToId('.$slug.')';
		
		$id = $this->loadCache( $key );
		
		if( $id ){
			return $id;
		}else{
			$id = dibi::fetchSingle("SELECT id_node FROM [article] WHERE url_identifier LIKE %s",$slug);
			if(!$id) $id = NULL;			
		}
		
		return $this->saveCache($key, $id);
	}
	
	public function idToSlug($id){	
		
		$key = 'idToSlug('.$id.')';
//		echo $key;
//		return $name = dibi::fetchSingle("SELECT url_identifier FROM [article] WHERE id_node = %i",$id);
		$slug = $this->loadCache( $key );
		
		if( $slug ){
			return $slug;
		}else{
			$name = dibi::fetchSingle("SELECT url_identifier FROM [article] WHERE id_node = %i",$id);
			if(!$name) $name = NULL;
		}
		
		return $this->saveCache($key, $name);
		
	}

}